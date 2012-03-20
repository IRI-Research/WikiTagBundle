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
 
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SearchService extends ContainerAware
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
    
    /**
     * doctrine object getter
     * @return object
     */
    public function getDoctrine()
    {
        if(is_null($this->doctrine))
        {
            $this->doctrine = $this->getContainer()->get('doctrine');
        }
        return $this->doctrine;
    }
    
    
    /**
     * Search.
     *
     * @param mixed $value : either a string or the list of fields to search into ; format : ['<field_name>' => ['weight'=><relative weight of the field>, 'value' => value]]
     * @param array $conditions : array : key : field name, value : simple value (operator is "=") or array(valuea, value2,...) (operator is IN) or array("operator"=>"=","!=","<".">","<=".">=","like","ilike","in">, "value"=>value)
     * @param array $fields : The field of field names to export, one of the list specified in the configuration file
     * @return array [{'host_doc_id'=>,'wikitag_doc_id'=>, 'wikitag_doc'=>, '_score'=>,'field1'=>, 'field2'=>, }]
     */
    public function search($value, array $conditions = null, array $fields = null)
    {
        if(is_null($value) || (is_string($value) && strlen($value) == 0) || count($value)==0 ) {
            return null;
        }
        
        $fielddeflist = $this->getContainer()->getParameter("wiki_tag.fields");
        $fieldquery = array();
        
        // build filed query
        if(is_string($value)) {
            foreach ($fielddeflist as $fieldname => $fielddef) {
                if(!is_null($fielddef) && isset($fielddef['weight'])) {
                    $weight = $fielddef['weight'];
                }
                else
                {
                    $weight = 1.0;
                }
                $fieldquery[] = array("columns"=>$fieldname, "value"=>$value, "weight"=>$weight);
            }
        }
        else {
            foreach ($value as $fieldname => $fielddef) {
                if(is_null($fielddef) || !isset($fielddef['value']) || strlen($fielddef['value']) == 0) {
                    continue;
                }
                $valuefield = $fielddef['value'];
                if(isset($fielddef['weight'])) {
                    $weight = $fielddef['weight'];
                }
                else
                {
                    $weight = 1.0;
                }
                
                $fieldquery[] = array("columns"=>$fieldname, "value"=>$valuefield, "weight"=>$weight);
            }
        }
        
        // buildf
        if(is_null($fields))
        {
            $fieldnamelist = array();
            foreach ($fielddeflist as $fieldname => $fielddef) {
                $fieldnamelist[] = $fieldname;
            }
        }
        else {
            $fieldnamelist = $fields;
        }
        
        $doctrine = $this->getContainer()->get('doctrine');
        $rep = $doctrine->getRepository('WikiTagBundle:Document');
                
        $score_res = $rep->search($fieldquery, $conditions, $fieldnamelist);

        $res = array();
        foreach($score_res as $single_res) {
            $res_entry = array();
            $res_entry['host_doc_id'] = $single_res[0]->getExternalId()->getId();
            $res_entry['wikitag_doc_id'] = $single_res[0]->getId();
            foreach($fieldnamelist as $fieldname) {
                $accessor_name = "get".ucfirst($fieldname);
                $res_entry[$fieldname] = $single_res[0]->{$accessor_name}();
            }
            $res_entry['_score'] = $single_res['score'];
            $res_entry['wikitag_doc'] = $single_res[0];
            $res[] = $res_entry;
        }
        
        return $res;
    }
    
    
    /**
     * get a list of tags label sprted by their number of documents tagged.
     * @param int $max_tags the max number of tags to return
     * @return array of array of tags,
     */
    public function getTagCloud($max_tags)
    {
        $rep = $this->getDoctrine()->getRepository('WikiTagBundle:Tag');
        return $rep->getTagCloud($max_tags);
    }
    
    /**
     * List the tag label containing the seed given as an argument.
     * The seed is either at the beggining, the end of the label or at the beggining of a word.
     * @param string $seed
     * @param boolean $doc_nb
     * @return : an array containing the possible tag labels with the number of documents
     */
    public function completion($seed, $doc_nb=False)
    {
        $rep = $this->getDoctrine()->getRepository('WikiTagBundle:Tag');
        
        $res = array();
        foreach ($rep->getCompletion($seed) as $value) {
            $res[] = array('label' => $value['label'], 'nb_docs' => $value['nb_docs']);
        }
        
        return $res;
    }
    
        
}