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

abstract class DocumentTag implements DocumentTagInterface {
    
    /**
    * @var integer $id
    */
    protected $id;
    
    /**
     * @var integer $originalOrder
     */
    protected $originalOrder;
    
    /**
     * @var integer $tagOrder
     */
    protected $tagOrder;
    
    /**
     * @var float $indexNote
     */
    protected $indexNote = 0.0;
    
    /**
     * @var bigint $wikipediaRevisionId
     */
    protected $wikipediaRevisionId;
    
    /**
     * @var object $tag
     */
    protected $tag;
    
    /**
     * @var object $document
     */
    protected $document;
    
    
   /**
    * @var DateTime
    */
    protected $createdAt;
    
    /**
    *
    * construct the class
    */
    function __construct()
    {
        $this->createdAt = new \DateTime("now", new \DateTimeZone('UTC'));
    }
    
    
    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * Set originalOrder
     *
     * @param integer $originalOrder
     */
    public function setOriginalOrder($originalOrder)
    {
        $this->originalOrder = $originalOrder;
    }
    
    /**
     * Get originalOrder
     *
     * @return integer
     */
    public function getOriginalOrder()
    {
        return $this->originalOrder;
    }
    
    /**
     * Set tagOrder
     *
     * @param integer $tagOrder
     */
    public function setTagOrder($tagOrder)
    {
        $this->tagOrder = $tagOrder;
    }
    
    /**
     * Get tagOrder
     *
     * @return integer
     */
    public function getTagOrder()
    {
        return $this->tagOrder;
    }
    
    /**
     * Set indexNote
     *
     * @param float $indexNote
     */
    public function setIndexNote($indexNote)
    {
        $this->indexNote = $indexNote;
    }
    
    /**
     * Get indexNote
     *
     * @return float
     */
    public function getIndexNote()
    {
        return $this->indexNote;
    }
    
    /**
     * Set wikipediaRevisionId
     *
     * @param bigint $wikipediaRevisionId
     */
    public function setWikipediaRevisionId($wikipediaRevisionId)
    {
        $this->wikipediaRevisionId = $wikipediaRevisionId;
    }
    
    /**
     * Get wikipediaRevisionId
     *
     * @return bigint
     */
    public function getWikipediaRevisionId()
    {
        return $this->wikipediaRevisionId;
    }
    
    /**
     * Set tag
     *
     * @param object $tag
     */
    public function setTag($tag)
    {
        $this->tag = $tag;
    }
    
    /**
     * Get tag
     *
     * @return object
     */
    public function getTag()
    {
        return $this->tag;
    }
    
    /**
     * Set document
     *
     * @param object $document
     */
    public function setDocument($document)
    {
        $this->document = $document;
    }
    
    /**
     * Get document
     *
     * @return object
     */
    public function getDocument()
    {
        return $this->document;
    }
    
   /**
    * (non-PHPdoc)
    * @see IRI\Bundle\WikiTagBundle\Model.DocumentTagInterface::getCreatedAt()
    */
    function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
    * Set date of creation
    *
    * @param DateTime $date
    */
    public function setCreatedAt($date)
    {
        $this->createdAt = $date;
    }
    
    
    /**
    * Get wikipedia_version_permalink
    *
    * @return string
    */
    public function getWikipediaVersionPermalink()
    {
        $WIKIPEDIA_VERSION_PERMALINK_TEMPLATE = "http://fr.wikipedia.org/w/index.php?oldid=";
        return $WIKIPEDIA_VERSION_PERMALINK_TEMPLATE.$this->wikipediaRevisionId;
    }
    
    
    
}