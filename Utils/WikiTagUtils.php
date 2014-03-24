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

use IRI\Bundle\WikiTagBundle\Entity\Tag;

class WikiTagUtils
{   
    /**
     * Cleans the tag label
     */
    public static function normalizeTag($tag_label)
    {
        if(strlen($tag_label)==0){
            return $tag_label;
        }
        $tag_label = trim($tag_label);//tag.strip()
        $tag_label = str_replace("_", " ", $tag_label);//tag.replace("_", " ")
        $tag_label = preg_replace('/\s+/u', ' ', $tag_label);//" ".join(tag.split())
        $tag_label = ucfirst($tag_label);//tag[0].upper() + tag[1:]
        return $tag_label;
    }
    
    /**
     * Query wikipedia with a normalized label or a pageid
     * return an array with the form
     * array(
     *      'new_label'=>$new_label,
     *   	'alternative_label'=>$alternative_label,
     *   	'status'=>$status,
     *   	'wikipedia_url'=>$url,
     *      'wikipedia_alternative_url'=>$alternative_url,
     *   	'pageid'=>$pageid,
     *   	'alternative_pageid'=>$alternative_pageid,
     *   	'dbpedia_uri'=>$dbpedia_uri,
     *   	'revision_id'=> ,
     *   	'response'=> the original wikipedia json response
     *   	)
     *
     * @param string $tag_label_normalized
     * @param bigint $page_id
     * @return array
     */
    public static function getWikipediaInfo($tag_label_normalized, $page_id=null, $ignore_wikipedia_error=false, $logger = null)
    {

        $params = array('action'=>'query', 'prop'=>'info|categories|langlinks', 'inprop'=>'url', 'lllimit'=>'500', 'cllimit'=>'500', 'rvprop'=>'ids', 'format'=>'json');
        if($tag_label_normalized!=null){
            $params['titles'] = urlencode($tag_label_normalized);
        }
        else if($page_id!=null){
            $params['pageids'] = $page_id;
        }
        else{
            return WikiTagUtils::returnNullResult(null);
        }
        
        try {
            $ar = WikiTagUtils::requestWikipedia($params);
        }
        catch(\Exception $e) {
            if($ignore_wikipedia_error) {
                if(!is_null($logger)) {
                    $logger->err("Error when querying wikipedia : ".$e->getMessage()." with trace : ".$e->getTraceAsString());
                }
                return WikiTagUtils::returnNullResult(null);
            }
            else {
                throw $e;
            }
        }

        $res = $ar[0];
        $original_response = $res;
        $pages = $ar[1];
        // If there 0 or more than 1 result, the query has failed
        if(count($pages)>1 || count($pages)==0){
            return WikiTagUtils::returnNullResult($res);
        }
        // get first result
        $page = reset($pages);
        // Unknow entry ?
        if(array_key_exists('missing', $page) || array_key_exists('invalid', $page)){
            return WikiTagUtils::returnNullResult($res);
        }
        // The entry exists, we get the datas.
        $url = array_key_exists('fullurl', $page) ? $page['fullurl'] : null;
        $pageid = array_key_exists('pageid', $page) ? $page['pageid'] : null;
        $new_label = array_key_exists('title', $page) ? $page['title'] : null;
        // We test the status (redirect first because a redirect has no categories key)
        if(array_key_exists('redirect', $page)){
            //return " REDIRECT";
            $status = Tag::$TAG_URL_STATUS_DICT["redirection"];
        }
        else if(WikiTagUtils::isHomonymy($page)){
            //return " HOMONYMY";
            $status = Tag::$TAG_URL_STATUS_DICT["homonyme"];
        }
        else{
            //return " MATCH";
            $status = Tag::$TAG_URL_STATUS_DICT["match"];
        }
        // In redirection, we have to get more datas by adding redirects=true to the params
        $alternative_label = null;
        $alternative_url = null;
        $alternative_pageid = null;
        if($status==Tag::$TAG_URL_STATUS_DICT["redirection"])
        {
            $params['redirects'] = "true";
            try {
                $ar = WikiTagUtils::requestWikipedia($params);
            }
            catch(\Exception $e) {
                if($ignore_wikipedia_error) {
                    if(!is_null($logger)) {
                        $logger->error("Error when querying wikipedia for redirection : ".$e->getMessage()." with trace : ".$e->getTraceAsString());
                    }
                    return WikiTagUtils::returnNullResult(null);
                }
                else {
                    throw $e;
                }
            }
            
            $res = $ar[0];
            $pages = $ar[1];
            // we know that we have at least one answer
            if(count($pages)>1 || count($pages)==0){
                return WikiTagUtils::returnNullResult($res);
            }
            // get first result
            $page = reset($pages);
            $alternative_label = array_key_exists('title', $page) ? $page['title'] : null;
            $alternative_url = array_key_exists('fullurl', $page) ? $page['fullurl'] : null;
            $alternative_pageid = array_key_exists('pageid', $page) ? $page['pageid'] : null;
        }
        
        $revision_id = $page['lastrevid'];
        
        // Get the dbpedia uri by requesting dbpedia with sparql
        $dbpedia_uri = WikiTagUtils::getDbpediaUri($new_label);
        
        $wp_response = array(
            'new_label'=>$new_label,
        	'alternative_label'=>$alternative_label,
        	'status'=>$status,
        	'wikipedia_url'=>$url,
            'wikipedia_alternative_url'=>$alternative_url,
        	'pageid'=>$pageid,
        	'alternative_pageid'=>$alternative_pageid,
        	'dbpedia_uri'=>$dbpedia_uri,
        	'revision_id'=>$revision_id,
        	'response'=>$original_response);
        
        return $wp_response;
    }
    
