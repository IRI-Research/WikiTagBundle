<?php
/*
 * This file is part of the WikiTagBundle package.
 *
 * (c) IRI <http://www.iri.centrepompidou.fr/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace IRI\Bundle\WikiTagBundle\DataFixures\ORM;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Company\BaseBundle\Entity\Document;
use Company\BaseBundle\Entity\Category;

class LoadDocumentData implements FixtureInterface, ContainerAwareInterface
{
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
    
    public function load($manager) {
        
        # create new categories
        $cat_def_list = array('cat1' => null, 'cat2' => null, 'cat3'=> null);
    
        foreach(array_keys($cat_def_list) as $cat_name) {
            $newcat = new Category();
            $newcat->setName($cat_name);
            $manager->persist($newcat);
            $cat_def_list[$cat_name] = $newcat;
        }
        
        # create new document
        $doc_def_list = array(
            array('title'=>'Title 0', 'description'=>'Description 0', 'tags' => null, 'categories' => array()),
            array('title'=>'Title 1', 'description'=>'Description 1', 'tags' => array('tag1', 'tag2', 'tag3', 'tag4'), 'categories' => array_values($cat_def_list)),
            array('title'=>'Title 2', 'description'=>'Description 2', 'tags' => array('tag2', 'tag3', 'tag4'), 'categories' => array($cat_def_list['cat1'], $cat_def_list['cat2'])),
            array('title'=>'Title 3', 'description'=>'Description 3', 'tags' => array('tag3', 'tag4'), 'categories' => array($cat_def_list['cat1'])),
            array('title'=>'Title 4', 'description'=>'Description 4', 'tags' => array(), 'categories' => array()),
            array('title'=>'Title 5', 'description'=>'Description 5', 'tags' => array('tag2', 'tag3', 'tag4'), 'categories' => array($cat_def_list['cat1'], $cat_def_list['cat2'])),
            array('title'=>'Title 10', 'description'=>'Description 10', 'tags' => array('tag1', 'tag2', 'tag3', 'tag4'), 'categories' => array()),
            array('title'=>'Title 11', 'description'=>'Description 11', 'tags' => array('tag4'), 'categories' => array($cat_def_list['cat2'])),
            array('title'=>'Title 20', 'description'=>'Description 20', 'tags' => array('newtag1'), 'categories' => array($cat_def_list['cat3'])),
            array('title'=>'Title 21', 'description'=>'Description 21', 'tags' => array('newtag2'), 'categories' => array($cat_def_list['cat3'])),
            array('title'=>'Title 22', 'description'=>'Description 22', 'tags' => array('another'), 'categories' => array($cat_def_list['cat3'])),
            array('title'=>'Title 23', 'description'=>'Description 23', 'tags' => array('hello world'), 'categories' => array($cat_def_list['cat3'])),
            array('title'=>'Lorem ipsum dolor sit amet', 'description'=>'Morbi adipiscing luctus ullamcorper. Nam suscipit, turpis vel faucibus fringilla, sem leo fermentum nunc, et mattis leo urna sed tellus. Suspendisse consectetur turpis cursus ipsum ullamcorper gravida. Nullam arcu nisi, condimentum id condimentum non, lobortis nec lorem. Donec commodo, ligula sit amet posuere fermentum, urna elit faucibus nunc, et faucibus lorem erat quis urna. Vestibulum a quam eros. Suspendisse non felis a metus faucibus porta. Morbi adipiscing augue vel justo euismod non pulvinar sem posuere. Duis sit amet ipsum et quam cursus commodo eu a purus. Pellentesque gravida tempus libero, eu consectetur nisl posuere id.', 'tags' => array('foobar'), 'categories' => array($cat_def_list['cat3'])),
            array('title'=>'Lorem ipsum dolor sit amet', 'description'=>'Lorem ipsum ullamcorper', 'tags' => array('barfoo'), 'categories' => array($cat_def_list['cat3'])),
            array('title'=>'caveat', 'description'=>'emptor', 'tags' => array('hello world'), 'categories' => array($cat_def_list['cat3'])),
            array('title'=>'emptor', 'description'=>'caveat', 'tags' => array('bonjour monde'), 'categories' => array($cat_def_list['cat3'])),
        );
        
        
        $newdocs = array();
        
        foreach ($doc_def_list as $doc_def) {
            
            $newdoc = new Document();
            $newdoc->setTitle($doc_def['title']);
            $newdoc->setDescription($doc_def['description']);

            foreach($doc_def['categories'] as $cat) {
                $newdoc->getCategories()->add($cat);
            }
            
            $manager->persist($newdoc);
            
            $newdocs[] = array($newdoc, $doc_def['tags']);
            
        }
        
        $manager->flush();
        
        foreach ($newdocs as $newdoc_array) {
            $newdoc = $newdoc_array[0];
            $tags = $newdoc_array[1];
            $this->container->get('wiki_tag.document')->addTags($newdoc->getId(), $tags);
            $manager->flush();
        }
        
        $manager->flush();
    }
 
}