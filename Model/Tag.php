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

use IRI\Bundle\WikiTagBundle\Utils\WikiTagUtils;

abstract class Tag implements TagInterface {
    
    public static $TAG_URL_STATUS_DICT = array('null_result'=>0,'redirection'=>1,'homonyme'=>2,'match'=>3, 'unsemantized'=>4);
     
    /**
     * @var integer $id
     */
    protected $id;

    /**
     * @var string $label
     */
    protected $label;

    /**
     * @var string $normalizedLabel
     */
    protected $normalizedLabel;

    /**
     * @var string $originalLabel
     */
    protected $originalLabel;
    
    /**
     * @var $alternativeLabel
     */
    protected $alternativeLabel;

    /**
     * @var string $alias
     */
    protected $alias;

    /**
     * @var string $wikipediaUrl
     */
    protected $wikipediaUrl;

    /**
    * @var string $alternativeWikipediaUrl
    */
    protected $alternativeWikipediaUrl;
            
    /**
     * @var bigint $wikipediaPageId
     */
    protected $wikipediaPageId;

   /**
    * @var bigint $alternativeWikipediaPageId
    */
    protected $alternativeWikipediaPageId;
    
    /**
     * @var smallint $urlStatus
     */
    protected $urlStatus;

    /**
     * @var string $dbpediaUri
     */
    protected $dbpediaUri;

    /**
     * @var integer $popularity
     */
    protected $popularity = 0;

    /**
     * @var object $category
     */
    protected $category;

    /**
     * @var ArrayCollection $documents
     */
    protected $documents;
    
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
     * Set id
     *
     * @param integer $id
     */
    function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Set label
     *
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
        $this->normalizedLabel = WikiTagUtils::normalizeTag($label);
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set normalizedLabel
     *
     * @param string $normalizedLabel
     */
    public function setNormalizedLabel($normalizedLabel)
    {
        $this->normalizedLabel = $normalizedLabel;
    }

    /**
     * Get normalizedLabel
     *
     * @return string
     */
    public function getNormalizedLabel()
    {
        return $this->normalizedLabel;
    }

    /**
     * Set originalLabel
     *
     * @param string $originalLabel
     */
    public function setOriginalLabel($originalLabel)
    {
        $this->originalLabel = $originalLabel;
    }

    /**
     * Get originalLabel
     *
     * @return string
     */
    public function getOriginalLabel()
    {
        return $this->originalLabel;
    }

    /**
    * Set alternativeLabel
    *
    * @param string $alternativeLabel
    */
    public function setAlternativeLabel($alternativeLabel)
    {
        $this->alternativeLabel = $alternativeLabel;
    }
    
    /**
     * Get alternativeLabel
     *
     * @return string
     */
    public function getAlternativeLabel()
    {
        return $this->alternativeLabel;
    }
    
    
    
    /**
     * Set alias
     *
     * @param string $alias
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
    }

    /**
     * Get alias
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Set wikipediaUrl
     *
     * @param string $wikipediaUrl
     */
    public function setWikipediaUrl($wikipediaUrl)
    {
        $this->wikipediaUrl = $wikipediaUrl;
    }

    /**
     * Get wikipediaUrl
     *
     * @return string
     */
    public function getWikipediaUrl()
    {
        return $this->wikipediaUrl;
    }

    
    /**
    * Set alternativeWikipediaUrl
    *
    * @param string $alternativeWikipediaUrl
    */
    public function setAlternativeWikipediaUrl($alternativeWikipediaUrl)
    {
        $this->alternativeWikipediaUrl = $alternativeWikipediaUrl;
    }
    
    /**
     * Get alternativeWikipediaUrl
     *
     * @return string
     */
    public function getAlternativeWikipediaUrl()
    {
        return $this->alternativeWikipediaUrl;
    }
    
    
    /**
     * Set wikipediaPageId
     *
     * @param bigint $wikipediaPageId
     */
    public function setWikipediaPageId($wikipediaPageId)
    {
        $this->wikipediaPageId = $wikipediaPageId;
    }

    /**
     * Get wikipediaPageId
     *
     * @return bigint
     */
    public function getWikipediaPageId()
    {
        return $this->wikipediaPageId;
    }

