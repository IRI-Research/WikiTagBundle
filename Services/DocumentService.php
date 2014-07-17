<?php
/*
 * This file is part of the WikiTagBundle package.
 *
 * (c) IRI <http://www.iri.centrepompidou.fr/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace IRI\Bundle\WikiTagBundle\Services;
 
use IRI\Bundle\WikiTagBundle\Entity\DocumentTag;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;
use IRI\Bundle\WikiTagBundle\Utils\WikiTagUtils;

class DocumentService extends ContainerAware
{
    /**
     * Get the container associated with this service.
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }
    
    /**
     * Public constructor with container as parameter for contruct injection.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->setContainer($container);
    }
    
    private $doctrine;
    
    public function getDoctrine()
    {
        if(is_null($this->doctrine))
        {
            $this->doctrine = $this->getContainer()->get('doctrine');
        }
        return $this->doctrine;
    }
    

    /**
     * 	Copy the list of tags of one document to another.
     * 	The ids are the ids of the "host" document.
	 * 	Beware, both Documents must exists in the database. therefore this method can be called only after a EntityManager::push()
	 * 	If one or the other doc is not found, an execption is raised.
	 *
     * @param mixed $id_doc_src the source document id
     * @param mixed $id_doc_tgt the target document id
     */
    public function copyTags($id_doc_src, $id_doc_tgt)
    {
        
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getEntityManager();
        
        $doc_rep = $em->getRepository("WikiTagBundle:Document");
        
        $src_doc = $doc_rep->findOneByExternalId($id_doc_src);
        if(is_null($src_doc))
        {
            throw new \Exception("cloneTags: no source doc");
        }
        
        $tgt_doc = $doc_rep->findOneByExternalId($id_doc_tgt);
        if(is_null($tgt_doc))
        {
            throw new \Exception("cloneTags: no target doc");
        }
        
        
        $doc_rep->copyTags($src_doc, $tgt_doc);
        
        $em->flush();
        
    }
    
    /**
     * Add a new tag (or tags) to a "host" document.
     * If the label already exists, an exception is raised.
     * Also, the document must exists in the database, i.e. Entitymanager::flush() must have been called on this objects before.
     *
     * @param mixed $doc the document to add the tags to
     * @param string|array $tag_label : the label of the new tag
     */
    public function addTags($doc, $tag_labels)
    {
        if(is_null($tag_labels) || (!is_string($tag_labels) && !is_array($tag_labels)) ) {
            return;
        }
        // We get the DocumentTags
        $em = $this->getDoctrine()->getEntityManager();

        $class = $this->getContainer()->getParameter("wiki_tag.document_class");
        
        if(! is_a($doc,"\IRI\Bundle\WikiTagBundle\Model\DocumentInterface")) {
            $doc_rep = $this->getDoctrine()->getRepository('WikiTagBundle:Document');
            if(is_a($doc, $class)) {
                // Get the document column id, set in the config file.
                $doc_id = $doc_rep->reflectionGetField($doc, Container()->getParameter("wiki_tag.document_id_column"));
            }
            else {
                $doc_id = $doc;
            }
            $doc = $doc_rep->findOneByExternalId($doc_id);
        }
        
        
        if(!is_array($tag_labels)) {
            $tag_labels = array($tag_labels);
        }
        
        foreach ($tag_labels as $tag_label) {
        
            $normalized_tag_label = WikiTagUtils::normalizeTag($tag_label);
            $created = false;
            
            $query = $em->createQuery("SELECT COUNT(dt.id) FROM WikiTagBundle:DocumentTag dt JOIN dt.tag t WHERE dt.document = :id_doc AND t.normalizedLabel = :label");
            $query->setParameters(array("id_doc"=>$doc, "label"=>$normalized_tag_label));
            
            $nb_tags = $query->getSingleScalarResult();
            
            if($nb_tags == 0) {
                # look in unit of work
                $uow = $em->getUnitOfWork();
                foreach($uow->getScheduledEntityInsertions() as $entity) {
                    if(is_a($entity, "\IRI\Bundle\WikiTagBundle\Model\DocumentTagInterface")) {
                        $tag = $entity->getTag();
                        if(!is_null($tag)) {
                            if($tag->getNormalizedLabel() === $normalized_tag_label)
                            {
                                $nb_tags++;
                                break;
                            }
                        }
                    }
                }
            }
            
            // If the label was found, we sent a bad request
            if($nb_tags > 0) {
                throw new WikiTagServiceException(sprintf("Le tag %s existe déjà pour cette fiche.", $tag_label), 400, null, "duplicate_tag");
            }
            // returns array($tag, $revision_id, $created)
            try {
                $ar = $this->getDoctrine()->getRepository('WikiTagBundle:Tag')->getOrCreateTag($tag_label, $this->getContainer()->getParameter('wiki_tag.ignore_wikipedia_error'), $this->getContainer()->get('logger'));
            }
            catch (\Exception $e){
                throw new WikiTagServiceException($e->getMessage(), 500 , $e, "wikipedia_request_failed");
            }
            
            $tag = $ar[0];
            $revision_id = $ar[1];
            $created = $ar[2];
            
            if(!$created) {
                $query = $em->createQuery("SELECT COUNT(dt.id) FROM WikiTagBundle:DocumentTag dt WHERE dt.document = :id_doc AND dt.tag = :tag");
                $query->setParameters(array("id_doc"=>$doc, "tag"=>$tag));
                $nb_tags = $query->getSingleScalarResult();
            }
            
            if($created || $nb_tags==0){
                $max_order = $this->getDoctrine()->getRepository('WikiTagBundle:DocumentTag')->getMaxOrder($doc_id);
                $new_order = $max_order + 1;
                $new_DT = new DocumentTag();
                $new_DT->setDocument($doc);
                $new_DT->setTag($tag);
                $new_DT->setOriginalOrder($new_order);
                $new_DT->setTagOrder($new_order);
                $new_DT->setWikipediaRevisionId($revision_id);
                $em->persist($new_DT);
            }
        }
                
    }
    
    /**
     * Returns the list of tags labels for @author ymh
     *
     * @param mixed $id_doc the document id.
     * @throws WikiTagServiceException if the document is not found
     */
    public function getTagLabels($id_doc)
    {
        $rep = $this->getDoctrine()->getRepository('WikiTagBundle:Document');
        $doc = $rep->findOneByExternalId($id_doc);
        
        if(is_null($doc)) {
            throw new WikiTagServiceException("Unknown document id");
        }
        
        return $rep->getTagsStr($doc);
    }
    
   	/**
     * Service to reorder the tags using their notes in the index search.
     * This service is configured in the configuration file by affecting the weight in each field definition.
     *
     * @param IRI\Bundle\WikiTagBundle\Model\DocumentInterface $document
     */
    public function reorderTags($document)
    {
        $doctrine = $this->getContainer()->get('doctrine');
    
        $tags_score = array();
    
        foreach($document->getTags() as $tag)
        {
            $label = $tag->getTag()->getLabel();
    
            $score_res = $this->search($label, array("id"=>$document->getId()));
    
            if(count($score_res)>0)
            {
                $score = floatval($score_res[0]['score']);
            }
            else
            {
                $score = 0.0;
            }
            $tags_score[] = array($score,$tag);
        }
        // sort tags based on score
        $i=1;
        usort($tags_score, function($a, $b) {
            return $a[0]<$b[0]?1:-1;
        });
    
        foreach($tags_score as $item)
        {
            $tag = $item[1];
            $tag->setTagOrder($i++);
            $tag->setIndexNote($item[0]);
            $doctrine->getEntityManager()->persist($tag);
        }
    
    }
    
        
}