<?php
/*
 * This file is part of the WikiTagBundle package.
 *
 * (c) IRI <http://www.iri.centrepompidou.fr/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace IRI\Bundle\WikiTagBundle\Model;

interface CategoryInterface {
    
    /**
    * Get id
    *
    * @return integer
    */
    function getId();
    
    /**
     * Set id
     *
     * @param integer $id
     */
    function setId($id);
    
    /**
     * Set label
     *
     * @param string $label
     */
    function setLabel($label);
    
    /**
     * Get label
     *
     * @return string
     */
    function getLabel();
    
}