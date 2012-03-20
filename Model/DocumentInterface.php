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

use Doctrine\Common\Collections\ArrayCollection;

interface DocumentInterface {
    
    /**
    * Get id
    *
    * @return integer
    */
    function getId();
    
    
    /**
     * Set the external id i.e. the host document id
     *
     * @param text $externalId
     */
    function setExternalId($externalId);
    
    /**
     * Get the external id, i.e. the host document id
     */
    function getExternalId();
    
    
    /**
     * Set manualOrder
     *
     * @param boolean $manualOrder
     */
    function setManualOrder($manualOrder);
    
    /**
     * Get manualOrder
     *
     * @return boolean
     */
    function getManualOrder();
    
    /**
     * Get the list of tags
     *
     * @return array of IRI\Bundle\WikiTagBundle\Model\DocumentTagInterface
     */
    function getTags();
    
    /**
     * Get tagsStr
     *
     * @return string
     */
    function getTagsStr();
    
    /**
     * Set tagsStr
     *
     * @param $tagsStr
     */
    function setTagsStr($tagsStr);
    
   /**
    * return the utc time when this object has been created
    */
    function getCreatedAt();
    
    /**
     * __toString magic method
     */
    function __toString();
            
}
