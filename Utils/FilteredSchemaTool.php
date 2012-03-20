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

use Doctrine\ORM\Tools\SchemaTool;

class FilteredSchemaTool extends SchemaTool
{
    
    private $container;
    
    public function getContainer()
    {
        return $this->container;
    }
    
    public function __construct($em, $container)
    {
        parent::__construct($em);
        $this->container = $container;
    }
    
    private function filterSchemaSql($sqls)
    {
        // get service
        $schema_utils = $this->getContainer()->get("wikitag.shema_utils");
        
        $res_sqls = $schema_utils->filter_foreign_key($sqls);
        $res_sqls = $schema_utils->filter_index_creation($res_sqls);
        
        
        return $res_sqls;
    }
    
    /**
     * (non-PHPdoc)
     * @see Doctrine\ORM\Tools.SchemaTool::getCreateSchemaSql()
     */
    public function getCreateSchemaSql(array $classes)
    {
        $res_sqls = parent::getCreateSchemaSql($classes);
        return $this->filterSchemaSql($res_sqls);
    }
    
    /**
     * (non-PHPdoc)
     * @see Doctrine\ORM\Tools.SchemaTool::getUpdateSchemaSql()
     */
    public function getUpdateSchemaSql(array $classes, $saveMode=false)
    {
        $res_sqls = parent::getUpdateSchemaSql($classes, $saveMode);
        return $this->filterSchemaSql($res_sqls);
    }
    
}