    /**
     * Generic curl request
     *
     * @param string $url
     * @return object (json decoded)
     */
    private static function curlRequest($url, $throw_error=true)
    {
    	$ch = curl_init();
    	curl_setopt($ch, CURLOPT_URL, $url);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	// default values
    	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:26.0) Gecko/20100101 Firefox/26.0');
    	curl_setopt($ch, CURLOPT_TIMEOUT_MS, 5000);
    	// Set options if they are set in the config.yml file, typically for proxy configuration.
    	// Thanks to the configuration file, it will execute commands like "curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);" or "curl_setopt($ch, CURLOPT_PROXY, "xxx.yyy.zzz:PORT");"
    	$curl_options = $GLOBALS["kernel"]->getContainer()->getParameter("wiki_tag.curl_options");
    	foreach ($curl_options as $key => $value) {
    		if(strtoupper($value)=='TRUE'){
    			$value = TRUE;
    		}
    		else if (strtoupper($value)=='FALSE'){
    			$value = FALSE;
    		}
    		else if (defined($value)){
    			$value = constant($value);
    		}
    		curl_setopt($ch, constant($key), $value);
    	}
    	// end of treatment
    	$res = curl_exec($ch);
    	$curl_errno = curl_errno($ch);
    	$curl_error = curl_error($ch);
    	curl_close($ch);
    
    	if ($curl_errno > 0 && $throw_error) {
    		throw new \Exception("$url\n request failed. cURLError #$curl_errno: $curl_error\n", $curl_errno, null);
    	}
    	
