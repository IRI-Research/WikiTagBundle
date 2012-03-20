<?php
/*
 * This file is part of the WikiTagBundle package.
 *
 * (c) IRI <http://www.iri.centrepompidou.fr/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace IRI\Bundle\WikiTagBundle\Utils;

use Mandango\Mondator\Definition\Definition;
use Mandango\Mondator\Definition\Property;
use Mandango\Mondator\Definition\Method;
use Mandango\Mondator\Dumper;

class SchemaUtils
{
    /**
     * The container for the service
     * @var unknown_type
     */
    protected $container;
    
    /**
     * Accessor for the container property.
     */
    public function getContainer()
    {
        return $this->container;
    }
    
    /**
     *
     * construct the shema utils service injects the container
     * @param unknown_type $container
     */
    public function __construct($container)
    {
       $this->container = $container;
    }
    
    
    
    /**
     * Return the sql to create the document table full text indexes
     * @return array
     */
    public function createFullTextIndexes()
    {
        $sql_code = array();
        $fields = $this->getContainer()->getParameter('wiki_tag.fields');
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
        
            if($type === 'text')
            {
                $def_column = "$name(4096)";
            }
            else
            {
                $def_column = $name;
            }
            $def_columns[] = $def_column;
        
            $sql_code[] = "ALTER IGNORE TABLE wikitag_document DROP INDEX ${name}_document_fulltext_idx";
            $sql_code[] = "ALTER TABLE wikitag_document ADD FULLTEXT INDEX ${name}_document_fulltext_idx ($def_column)";
        }

        $sql_code[] = "ALTER IGNORE TABLE wikitag_document DROP INDEX tags_str_document_fulltext_idx";
        $sql_code[] = "ALTER TABLE wikitag_document ADD FULLTEXT INDEX tags_str_document_fulltext_idx (tags_str)";
        
        $sql_code[] = "ALTER IGNORE TABLE wikitag_document DROP INDEX all_document_fulltext_idx";
        $sql_code[] = "ALTER TABLE wikitag_document ADD FULLTEXT INDEX all_document_fulltext_idx (".join(",", $def_columns).")";
        
        return $sql_code;
        
    }
    
    public function filter_foreign_key(array $sqls)
    {
        $res_sqls = array();
        foreach ($sqls as $sql) {
            if(!preg_match("/ADD CONSTRAINT .+ FOREIGN KEY \(.*\) REFERENCES wikitag_document\(id\)/i", $sql))
            {
                $res_sqls[] = $sql;
            }
        }
        
        return $res_sqls;
        
    }
    
    public function filter_index_creation(array $sqls)
    {
        $res_sqls = array();
        
        $replace_regexps = array();
        
        $fields = $this->getContainer()->getParameter('wiki_tag.fields');
        $field_names = array();
        foreach ( $fields as $name => $field_def)
        {
            // create regular expression
            $replace_regexps[] = "/INDEX (${name}_document_fulltext_idx (?:ON wikitag_document ){0,1}\(${name}\))/";
            $field_names[] = " ?${name},?";
        }
        $field_names[] = " ?tags_str,?";
        $replace_regexps[] = "/INDEX (tags_str_document_fulltext_idx (?:ON wikitag_document ){0,1}\(tags_str\))/";
        $replace_regexps[] = "/INDEX (all_document_fulltext_idx (?:ON wikitag_document ){0,1}\((?:".implode("|",$field_names)."){".count($field_names)."}\))/";
        
        foreach($sqls as $sql)
        {
            if(strrpos($sql,"wikitag_document"))
            {
                $sql = preg_replace($replace_regexps, "FULLTEXT INDEX $1", $sql);
            }
            $res_sqls[] = $sql;
        }
        
        return $res_sqls;
    }
    
    public function generateDocumentClass()
    {
        $definition = new Definition('IRI\Bundle\WikiTagBundle\Entity\Document');
        
        $definition->setParentClass('\IRI\Bundle\WikiTagBundle\Model\Document');
        
        $fields = $this->getContainer()->getParameter('wiki_tag.fields');
        
        foreach ( $fields as $name => $field_def)
        {
            $property = new Property("private", $name, NULL);
            $definition->addProperty($property);
        
            $get_method = new Method("public", "get".ucfirst($name), NULL, <<<EOF
                        return \$this->$name;
EOF
            );
            $definition->addMethod($get_method);
        
            $set_method = new Method("public", "set".ucfirst($name), "\$$name", <<<EOF
                        \$this->$name = \$$name;
EOF
            );
            $definition->addMethod($set_method);
        
        }
        
        $dumper = new Dumper($definition);
        $classCode = $dumper->dump();
        
        return $classCode;
    }
}