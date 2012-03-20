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
use Doctrine\Common\Util\Debug;

abstract class Document implements DocumentInterface {
    
    /**
    * @var integer $id
    */
    protected $id;
    
    /**
     * @var boolean $manualOrder
     */
    protected $manualOrder = false;
    
    /**
     * @var mixed $externalId
     */
    protected $externalId;
    
    /**
     * @var ArrayCollection $tags
     */
    protected $tags;
    
    /**
     * @var string tagsStr
     */
    protected $tagsStr;
        
   /**
    * @var DateTime
    */
    protected $createdAt;
    
    
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
    * Set manualOrder
    *
    * @param boolean $manualOrder
    */
    function setManualOrder($manualOrder)
    {
        $this->manualOrder = $manualOrder;
    }
    
    /**
     * Get manualOrder
     *
     * @return boolean
     */
    function getManualOrder()
    {
        return $this->manualOrder;
    }
    
    /**
     * (non-PHPdoc)
     * @see IRI\Bundle\WikiTagBundle\Model.BaseDocumentInterface::setExternalId()
     */
    function setExternalId($externalId)
    {
        $this->externalId = $externalId;
    }
    
    /**
     * (non-PHPdoc)
     * @see IRI\Bundle\WikiTagBundle\Model.DocumentInterface::getExternalId()
     */
    function getExternalId()
    {
        return $this->externalId;
    }
    
    /**
     * (non-PHPdoc)
     * @see IRI\Bundle\WikiTagBundle\Model.DocumentInterface::getTags()
     */
    function getTags()
    {
        return $this->tags;
    }
    
    /**
     * (non-PHPdoc)
     * @see IRI\Bundle\WikiTagBundle\Model.DocumentInterface::setTagsStr()
     */
    function setTagsStr($tagsStr)
    {
        $this->tagsStr = $tagsStr;
    }
    
   	/**
     * (non-PHPdoc)
     * @see IRI\Bundle\WikiTagBundle\Model.DocumentInterface::getTagsStr()
     */
    function getTagsStr()
    {
        return $this->tagsStr;
    }
    
    /**
     * (non-PHPdoc)
     * @see IRI\Bundle\WikiTagBundle\Model.DocumentInterface::getCreatedAt()
     */
    function getCreatedAt()
    {
        return $this->createdAt;
    }
    
    /**
    *
    * construct the class
    */
    function __construct()
    {
        $this->createdAt = new \DateTime("now", new \DateTimeZone('UTC'));
    }
    
    /**
     * (non-PHPdoc)
     * @see IRI\Bundle\WikiTagBundle\Model.DocumentInterface::__toString()
     */
    public function __toString()
    {
        return print_r(Debug::export($this, 3),true);
    }

}