    	return $res;
	}
    

    /**
     * build and do the request to Wikipedia.
     *
     * @param array $params
     * @return array
     */
    private static function requestWikipedia($params)
    {
        $params_str = '';
        foreach ($params as $key => $value) {
            if ($params_str==''){
                $params_str = $key.'='.$value;
            }
            else{
                $params_str .= '&'.$key.'='.$value;
            }
        }
        
        $url = $GLOBALS["kernel"]->getContainer()->getParameter("wiki_tag.url_templates")["wikipedia_api"].'?'.$params_str;
        
        $res = WikiTagUtils::curlRequest($url);
        $val = json_decode($res, true);
        $pages = $val["query"]["pages"];
        return array($res, $pages);
    }
    
    /**
     * Returns tag with a null result, usually used after a failed request on Wikipedia
     */
    private static function returnNullResult($response)
    {
        return array('new_label'=>null, 'status'=>Tag::$TAG_URL_STATUS_DICT['null_result'], 'wikipedia_url'=>null, 'pageid'=>null, 'dbpedia_uri'=>null, 'revision_id'=>null, 'response'=>$response);
    }
    
    /**
     * Returns tag with a null result, usually used after a failed request on Wikipedia
     */
    private static function isHomonymy($page)
    {
        //$s = "";
        foreach ($page["categories"] as $ar) {
            //$s .= ", b : ".$ar." - title = ".$ar["title"].", strpos = ".strpos($ar["title"], 'Catégorie:Homonymie');
            // Strict test because false can be seen as "O".
            if(strpos($ar["title"], 'Catégorie:Homonymie')!==false || strpos($ar["title"], 'Category:Disambiguation')!==false){
                //$s .= "TRUE";
                return true;
            }
        }
        return false;
    }
    
    /**
     * Builds DbPedia URI
     */
    public static function getDbpediaUri($label, $params=[], $throw_error=true, $req_param="label")
    {
    	// Get lang from url
    	$dbp_url = $GLOBALS["kernel"]->getContainer()->getParameter("wiki_tag.url_templates")["dbpedia_sparql"];
    	$lang = substr($dbp_url, 7, 2);
    	// filter with regexp to avoid results with "category:LABEL" or other "abc:LABEL"
    	$query = 'select distinct * where { ?s rdfs:label "'.$label.'"@'.$lang.' . FILTER (regex(?s, "^http\\\\://[^:]+$")) }';
    	if($req_param=="pageid"){
    		$query = 'select distinct * where { ?s dbpedia-owl:wikiPageID '.$label.' }';
    	}
    	elseif ($req_param=="wikiurl"){
    		$query = 'select distinct * where { ?s foaf:isPrimaryTopicOf <'.$label.'> }';
    	}
    	
    	$params = [
    		"query" => $query,
    		"format" => 'application/json',
    	];
    	
    	$params_str = '';
    	foreach ($params as $key => $value) {
    		if ($params_str==''){
    			$params_str = $key.'='.urlencode($value);
    		}
    		else{
    			$params_str .= '&'.$key.'='.urlencode($value);
    		}
    	}
    	
    	$url = $GLOBALS["kernel"]->getContainer()->getParameter("wiki_tag.url_templates")["dbpedia_sparql"].'?'.$params_str;
    	
    	$res = WikiTagUtils::curlRequest($url, $throw_error);
    	$val = json_decode($res, true);
    	$uri = "";
    	if($val){
	    	if(array_key_exists("results", $val)){
	    		if(array_key_exists("bindings", $val["results"])){
	    			$len = count($val["results"]["bindings"]);
	    			if($len > 0){
	    				$uri = $val["results"]["bindings"][0]["s"]["value"];
	    				if($len>1){
	    					// If there are several results, we test the "url label" to see if it matches the label.
	    					// Why ? Because, for example "1000" gets "Category:1000" and "1000" as result.
	    					// We keep this code to be safe but the regexp in the sparql request normally avoids this problem.
	    					for($i=0;$i<$len;$i++){
	    						$res_uri = $val["results"]["bindings"][$i]["s"]["value"];
	    						$url_label = substr( $res_uri, strrpos( $res_uri, '/' )+1 );
	    						if(str_replace(" ", "_", $label) == $url_label){
	    							$uri = $res_uri;
	    						}
	    					}
	    				}
	    			}
	    		}
	    	}
    	}
    	return $uri;
    }
    
    /**
     * URLencode label for wikipedia
     */
    private static function urlize_for_wikipedia($label){
        return urlencode(str_replace(" ", "_", $label));
    }
}
