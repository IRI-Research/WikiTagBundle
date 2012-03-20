<?php
/*
 * This file is part of the WikiTagBundle package.
 *
 * (c) IRI <http://www.iri.centrepompidou.fr/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace IRI\Bundle\WikiTagBundle\Listener;

use Doctrine\DBAL\Schema\Table;

use Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\ToolEvents;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Doctrine ORM listener updating the document index
 *
 * @author ymh
 *
 */
class WikiTagDocumentListener implements EventSubscriber
{
    
    /**
    * @var ContainerInterface
    */
    private $container;
    
    /**
     * Constructor
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    public function getContainer()
    {
        return $this->container;
    }
    
    public function getSubscribedEvents()
    {
        return array(
            Events::loadClassMetadata,
            ToolEvents::postGenerateSchemaTable,
        );
    }
    
    
    /**
     * callback function executed when the locadClassMetadata event is raised.
     *
     * @param LoadClassMetadataEventArgs $args
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $args)
    {
        //check that IRI\\Bundle\\WikiTagBundle\\Entity\\Document exists. if not create it and load it
        //
        $path = $this->container->getParameter('kernel.cache_dir')."/wikitag";
        $file = "$path/IRI/Bundle/WikiTagBundle/Entity/Document.php";
        $config_file = $this->container->getParameter('kernel.root_dir')."/app/config/config.yml";
        
        if(file_exists($file) && file_exists($config_file) && (filemtime($file)<filemtime($config_file)))
        {
            unlink($file);
        }
        if(!class_exists("\IRI\Bundle\WikiTagBundle\Entity\Document"))
        {
            $schema_utils = $this->getContainer()->get("wikitag.shema_utils");
            $classCode = $schema_utils->generateDocumentClass();
            
            $logger = $this->container->get('logger');
            $logger->debug("File to generate : $file");
            if(!file_exists(dirname($file)) && !mkdir(dirname($file),0777,true))
            {
                throw new Exception("Impossible to create document file");
            }
            file_put_contents($file, $classCode);
            
            $document_schema = $args->getEntityManager()->getClassMetadata("IRI\\Bundle\\WikiTagBundle\\Entity\\Document");
            return;
        }
        
        
        $metadata = $args->getClassMetadata();
        if($metadata->name === "IRI\\Bundle\\WikiTagBundle\\Entity\\Document")
        {
            $document_class = $this->container->getParameter('wiki_tag.document_class');
            
            $logger = $this->container->get('logger');
            $logger->debug("DocumentListener: Add ExternalId Mapping");

            $document_id_column = $this->container->getParameter('wiki_tag.document_id_column');
            
            $logger->debug("DocumentListener: external id def : " . print_r($document_id_column, true));
            
            /*$target_metadata = $args->getEntityManager()->getClassMetadata($document_class);
            $mapping = array_replace(array(), $target_metadata->getFieldMapping($document_id_column));
            $mapping['fieldName'] = 'externalId';
            $mapping['columnName'] = 'external_id';
            $mapping['id'] = false;
            $metadata->mapField($mapping);*/
            $metadata->mapOneToOne(array(
                'targetEntity' => $document_class,
                'fieldName' => 'externalId',
                'joinColumns' => array(0=>array(
                    'name' => 'external_id',
                    'referencedColumnName' => $document_id_column
                )),
            ));
            
            //map the fields
            $fields = $this->container->getParameter('wiki_tag.fields');
            
            $def_columns = array();
            foreach ( $fields as $name => $field_def)
            {
                if(isset($field_def['type']))
                {
                    $type = $field_def['type'];
                }
                if(!isset($type) || is_null($type) || strlen($type) == 0)
                {
                    $type = "text";
                }
                $mapping = array('fieldName' => $name, 'type' => $type);
                if($type == 'string')
                {
                    if(isset($field_def['length']))
                    {
                        $length = $field_def['length'];
                    }
                    if(!isset($length))
                    {
                        $length = 1024;
                    }
                    elseif (!is_int($length))
                    {
                        $length = intval($length);
                    }
                    $mapping['length'] = $length;
                }
                $metadata->mapField($mapping);
                $def_columns[] = $name;
                $metadata->table['indexes']["${name}_document_fulltext_idx"] = array( 'columns' => array("$name",));
            }
            $def_columns[] = "tags_str";
            $metadata->table['indexes']["all_document_fulltext_idx"] = array('columns'=> $def_columns);
        }
    }
    
    public function postGenerateSchemaTable(GenerateSchemaTableEventArgs $args)
    {
    
        if($args->getClassMetadata()->name === "IRI\\Bundle\\WikiTagBundle\\Entity\\Document")
        {
            $logger = $this->container->get('logger');
            $logger->debug("Generate schema table ".$args->getClassTable()->getName());
    
            $args->getClassTable()->addOption('engine','MyISAM');
        }
    }
    
    
}