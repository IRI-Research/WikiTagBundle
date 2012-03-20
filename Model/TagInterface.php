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

interface TagInterface {
    
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
    
    /**
     * Set normalizedLabel
     *
     * @param string $normalizedLabel
     */
    function setNormalizedLabel($normalizedLabel);
    
    /**
     * Get normalizedLabel
     *
     * @return string
     */
    function getNormalizedLabel();
    
    /**
     * Set originalLabel
     *
     * @param string $originalLabel
     */
    function setOriginalLabel($originalLabel);
        
    /**
     * Get originalLabel
     *
     * @return string
     */
    function getOriginalLabel();
    
    /**
     * Set the alternative label
     *
     * @param string $alternativeLabel
     */
    function setAlternativeLabel($alternativeLabel);
    
    /**
     * Get the alternative label.
     *
     * @return string
     */
    function getAlternativeLabel();
    
    /**
     * Set alias
     *
     * @param string $alias
     */
    function setAlias($alias);
    
    /**
     * Get alias
     *
     * @return string
     */
    function getAlias();
    
    /**
     * Set wikipediaUrl
     *
     * @param string $wikipediaUrl
     */
    function setWikipediaUrl($wikipediaUrl);
    
    /**
     * Get wikipediaUrl
     *
     * @return string
     */
    function getWikipediaUrl();
    
    /**
    * Set alternativeWikipediaUrl
    *
    * @param string $alternativeWikipediaUrl
    */
    function setAlternativeWikipediaUrl($alternativeWikipediaUrl);
    
    /**
     * Get alternativeWikipediaUrl
     *
     * @return string
     */
    function getAlternativeWikipediaUrl();
    
    
    /**
     * Set wikipediaPageId
     *
     * @param bigint $wikipediaPageId
     */
    function setWikipediaPageId($wikipediaPageId);
    
    /**
     * Get wikipediaPageId
     *
     * @return bigint
     */
    function getWikipediaPageId();

    
   /**
    * Set aletrnativeWikipediaPageId
    *
    * @param bigint $alternativeWikipediaPageId
    */
    function setAlternativeWikipediaPageId($alternativeWikipediaPageId);
    
    /**
     * Get alternativeWikipediaPageId
     *
     * @return bigint
     */
    function getAlternativeWikipediaPageId();
    
    
    
    /**
     * Set urlStatus
     *
     * @param smallint $urlStatus
     */
    function setUrlStatus($urlStatus);
    
    /**
     * Get urlStatus
     *
     * @return smallint
     */
    function getUrlStatus();
    
    /**
    * Get UrlStatusText
    *
    * @return string
    */
    function getUrlStatusText();
    
    
    /**
     * Set dbpediaUri
     *
     * @param string $dbpediaUri
     */
    function setDbpediaUri($dbpediaUri);
    
    /**
     * Get dbpediaUri
     *
     * @return string
     */
    function getDbpediaUri();
    
    /**
     * Set popularity
     *
     * @param integer $popularity
     */
    function setPopularity($popularity);
    
    /**
     * Get popularity
     *
     * @return integer
     */
    function getPopularity();
    

    /**
    * Set category
    *
    * @param object $category
    */
    function setCategory($category);
    
    /**
     * Get category
     *
     * @return object
     */
    function getCategory();
    
    /**
     * return the utc time when this tag has been created
     */
    function getCreatedAt();
    
    /**
    * Set created at date
    *
    * @param DateTime $date
    */
    function setCreatedAt($date);
    
    /**
     * Get Documents
     *
     * @return ArrayCollection
     */
    function getDocuments();
    
    /**
     * Nullify category
     *
     */
    function nullCategory();
    
    
    /**
     * Set wikipedia info.
     * @param $wikipedia_info
     */
    function setWikipediaInfo($wikipedia_info);
        
    
}