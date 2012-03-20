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

class DocumentServiceTest extends \PHPUnit_Framework_TestCase
{

    protected $_container;
    protected $_doctrine;
    protected $_application;
    protected $_kernel;
    
    public function __construct()
    {
        $this->_kernel = new \AppKernel("test", true);
        $this->_kernel->boot();
        $this->_container = $this->_kernel->getContainer();
        parent::__construct();
    }
    
    protected function get($service)
    {
        return $this->_container->get($service);
    }
    
    protected function getDoctrine()
    {
        if(is_null($this->_doctrine))
        {
            $this->_doctrine = $this->get('doctrine');
        }
        return $this->_doctrine;
    }
        
    
    public function testInsert()
    {
        $newdoc = new \Company\BaseBundle\Entity\Document();
        $newdoc->setTitle("Title 5");
        $newdoc->setDescription("Description 5");
        
        $this->getDoctrine()->getEntityManager()->persist($newdoc);
        
        $this->getDoctrine()->getEntityManager()->flush();
        
        
        $doc = $this->getDoctrine()->getRepository("WikiTagBundle:Document")->findOneByExternalId($newdoc->getId());
        
        $this->assertEquals("Title 5", $doc->getTitle());
        $this->assertEquals("Description 5", $doc->getDescription());
        
        
    }
    
    public function testTagOrder()
    {
        $doc = $this->getDoctrine()->getRepository("WikiTagBundle:Document")->findOneByTitle("Title 1");
        
        $this->assertNotNull($doc);
        $i = 1;
        foreach($doc->getTags() as $dt) {
            $this->assertEquals($i++, $dt->getTagOrder());
        }
        
    }

    public function testUpdate()
    {
        $hostdoc = $this->getDoctrine()->getRepository("CompanyBaseBundle:Document")->findOneByTitle("Title 3");
        
        $this->assertEquals("Title 3", $hostdoc->getTitle());
        $this->assertEquals("Description 3", $hostdoc->getDescription());
        
        $hostdoc->setTitle("Title 3 modified");
        $hostdoc->setDescription("Description 3 modified");
        
        $this->getDoctrine()->getEntityManager()->persist($hostdoc);
        $this->getDoctrine()->getEntityManager()->flush();
        
        $doc = $this->getDoctrine()->getRepository("WikiTagBundle:Document")->findOneByExternalId($hostdoc->getId());
        
        $this->assertEquals("Title 3 modified", $doc->getTitle());
        $this->assertEquals("Description 3 modified", $doc->getDescription());

    }
    
    public function testUpdateCategory()
    {
        $hostdoc = $this->getDoctrine()->getRepository("CompanyBaseBundle:Document")->findOneByTitle("Title 5");
        $cat = $this->getDoctrine()->getRepository("CompanyBaseBundle:category")->findOneByName("cat3");
    
        $this->assertEquals("Title 5", $hostdoc->getTitle());
        $this->assertEquals("Description 5", $hostdoc->getDescription());
        $this->assertEquals("cat1,cat2", $hostdoc->getCategoriesStr());
        
        
        $hostdoc->setTitle("Title 5 modified");
        $hostdoc->setDescription("Description 5 modified");
        $hostdoc->getCategories()->add($cat);
    
        $this->getDoctrine()->getEntityManager()->persist($hostdoc);
        $this->getDoctrine()->getEntityManager()->flush();
    
        $doc = $this->getDoctrine()->getRepository("WikiTagBundle:Document")->findOneByExternalId($hostdoc->getId());
    
        $this->assertEquals("Title 5 modified", $doc->getTitle());
        $this->assertEquals("Description 5 modified", $doc->getDescription());
        
        
        $this->assertEquals("cat1,cat2,cat3", $doc->getCategories());
        
    }
    
    
    public function testDelete()
    {
        $hostdoc = $this->getDoctrine()->getRepository("CompanyBaseBundle:Document")->findOneByTitle("Title 10");
        $hostdocid = $hostdoc->getId();
        $this->getDoctrine()->getEntityManager()->remove($hostdoc);
        $this->getDoctrine()->getEntityManager()->flush();
        
        $doc = $this->getDoctrine()->getRepository("WikiTagBundle:Document")->findOneByExternalId($hostdocid);
        
        $this->assertTrue(is_null($doc));
        
    }
    
    
    public function testGetTagLabels()
    {
        $doc_service = $this->get("wiki_tag.document");
        $doc1 = $this->getDoctrine()->getRepository("CompanyBaseBundle:Document")->findOneByTitle("Title 1");
        
        $tags = $doc_service->getTagLabels($doc1->getId());
        
        $this->assertEquals(4,count($tags));
        
        $this->assertEquals(array("Tag1","Tag2","Tag3","Tag4"),$tags);
        
    }
    
    public function testCopyTags()
    {
    
        $doc_service = $this->get("wiki_tag.document");
        
        $doc1 = $this->getDoctrine()->getRepository("CompanyBaseBundle:Document")->findOneByTitle("Title 1");

        $this->assertEquals(4,count($doc_service->getTagLabels($doc1->getId())));
                
        $doc2 = $this->getDoctrine()->getRepository("CompanyBaseBundle:Document")->findOneByTitle("Title 4");
        
        $this->assertEquals(0,count($doc_service->getTagLabels($doc2->getId())));
        
        $doc_service->copyTags($doc1->getId(), $doc2->getId());

        $this->assertEquals(4,count($doc_service->getTagLabels($doc2->getId())));
        $this->assertEquals(array("Tag1","Tag2","Tag3","Tag4"),$doc_service->getTagLabels($doc2->getId()));
        
    }
    
    
    public function testCopyTagsEmpty()
    {
        $doc_service = $this->get("wiki_tag.document");
        
        $doc0 = $this->getDoctrine()->getRepository("CompanyBaseBundle:Document")->findOneByTitle("Title 0");
        
        $this->assertEquals(0,count($doc_service->getTagLabels($doc0->getId())));
        
        $doc1 = $this->getDoctrine()->getRepository("CompanyBaseBundle:Document")->findOneByTitle("Title 1");
        
        $this->assertEquals(4,count($doc_service->getTagLabels($doc1->getId())));
        
        $doc_service->copyTags($doc0->getId(), $doc1->getId());
        
        $this->assertEquals(0,count($doc_service->getTagLabels($doc1->getId())));
        
    }
    
    public function testCopyNoPersist()
    {
        $doc_service = $this->get("wiki_tag.document");
        $doc1 = $this->getDoctrine()->getRepository("CompanyBaseBundle:Document")->findOneByTitle("Title 1");
        
        $newdoc = new \Company\BaseBundle\Entity\Document();
        $newdoc->setTitle('a title');
        $newdoc->setDescription('a description');

        $this->getDoctrine()->getEntityManager()->persist($newdoc);
        $this->getDoctrine()->getEntityManager()->flush();
        
        $doc_service->copyTags($doc1->getId(), $newdoc->getId());
        
        $this->getDoctrine()->getEntityManager()->flush();
        
        $this->assertEquals(4,count($doc_service->getTagLabels($newdoc->getId())));
        
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
    
    protected function runConsole($command, Array $options = array())
    {
        
        $options["-e"] = "test";
        $options["-q"] = null;
        $options = array_merge($options, array('command' => $command));
        return $this->_application->run(new \Symfony\Component\Console\Input\ArrayInput($options));
    }
    
}