    /**
    * Set alternativeWikipediaPageId
    *
    * @param bigint $alternativeWikipediaPageId
    */
    function setAlternativeWikipediaPageId($alternativeWikipediaPageId)
    {
        $this->alternativeWikipediaPageId = $alternativeWikipediaPageId;
    }
    
    /**
     * Get alternativeWikipediaPageId
     *
     * @return bigint
     */
    function getAlternativeWikipediaPageId()
    {
        return $this->alternativeWikipediaPageId;
    }
    
    
    /**
     * Set urlStatus
     *
     * @param smallint $urlStatus
     */
    public function setUrlStatus($urlStatus)
    {
        $this->urlStatus = $urlStatus;
    }

    /**
     * Get urlStatus
     *
     * @return smallint
     */
    public function getUrlStatus()
    {
        return $this->urlStatus;
    }
    
    /**
    * Get UrlStatusText
    *
    * @return string
    */
    public function getUrlStatusText()
    {
        if(is_null($this->getUrlStatus()))
        {
            return null;
        }
        switch ($this->getUrlStatus()) {
            case 0:
                return "null_result";
            case 1:
                return "redirection";
            case 2:
                return "homonyme";
            case 3:
                return "match";
            case 4:
                return "unsemantized";
            default:
                return "";
        }
    }
    

    /**
     * Set dbpediaUri
     *
     * @param string $dbpediaUri
     */
    public function setDbpediaUri($dbpediaUri)
    {
        $this->dbpediaUri = $dbpediaUri;
    }

    /**
     * Get dbpediaUri
     *
     * @return string
     */
    public function getDbpediaUri()
    {
        return $this->dbpediaUri;
    }

    /**
     * Set popularity
     *
     * @param integer $popularity
     */
    public function setPopularity($popularity)
    {
        $this->popularity = $popularity;
    }

    /**
     * Get popularity
     *
     * @return integer
     */
    public function getPopularity()
    {
        return $this->popularity;
    }

    /**
    * Set category
    *
    * @param object $category
    */
    public function setCategory($category)
    {
        $this->category = $category;
    }
    
    /**
     * Get category
     *
     * @return object
     */
    function getCategory()
    {
        return $this->category;
    }
    
    /**
     * (non-PHPdoc)
     * @see IRI\Bundle\WikiTagBundle\Model.TagInterface::getCreatedAt()
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
     * Get Documents
     *
     * @return ArrayCollection
     */
    public function getDocuments()
    {
        return $this->documents;
    }
    
    /**
     * Set category to Null
     *
     */
    function nullCategory()
    {
        return $this->setCategory(NULL);
    }
    
    
    /**
     * (non-PHPdoc)
     * @see IRI\Bundle\WikiTagBundle\Model.TagInterface::setWikipediaInfo()
     */
    function setWikipediaInfo($wikipedia_info)
    {
        $new_label = $wikipedia_info['new_label'];
        $status = $wikipedia_info['status'];
        $url = $wikipedia_info['wikipedia_url'];
        $pageid = $wikipedia_info['pageid'];
        $dbpedia_uri = $wikipedia_info["dbpedia_uri"];
        $wikipedia_revision_id = $wikipedia_info['revision_id'];
        $alternative_label = array_key_exists('alternative_label', $wikipedia_info) ? $wikipedia_info['alternative_label'] : null;
        $alternative_url = array_key_exists('wikipedia_alternative_url', $wikipedia_info) ? $wikipedia_info['wikipedia_alternative_url'] : null;
        $alternative_pageid = array_key_exists('alternative_pageid', $wikipedia_info) ? $wikipedia_info['alternative_pageid'] : null;
        
        # We save the datas
        if(! is_null($new_label))
        {
            $this->setLabel($new_label);
        }
        
        if(! is_null($status))
        {
            $this->setUrlStatus($status);
        }

        $this->setWikipediaUrl($url);
        $this->setWikipediaPageId($pageid);
        $this->setDbpediaUri($dbpedia_uri);
        $this->setAlternativeLabel($alternative_label);
        $this->setAlternativeWikipediaUrl($alternative_url);
        $this->setAlternativeWikipediaPageId($alternative_pageid);
        
    }
    
     
 }