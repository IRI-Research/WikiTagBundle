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

interface DocumentTagInterface {

    /**
    * Get id
    *
    * @return integer
    */
    function getId();
    
    /**
     * Set originalOrder
     *
     * @param integer $originalOrder
     */
    function setOriginalOrder($originalOrder);
    
    /**
     * Get originalOrder
     *
     * @return integer
     */
    function getOriginalOrder();
    
    /**
     * Set tagOrder
     *
     * @param integer $tagOrder
     */
    function setTagOrder($tagOrder);
    
    /**
     * Get tagOrder
     *
     * @return integer
     */
    function getTagOrder();
    
    /**
     * Set indexNote
     *
     * @param float $indexNote
     */
    function setIndexNote($indexNote);
    
    /**
     * Get indexNote
     *
     * @return float
     */
    function getIndexNote();
    
    /**
     * Set wikipediaRevisionId
     *
     * @param bigint $wikipediaRevisionId
     */
    function setWikipediaRevisionId($wikipediaRevisionId);
    
    /**
     * Get wikipediaRevisionId
     *
     * @return bigint
     */
    function getWikipediaRevisionId();
    
    /**
     * Set tag
     *
     * @param object $tag
     */
    function setTag($tag);
    
    /**
     * Get tag
     *
     * @return object
     */
    function getTag();
    
    /**
     * Set document
     *
     * @param object $document
     */
    function setDocument($document);
    
    /**
     * Get document
     *
     * @return object
     */
    function getDocument();
    
   /**
    * return the utc time when this object has been created
    */
    function getCreatedAt();
    
    /**
    * Set created at date
    *
    * @param DateTime $date
    */
    function setCreatedAt($date);
    
    

}