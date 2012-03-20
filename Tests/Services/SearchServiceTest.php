<?php
/*
 * This file is part of the WikiTagBundle package.
 *
 * (c) IRI <http://www.iri.centrepompidou.fr/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace IRI\Bundle\WikiTagBundle\Tests\Services;

require_once(__DIR__ . "/../../../../../../../app/AppKernel.php");

class SearchServiceTest extends \PHPUnit_Framework_TestCase
{

    protected $_container;
    protected $_application;
    protected $_kernel;
    
    
    public function __construct()
    {
        $this->_kernel = new \AppKernel("test", true);
        $this->_kernel->boot();
        $this->_container = $this->_kernel->getContainer();
        parent::__construct();
    }

    protected function runConsole($command, Array $options = array())
    {
    
        $options["-e"] = "test";
        $options["-q"] = null;
        $options = array_merge($options, array('command' => $command));
        return $this->_application->run(new \Symfony\Component\Console\Input\ArrayInput($options));
    }
    
    public function setUp()
    {
        $this->_application = new \Symfony\Bundle\FrameworkBundle\Console\Application($this->_kernel);
        $this->_application->setAutoExit(false);
        $this->runConsole("doctrine:schema:drop", array("--force" => true));
        $this->runConsole("wikitag:schema:create");
        $this->runConsole("cache:warmup");
        $this->runConsole("doctrine:fixtures:load"/*, array("--fixtures" => __DIR__ . "/../../../../../../../src/Company/BaseBundle/DataFixtures")*/);
    }
    
    protected function get($service)
    {
        return $this->_container->get($service);
    }
    
    
    public function testTagCloud()
    {
        $search_service = $this->get("wiki_tag.search");
        
        $result = $search_service->getTagCloud(4);
        
        $this->assertNotNull($result, "tag cloud should not be null");
        $this->assertLessThanOrEqual(4, count($result));
        $this->assertEquals(4, count($result));
        
        // 0->tag4,6 ; 0->tag3,5 ; 0->tag2,4 ; 0->tag1,2
        $expected = array('Tag1'=>2,'Tag2'=>4, 'Tag3'=>5, 'Tag4'=>6);
        for ($i = 0; $i < 4; $i++) {
            $tagname = "Tag".(4-$i);
            $this->assertEquals($tagname,$result[$i]['label']);
            $this->assertEquals($expected[$tagname],$result[$i]['nb_docs']);
        }
        
    }


    public function testTagCloudLimit()
    {
        $search_service = $this->get("wiki_tag.search");
    
        $result = $search_service->getTagCloud(2);
    
        $this->assertNotNull($result, "tag cloud should not be null");
        $this->assertEquals(2, count($result));
    
        $expected = array('Tag3'=>5, 'Tag4'=>6);
        for ($i = 0; $i < 2; $i++) {
            $tagname = "Tag".(4-$i);
            $this->assertEquals($tagname,$result[$i]['label']);
            $this->assertEquals($expected[$tagname],$result[$i]['nb_docs']);
        }
    
    }
    
    public function testCompletion()
    {
        $search_service = $this->get("wiki_tag.search");
    
        $result = $search_service->completion("tag");
        $completion_result = array(
        	'Tag1' => 2,
        	'Tag2' => 4,
        	'Tag3' => 5,
            'Tag4' => 6
        );
    
        $this->assertNotNull($result, "completion should not be null");
        $this->assertEquals(4, count($result));
        
        foreach ($result as $tagname) {
            $this->assertEquals(0,strpos($tagname['label'],"Tag"));
            $this->assertArrayHasKey($tagname['label'], $completion_result);
            $this->assertEquals($completion_result[$tagname['label']], $tagname['nb_docs']);
        }
    }
    
    public function testSearch() {
        
        $search_service = $this->get("wiki_tag.search");
        $result = $search_service->search("Suspendisse");
        
        $this->assertNotNull($result, "search result should not be null");
        $this->assertEquals(1, count($result));
        foreach (array("_score","host_doc_id", "wikitag_doc_id", "wikitag_doc", "title", "description", "categories") as $key) {
            $this->assertArrayHasKey($key,$result[0]);
            $this->assertNotNull($result[0][$key]);
        }
        $this->assertTrue($result[0]['_score']>0);
        $this->assertEquals("Lorem ipsum dolor sit amet", $result[0]['title']);
        $this->assertTrue(is_a($result[0]['wikitag_doc'], "\IRI\Bundle\WikiTagBundle\Model\DocumentInterface"));
        $this->assertEquals("Lorem ipsum dolor sit amet", $result[0]['wikitag_doc']->getTitle());
        
    }

    public function testSearchCondition() {
    
        $search_service = $this->get("wiki_tag.search");
        $result = $search_service->search("ullamcorper");
    
        $this->assertNotNull($result, "search result should not be null");
        $this->assertEquals(2, count($result));
        
        $result = $search_service->search("ullamcorper", array("tagsStr"=>array("operator"=>"like","value"=>"barfoo")));
        
        foreach (array("_score","host_doc_id", "wikitag_doc_id", "wikitag_doc", "title", "description", "categories") as $key) {
            $this->assertArrayHasKey($key,$result[0]);
            $this->assertNotNull($result[0][$key]);
        }
        $this->assertTrue($result[0]['_score']>0);
        $this->assertEquals("Lorem ipsum dolor sit amet", $result[0]['title']);
        $this->assertTrue(is_a($result[0]['wikitag_doc'], "\IRI\Bundle\WikiTagBundle\Model\DocumentInterface"));
        $this->assertEquals("Lorem ipsum dolor sit amet", $result[0]['wikitag_doc']->getTitle());
        $this->assertEquals("Lorem ipsum ullamcorper", $result[0]['description']);
    
    }
    
    public function testSearchWeight() {
        $search_service = $this->get("wiki_tag.search");
        
        $result = $search_service->search(array('title'=>array('weight'=>0.9, 'value'=>'caveat'), 'description'=>array('weight'=>0.1, 'value'=>'caveat')));
        
        $this->assertNotNull($result, "search result should not be null");
        $this->assertEquals(2, count($result));
        $this->assertTrue($result[0]['_score']>$result[1]['_score']);
        
        $this->assertEquals('caveat', $result[0]['title']);
        $this->assertEquals('emptor', $result[0]['description']);

        $result = $search_service->search(array('title'=>array('weight'=>0.1, 'value'=>'caveat'), 'description'=>array('weight'=>0.9, 'value'=>'caveat')));
        
        $this->assertNotNull($result, "search result should not be null");
        $this->assertEquals(2, count($result));
        $this->assertTrue($result[0]['_score']>$result[1]['_score']);
        
        $this->assertEquals('caveat', $result[0]['description']);
        $this->assertEquals('emptor', $result[0]['title']);
        
        
    }
    
    
    
}

