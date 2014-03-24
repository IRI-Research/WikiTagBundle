<?php
/*
 * This file is part of the WikiTagBundle package.
 *
 * (c) IRI <http://www.iri.centrepompidou.fr/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace IRI\Bundle\WikiTagBundle\Controller;

use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\DBAL\DriverManager;

use IRI\Bundle\WikiTagBundle\Entity\DocumentTag;
use IRI\Bundle\WikiTagBundle\Entity\Tag;
use IRI\Bundle\WikiTagBundle\Utils\WikiTagUtils;
use IRI\Bundle\WikiTagBundle\Services\WikiTagServiceException;
use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;


class WikiTagController extends Controller
{
    private static $SEARCH_STAR_CHARACTER = "*";
    
    /**
     * Fake index action
     */
    public function indexAction()
    {
        return new Response('<html><body>Nothing to see here.</body></html>');
    }

    /**
     * Renders the little html to add the css
     */
    public function addCssAction()
    {
        return $this->render('WikiTagBundle:WikiTag:css.html.twig');
    }

    /**
     * Renders the little html to add the javascript
     *
     * @param unknown_type $tags_list
     * @return \Symfony\Bundle\FrameworkBundle\Controller\Response
     */
    public function addJavascriptAction($tags_list=false, $profile_name=null, $read_only=false)
    {
        $cats = $this->getDoctrine()->getRepository('WikiTagBundle:Category')->findOrderedCategories();
        // $cats is {"Label":"Créateur"},{"Label":"Datation"},...
        $nbCats = count($cats);
        $ar = array('' => '');
        for($i=0;$i<$nbCats;$i++) {
            $temp = array($cats[$i]["label"] => $cats[$i]["label"]);
            $ar = array_merge($ar, $temp);
        }
        // ... so we create is json like {"":""},{"Créateur":"Créateur"},{"Datation":"Datation"},...
        $categories = json_encode($ar);
        // Management of profiles for the list of displayed columns and reorder tag button
        $profile_array = $this->container->getParameter("wiki_tag.document_list_profile");
        $columns_array = null;
        if($profile_array!=null && $profile_name!=null && $profile_name!=""){
            $columns_array = $profile_array[$profile_name];
        }
        
        return $this->render('WikiTagBundle:WikiTag:javascript.html.twig', 
        		array('wikipedia_api_url' => $this->container->getParameter("wiki_tag.url_templates")["wikipedia_api"], 
        				'categories' => $categories, 
        				'tags_list' => $tags_list, 
        				'columns' => $columns_array, 
        				'read_only' => $read_only));
    }

    /**
     * Renders the little html to add the javascript for context search
     */
    public function addJavascriptForContextSearchAction($context_name)
    {
        // WARNING : PREREQUISITE : the request to add a tag needs the external document id,
        // which is gotten by the jQuery call $('#wikitag_document_id').val() in the page.
        // So the page holding this context search MUST have a input value with this id.
        // We add the reactive selectors
        $reac_sel_array = $this->container->getParameter("wiki_tag.reactive_selectors");
        $reactive_selectors = null;
        if(array_key_exists($context_name, $reac_sel_array)){
            if($reac_sel_array[$context_name][0]=='document'){
                $reactive_selectors = 'document';
            }
            else{
                $reactive_selectors = '"'.join('","',$reac_sel_array[$context_name]).'"';
            }
        }
        return $this->render('WikiTagBundle:WikiTag:javascriptForContextSearch.html.twig', array('reactive_selectors' => $reactive_selectors));
    }

    /**
     * Display a list of ordered tag for a document
     * @param integer $id_doc
     */
    public function documentTagsAction($id_doc, $profile_name="")
    {
        // Management of profiles for the list of displayed columns and reorder tag button
        $profile_array = $this->container->getParameter("wiki_tag.document_list_profile");
        $columns_array = null;
        if($profile_array!=null && $profile_name!=null && $profile_name!=""){
            $columns_array = $profile_array[$profile_name];
        }
        
        $ordered_tags = $this->getDoctrine()->getRepository('WikiTagBundle:DocumentTag')->findOrderedTagsForDoc($id_doc);
        //$ordered_tags = null;
        return $this->render('WikiTagBundle:WikiTag:documentTags.html.twig', 
        		array('ordered_tags' => $ordered_tags, 
        				'doc_id' => $id_doc, 
        				'columns' => $columns_array, 
        				'profile_name' => $profile_name,
        				'wikipedia_opensearch_url' => $this->container->getParameter("wiki_tag.url_templates")["wikipedia_opensearch"]));
    }

    /**
     *
     * The action called when a tag is moved in a document tag list.
     *
     * @return \Symfony\Bundle\FrameworkBundle\Controller\Response
     */
    public function tagUpDownAction()
    {

        $req = $this->getRequest()->request;
        $id_doc = $req->get('wikitag_document_id');
        // post vars new_order and old_order indicate the position (from 1) of the tag in the list.
        // NB : it is different from the DocumentTag.order in the database.
        $new_order = intval($req->get('new_order')) - 1;
        $old_order = intval($req->get('old_order')) - 1;
        // First we get the DocumentTags
        $em = $this->getDoctrine()->getEntityManager();
        $ordered_tags = $this->getDoctrine()->getRepository('WikiTagBundle:DocumentTag')->findOrderedTagsForDoc($id_doc);
        // We change the moved DocumentTag's order
        $new_dt_order = $ordered_tags[$new_order]->getTagOrder();
        $moved_dt = $ordered_tags[$old_order];
        $moved_dt->setTagOrder($new_dt_order);
        // We move the TaggedSheets's order
        if($new_order > $old_order){
            // And we decrease the other ones
            for ($i=($old_order+1); $i <= ($new_order); $i++){
                $dt = $ordered_tags[$i];
                $dt->setTagOrder($dt->getTagOrder() - 1);
            }
        }
        else{
            // And we increase the other ones
            for ($i=$new_order; $i <= ($old_order-1); $i++){
                $dt = $ordered_tags[$i];
                $dt->setTagOrder($dt->getTagOrder() + 1);
            }
        }
        // Save datas.
        $em->flush();
        
        return $this->renderDocTags($id_doc, $req->get('wikitag_document_profile'));
    }

    /**
     * Action to remove a tag from a document tag list
     * @return \Symfony\Bundle\FrameworkBundle\Controller\Response
     */
    public function removeTagFromListAction()
    {
        $id_doc = $this->getRequest()->request->get('wikitag_document_id');
        $id_tag = $this->getRequest()->request->get('tag_id');
        // We get the DocumentTag meant to be deleted, and remove it.
        $em = $this->getDoctrine()->getEntityManager();
        
        $dt = $this->getDoctrine()->getRepository('WikiTagBundle:DocumentTag')->findOneByDocumentExternalId($id_doc, array('tag' => $id_tag));
        $em->remove($dt);
        $em->flush();

        return $this->renderDocTags($id_doc, $this->getRequest()->request->get('wikitag_document_profile'));
    }

    /**
     * Modify the tag in the context of a tag list for one document
     *
     */
    public function modifyDocumentTagAction()
    {
        $id_doc = $this->getRequest()->request->get('wikitag_document_id');
        $tag_label = $this->getRequest()->request->get('value');
        $id_moved_tag = $this->getRequest()->request->get('id');
        $moved_tag = $this->getDoctrine()->getRepository('WikiTagBundle:Tag')->findOneBy(array('id' => $id_moved_tag));
        if($tag_label!=$moved_tag->getLabel()){
            // We get the DocumentTags
            $em = $this->getDoctrine()->getEntityManager();
            
            $tags = $this->getDoctrine()->getRepository('WikiTagBundle:DocumentTag')->findByDocumentExternalId($id_doc);
            $found = false;
            foreach ($tags as $dt)
            {
                if($dt->getTag()->getLabel()===$tag_label)
                {
                    $found = true;
                    break;
                }
            }
            // If the label was found, we sent a bad request
            if($found==true){
                return new Response(json_encode(array('error' => 'duplicate_tag', 'message' => sprintf("Le tag %s existe déjà pour cette fiche.", $tag_label))),400);
            }
            // We create the new tag or get the already existing tag. $tag, $revision_id, $created
            try {
                $ar = $this->getDoctrine()->getRepository('WikiTagBundle:Tag')->getOrCreateTag($tag_label, $this->container->getParameter('wiki_tag.ignore_wikipedia_error'), $this->container->get('logger'));
            }
            catch (\Exception $e){
                return new Response(json_encode(array('error' => 'wikipedia_request_failed', 'message' => $e->getMessage())),400);
            }
            $tag = $ar[0];
            $revision_id = $ar[1];
            $created = $ar[2];
            
            // We get the DocumentTag and change its tag
            
            $dt = $this->getDoctrine()->getRepository('WikiTagBundle:DocumentTag')->findOneByDocumentExternalId($id_doc, array('tag' => $id_moved_tag));
            $dt->setTag($tag);
            $dt->setWikipediaRevisionId($revision_id);
            
            $score_res = $this->container->get('wiki_tag.search')->search($tag_label, array("externalId"=>$id_doc));
            
            if(count($score_res)>0)
            {
                $score = floatval($score_res[0]['score']);
            }
            else
            {
                $score = 0.0;
            }
            $dt->setIndexNote($score);
            
            // We set ManualOrder = true for the current document
            $doc = $this->getDoctrine()->getRepository('WikiTagBundle:Document')->findOneBy(array('externalId' => $id_doc));
            $doc->setManualOrder(true);
            // We save the datas
            $em->flush();
        }
        // We render the document's tags
        return $this->renderDocTags($id_doc, $this->getRequest()->request->get('wikitag_document_profile'));

    }

    /**
     * The action called to reorder the the tags of a document. The tags are reordered according to the indexation score of the tag label on the document.
     * The fields taken into account for calculating the score are defined in the wikitag configuration.
     */
    public function reorderTagDocumentAction()
    {
        $id_doc = $this->getRequest()->request->get('wikitag_document_id');
        $res = $this->getDoctrine()->getRepository('WikiTagBundle:Document');
        
        $doc = $res->findOneByExternalId($id_doc);
        $doc->setManualOrder(false);
        $this->getDoctrine()->getEntityManager()->persist($doc);
        
        $doc_service = $this->get('wiki_tag.document')->reorderTags($doc);
        
        $this->getDoctrine()->getEntityManager()->flush();

        return $this->renderDocTags($id_doc, $this->getRequest()->request->get('wikitag_document_profile'));
    }

    /**
     * The action called to add a new tag (especially from the completion box)
     */
    public function addTagAction()
    {
        $id_doc = $this->getRequest()->request->get('wikitag_document_id');
        $tag_label = $this->getRequest()->request->get('value');
        
        
        try
        {
            $this->get('wiki_tag.document')->addTags($id_doc, $tag_label);
        }
        catch (WikiTagServiceException $e)
        {
            return new Response(json_encode(array('error' => $e->getErrorCode(), 'message' => $e->getMessage())),$e->getCode());
        }
        $this->getDoctrine()->getEntityManager()->flush();

        
        return $this->renderDocTags($id_doc, $this->getRequest()->request->get('wikitag_document_profile'));
    }


    /**
     * Action to remove the wikipedia link form a tag. This action create a copy of the original tag with all the link to wikipedia set to null.
     *
     * @return \Symfony\Bundle\FrameworkBundle\Controller\Response
     */
    public function removeWpLinkAction()
    {
        $id_doc = $this->getRequest()->request->get('wikitag_document_id');
        $id_tag = $this->getRequest()->request->get('tag_id');
        $tag = $this->getDoctrine()->getRepository('WikiTagBundle:Tag')->find($id_tag);
 
        $em = $this->getDoctrine()->getEntityManager();
        $query = $em->createQuery("SELECT t FROM WikiTagBundle:Tag t WHERE t.label = :label AND (t.urlStatus = :status_null OR t.urlStatus = :status_unsemantized)");
        $query->setParameters(array("label"=>$tag->getLabel(),"status_null"=>Tag::$TAG_URL_STATUS_DICT['null_result'],"status_unsemantized"=>Tag::$TAG_URL_STATUS_DICT['unsemantized']));
        $un_tag = null;
        $un_tags = $query->getResult();
        if(count($un_tags)>0) {
            $un_tag = $un_tags[0];
        }
        $un_tag_created = false;
        if(!$un_tag){
            // Create another tag almost identical, without the W info
            $un_tag = new Tag();
            $un_tag->setLabel($tag->getLabel());
            $un_tag->setOriginalLabel($tag->getOriginalLabel());
            $un_tag->setWikipediaUrl(null);
            $un_tag->setWikipediaPageId(null);
            $un_tag->setAlternativeWikipediaUrl(null);
            $un_tag->setAlternativeWikipediaPageId(null);
            $un_tag->setAlternativeLabel(null);
            $un_tag->setDbpediaUri(null);
            $un_tag->setCategory($tag->getCategory());
            $un_tag->setAlias($tag->getAlias());
            $un_tag->setPopularity($tag->getPopularity());
            $un_tag->setUrlStatus(Tag::$TAG_URL_STATUS_DICT['unsemantized']);
            $un_tag_created = true;
            $em->persist($un_tag);
        }
        elseif($un_tag->getUrlStatus()==Tag::$TAG_URL_STATUS_DICT['null_result'])
        {
            $un_tag->setUrlStatus(Tag::$TAG_URL_STATUS_DICT['unsemantized']);
            $un_tag_created = true;
            $em->persist($un_tag);
        }
        
        
        
        if($id_doc && $id_doc!=""){
            // We associate the unsemantized tag to the DocumentTag and save datas
            $dt = $this->getDoctrine()->getRepository('WikiTagBundle:DocumentTag')->findOneByDocumentExternalId($id_doc, array('tag' => $id_tag));
            $dt->setTag($un_tag);
            $em->flush();
            return $this->renderDocTags($id_doc, $this->getRequest()->request->get('wikitag_document_profile'));
        }
        else{
            // Here we are in the context of tag list.
            if($un_tag_created==true){
                $em->flush();
                return $this->renderAllTags();
            }
            else{
                // The unsemantized version of the tag already exist, so we send an error.
                return new Response(json_encode(array('error' => 'duplicate_tag', 'message' => sprintf("La version désémantisée du tag %s (%s) existe déjà.", $un_tag->getLabel(), $un_tag->getOriginalLabel()))),400);
            }
        }
    }


    /**
     * Action to update a tag category.
     * @return \Symfony\Bundle\FrameworkBundle\Controller\Response
     */
    public function updateTagCategoryAction()
    {
        $id_doc = $this->getRequest()->request->get('wikitag_document_id');
        $id_tag = $this->getRequest()->request->get('id');
        $cat_label = $this->getRequest()->request->get('value');
        // We get the Tag and update its category.
        $em = $this->getDoctrine()->getEntityManager();
        $tag = $this->getDoctrine()->getRepository('WikiTagBundle:Tag')->find($id_tag);
        if($cat_label==''){
            $cat = null;
            $tag->nullCategory();
        }
        else{
            $cat = $this->getDoctrine()->getRepository('WikiTagBundle:Category')->findOneBy(array('label' => $cat_label));
            $tag->setCategory($cat);
        }
        $em->flush();

        if($id_doc && $id_doc!=""){
            return $this->renderDocTags($id_doc, $this->getRequest()->request->get('wikitag_document_profile'));
        }
        else{
            return $this->renderAllTags();
        }
    }


    /**
     *
     * Generic render partial template
     * @param unknown_type $id_doc
     */
    public function renderDocTags($id_doc, $profile_name="")
    {
        // Management of profiles for the list of displayed columns and reorder tag button
        $profile_array = $this->container->getParameter("wiki_tag.document_list_profile");
        $columns_array = null;
        if($profile_array!=null && $profile_name!=null && $profile_name!=""){
            $columns_array = $profile_array[$profile_name];
        }
        $ordered_tags = $this->getDoctrine()->getRepository('WikiTagBundle:DocumentTag')->findOrderedTagsForDoc($id_doc);
        return $this->render('WikiTagBundle:WikiTag:tagTable.html.twig', 
        		array('ordered_tags' => $ordered_tags, 
        				'doc_id' => $id_doc, 
        				'columns' => $columns_array, 
        				'profile_name' => $profile_name,
        				'wikipedia_opensearch_url' => $this->container->getParameter("wiki_tag.url_templates")["wikipedia_opensearch"]));
    }


    /**
     * Action to update the tag alias.
	 *
     * @return \Symfony\Bundle\FrameworkBundle\Controller\Response
     */
    public function updateTagAliasAction()
    {
        $id_tag = $this->getRequest()->request->get('id');
        $alias = $this->getRequest()->request->get('value');
        // We get the Tag and update its category.
        $em = $this->getDoctrine()->getEntityManager();
        $tag = $this->getDoctrine()->getRepository('WikiTagBundle:Tag')->find($id_tag);
        $tag->setAlias($alias);
        $em->flush();
        
        $id_doc = $this->getRequest()->request->get('wikitag_document_id');
        if($id_doc && $id_doc!=""){
            // In case we changed the alias from the document view
            return $this->renderDocTags($id_doc, $this->getRequest()->request->get('wikitag_document_profile'));
        }
        else{
            // In case we changed the alias from the tag list.
            return $this->renderAllTags();
        }
    }
    
    /**
     * List all tags, with pagination and search.
     *
     * @return \Symfony\Bundle\FrameworkBundle\Controller\Response
     */
    public function allTagsAction()
    {
        // $this->getRequest()->query->get('foo') does not work "because" we are a second controller. So we have to use $_GET.
        // Searched string
        $searched = NULL;
        if(array_key_exists('searched', $_GET)){
            $searched = $_GET['searched'];
        }
        // Number of tags per page
        $nb_by_page = 50;
        if(array_key_exists('nb_by_page', $_GET)){
            $nb_by_page = intval($_GET['nb_by_page']);
        }
        // Current page number
        $num_page = 1;
        if(array_key_exists('num_page', $_GET)){
            $num_page = intval($_GET['num_page']);
        }
        // Sorting criteria
        $sort = NULL;
        if(array_key_exists('sort', $_GET)){
            $sort = $_GET['sort'];
        }
        
        // We get the needed datas in an array($tags, $num_page, $nb_by_page, $searched, $sort, $reverse_sort, $pagerfanta);
        $ar = $this->getAllTags($num_page, $nb_by_page, $sort, $searched);
        //return new Response($ar);
        $tags = $ar[0];
        $num_page = $ar[1];
        $nb_by_page = $ar[2];
        $searched = $ar[3];
        $sort = $ar[4];
        $reverse_sort = $ar[5];
        $pagerfanta = $ar[6];
        
        // We get the needed vars : number totals of tags, previous and next page number
        $last_page = $pagerfanta->getNbPages();
        $nb_total = $pagerfanta->getNbResults();
        $prev_page = 1;
        if($pagerfanta->hasPreviousPage()){
            $prev_page = $pagerfanta->getPreviousPage();
        }
        $next_page = $last_page;
        if($pagerfanta->hasNextPage()){
            $next_page = $pagerfanta->getNextPage();
        }
        // We calculate start_index and end_index (number of tags in the whole list)
        $start_index = 1 + (($num_page - 1) * $nb_by_page);
        $end_index = min($nb_total, $start_index + $nb_by_page - 1);
        
        // We build the list of tags's first letters to make quick search.
        $conn = $this->getDoctrine()->getEntityManager()->getConnection();
        $sql = "SELECT UPPER(SUBSTRING(normalized_label,1,1)) as fl FROM wikitag_tag GROUP BY fl ORDER BY fl";
        $letters = $conn->query($sql)->fetchAll();
        $search_def = array();
        foreach ($letters as $l){
            $search_def[$l[0]] = $l[0].WikiTagController::$SEARCH_STAR_CHARACTER;
        }
        
        return $this->render('WikiTagBundle:WikiTag:TagList.html.twig',
            array('tags' => $tags, 
            		'searched' => $searched, 
            		'search_def' => $search_def, 
            		'nb_by_page' => $nb_by_page, 
            		'sort' => $sort,
            		'start_index' => $start_index, 
            		'end_index' => $end_index, 
            		'nb_total' => $nb_total, 
            		'num_page' => $num_page, 
            		'last_page' => $last_page,
        			'prev_page' => $prev_page, 
            		'next_page' => $next_page, 
            		'reverse_sort' => $reverse_sort, 
            		'route_for_documents_by_tag' => $this->container->getParameter("wiki_tag.route_for_documents_by_tag"),
            		'wikipedia_opensearch_url' => $this->container->getParameter("wiki_tag.url_templates")["wikipedia_opensearch"]));
    }

    /**
     * Modify the tag in the context of all tags list.
     */
    public function modifyTagAction()
    {
        $tag_label = $this->getRequest()->request->get('value');
        $id_moved_tag = $this->getRequest()->request->get('id');
        $moved_tag = $this->getDoctrine()->getRepository('WikiTagBundle:Tag')->findOneBy(array('id' => $id_moved_tag));
        // We update the tag label and its wikipedia info with the new label.
        try {
            $this->updateTagWithNewLabel($moved_tag, $tag_label);
        }
        catch (\Exception $e){
            return new Response(json_encode(array('error' => 'wikipedia_request_failed', 'message' => $e->getMessage())),500);
        }
        // We render the tag list.
        return $this->renderAllTags();
    }
        
        
    /**
     * Resemantize the tag with its original label. Kind of undo if we changed the tag's label.
     *
     */
    public function resetWpInfoAction()
    {
        $id_moved_tag = $this->getRequest()->request->get('tag_id');
        $moved_tag = $this->getDoctrine()->getRepository('WikiTagBundle:Tag')->findOneBy(array('id' => $id_moved_tag));
        // We update the tag label and its wikipedia info with the original label.
        try {
            $this->updateTagWithNewLabel($moved_tag, $moved_tag->getOriginalLabel());
        }
        catch (\Exception $e){
            return new Response(json_encode(array('error' => 'wikipedia_request_failed', 'message' => $e->getMessage())),500);
        }
        
        // We render the tag list.
        return $this->renderAllTags();
    }
    
    /**
     * Redo a Wikipedia search
     * @return \Symfony\Component\HttpFoundation\Response|\Symfony\Bundle\FrameworkBundle\Controller\Response
     */
    public function relaunchWpSearchAction()
    {
        $id_tag = $this->getRequest()->request->get('tag_id');
        $tag = $this->getDoctrine()->getRepository('WikiTagBundle:Tag')->findOneBy(array('id' => $id_tag));
        // We update the tag label and its wikipedia info with the original label.
        try {
            $this->updateTagWithNewLabel($tag, $tag->getLabel());
        }
        catch (\Exception $e){
            return new Response(json_encode(array('error' => 'wikipedia_request_failed', 'message' => $e->getMessage())),500);
        }
    
        // We render the tag list.
        return $this->renderAllTags();
    }
    


    /**
     * Generic render partial template for tag list
     */
    private function updateTagWithNewLabel($tag, $label)
    {
        if($tag!=null && $label!=null){
            // We get the Wikipedia informations for the sent label
            $tag_label_normalized = WikiTagUtils::normalizeTag($label);
            $wp_response = WikiTagUtils::getWikipediaInfo($tag_label_normalized, null, $this->container->getParameter('wiki_tag.ignore_wikipedia_error'), $this->container->get('logger'));
            $tag->setWikipediaInfo($wp_response);
            // Save datas.
            $em = $this->getDoctrine()->getEntityManager();
            $em->persist($tag);
            $em->flush();
        }
    }


    /**
     * Generic render partial template for tag list
     */
    public function renderAllTags($num_page=null, $nb_by_page=null, $sort=null, $searched=null)
    {
        if(is_null($num_page)) {
            $num_page = $this->getRequest()->request->get('num_page');
        }
        if(is_null($nb_by_page)) {
            $nb_by_page = $this->getRequest()->request->get('nb_by_page');
        }
        if(is_null($sort)) {
            $sort = $this->getRequest()->request->get('sort');
        }
        if(is_null($searched)) {
            $searched = $this->getRequest()->request->get('searched');
        }
        //We get the needed datas in an array($tags, $num_page, $nb_by_page, $searched, $sort, $reverse_sort, $pagerfanta);
        $ar = $this->getAllTags($num_page, $nb_by_page, $sort, $searched);
        $tags = $ar[0];
        $num_page = $ar[1];
        $nb_by_page = $ar[2];
        $searched = $ar[3];
        $sort = $ar[4];
        $reverse_sort = $ar[5];
        
        return $this->render('WikiTagBundle:WikiTag:TagListTable.html.twig',
            array('tags' => $tags, 
            		'searched' => $searched, 
            		'nb_by_page' => $nb_by_page, 
            		'sort' => $sort, 
            		'num_page' => $num_page,
        			'reverse_sort' => $reverse_sort, 
            		'route_for_documents_by_tag' => $this->container->getParameter("wiki_tag.route_for_documents_by_tag"),
            		'wikipedia_opensearch_url' => $this->container->getParameter("wiki_tag.url_templates")["wikipedia_opensearch"]));
    }

    /**
     * Generic to get all tags with the context (pagination number, nb by page, searched string, sort)
     */
    private function getAllTags($num_page=NULL, $nb_by_page=NULL, $sort=NULL, $searched=NULL)
    {
        // We get/set all the parameters for the search and pagination.
        // Searched string
        if($searched==NULL){
            $searched = "";
        }
        $searched = urldecode($searched);
        // Number of tags per page
        if($nb_by_page==NULL){
            $nb_by_page = 50;
        }
        // Current page number
        if($num_page==NULL){
            $num_page = 1;
        }
        // We build the query.
        $qb = $this->getDoctrine()->getEntityManager()->createQueryBuilder();
        $qb->select('t', 'COUNT( dt.id ) AS nb_docs');
        $qb->from('WikiTagBundle:Tag','t');
        $qb->leftJoin('t.documents', 'dt', 'WITH', 't = dt.tag');
        $qb->addGroupBy('t.id');
        
        // We add the search string if necessary
        if($searched!=""){
            // We replace "*" by "%", and doctrine wants ' to be ''.
            $qb->where($qb->expr()->orx($qb->expr()->like('t.normalizedLabel', "'".str_replace("'", "''", str_replace("*", "%", str_replace("+", " ", $searched)))."'")));
        }
        //return $qb->getDql();
        
        // We add the sorting criteria
        if($sort==NULL){
            $sort = "popd"; // sort by descendent popularity by default.
            $reverse_sort = "popa";
        }
        //$sort_query = "nb_docs DESC t.popularity DESC t.normalizedLabel ASC t.label ASC";
        switch($sort){
            case "popd":
                $qb->addOrderBy('t.popularity','DESC');
                $qb->addOrderBy('nb_docs','DESC');
                $qb->addOrderBy('t.normalizedLabel','ASC');
                $qb->addOrderBy('t.label','ASC');
                $reverse_sort = "popa";
                break;
            case "popa":
                $qb->addOrderBy('t.popularity','ASC');
                $qb->addOrderBy('nb_docs','DESC');
                $qb->addOrderBy('t.normalizedLabel','ASC');
                $qb->addOrderBy('t.label','ASC');
                $reverse_sort = "popd";
                break;
            case "labd":
                $qb->addOrderBy('t.normalizedLabel','DESC');
                $qb->addOrderBy('t.label','DESC');
                $reverse_sort = "laba";
                break;
            case "laba":
                $qb->addOrderBy('t.normalizedLabel','ASC');
                $qb->addOrderBy('t.label','ASC');
                $reverse_sort = "labd";
                break;
            case "nbd":
                $qb->addOrderBy('nb_docs','DESC');
                $qb->addOrderBy('t.popularity','DESC');
                $qb->addOrderBy('t.normalizedLabel','ASC');
                $qb->addOrderBy('t.label','ASC');
                $reverse_sort = "nba";
                break;
            case "nba":
                $qb->addOrderBy('nb_docs','ASC');
                $qb->addOrderBy('t.popularity','DESC');
                $qb->addOrderBy('t.normalizedLabel','ASC');
                $qb->addOrderBy('t.label','ASC');
                $reverse_sort = "nbd";
                break;
        }
        
        // We paginate
        $adapter = new DoctrineORMAdapter($qb);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($nb_by_page); // 10 by default
        $pagerfanta->setCurrentPage($num_page); // 1 by default
        $nb_total = $pagerfanta->getNbResults();
        $tags = $pagerfanta->getCurrentPageResults();
        $pagerfanta->haveToPaginate(); // whether the number of results if higher than the max per page
        
        return array($tags, $num_page, $nb_by_page, $searched, $sort, $reverse_sort, $pagerfanta);
    }


}