<?php
/*
 * This file is part of the WikiTagBundle package.
 *
 * (c) IRI <http://www.iri.centrepompidou.fr/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace IRI\Bundle\WikiTagBundle\Command;


use IRI\Bundle\WikiTagBundle\Entity\DocumentTag;

use IRI\Bundle\WikiTagBundle\Entity\Tag;

use IRI\Bundle\WikiTagBundle\Entity\Category;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Mandango\Mondator\Definition\Definition;
use Mandango\Mondator\Definition\Property;
use Mandango\Mondator\Definition\Method;
use Mandango\Mondator\Dumper;

/**
 *
 * This class implements a command to load categories, tags, and document_tags fixtures from a yaml file.
 *
 * @author ymh
 * @author tc
 *
 */
class LoadFixturesCommand extends ProgressContainerAwareCommand
{
    protected function configure()
    {
        parent::configure();
        
        $this
            ->setName('wikitag:load-fixtures')
            ->setDescription('Load categories, tags, document_tags in JSON format. We suppose SyncDocuments command was executed.')
            ->addArgument('path', InputArgument::REQUIRED, 'path to the yaml file.')
            ->addOption("simulate","S",InputOption::VALUE_NONE, "Load and parse file, create objects but does not save them")
            ->addOption("categories","C",InputOption::VALUE_NONE, "Will save the categories and not the tags/doctags if options are not set (useful to avoid memory problems)")
            ->addOption("tags","T",InputOption::VALUE_NONE, "Will save the tags and not the categories/doctags if options are not set (useful to avoid memory problems)")
            ->addOption("doctags","D",InputOption::VALUE_NONE, "Will save the document_tags and not the categories/tags if options are not set (useful to avoid memory problems)")
            ->addOption("ibegin","B",InputOption::VALUE_OPTIONAL, "Iteration begin, to save part of the datas (useful to avoid memory problems)")
            ->addOption("iend","E",InputOption::VALUE_OPTIONAL, "Iteration end, to save part of the datas (useful to avoid memory problems)");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Default values
        $do_cat = FALSE;
        $do_tags = FALSE;
        $do_doctags = FALSE;
        // Get options
        $file_path = $input->getArgument('path');
        $simulate = $input->getOption('simulate');
        $do_cat = $input->getOption('categories');
        $do_tags = $input->getOption('tags');
        $do_doctags = $input->getOption('doctags');
        // If no options were set, we load the 3 objects
        if(!$do_cat && !$do_tags && !$do_doctags){
            $do_cat = TRUE;
            $do_tags = TRUE;
            $do_doctags = TRUE;
        }
        // Set iBegin and iEnd
        $iBegin = intval($input->getOption('ibegin'));
        $iEnd = intval($input->getOption('iend'));
        if($iEnd==0){
            $iEnd = INF;
        }
        $output->writeln("simulate = ".((!$simulate)?"false":"true").", file path = $file_path"
                        .", load cat = ".(($do_cat)?"true":"false").", load tags = ".(($do_tags)?"true":"false")
                        .", load doc_tags = ".(($do_doctags)?"true":"false").", ibegin = ".$iBegin.", iend = ".$iEnd.", min = ".min(10, $iEnd));
        
        
        // Parse json file and build the data array.
        $value = NULL;
        try {
            $content = file_get_contents($file_path);
            $value = json_decode($content, TRUE);
        } catch (Exception $e) {
            printf("Unable to parse the JSON string: %s", $e->getMessage());
        }
        
        // We build the 3 data arrays.
        $categories = array();
        $tags = array();
        $doctags = array();
        foreach ($value as $ar){
            if($ar["model"]=="hdabo.tagcategory"){
                array_push($categories,$ar);
            }
            else if($ar["model"]=="hdabo.tag"){
                array_push($tags,$ar);
            }
            else if($ar["model"]=="hdabo.taggedsheet"){
                array_push($doctags,$ar);
            }
        }
        $content = NULL;
        $value = NULL;
        
        // Get the entity manager
        $em = $this->getContainer()->get('doctrine')->getEntityManager();
        
        if($do_cat){
            // We save the categories, and force their id to be coherent with the tag's data.
            $nb = count($categories);
            if($nb>0){
                $output->writeln("Saving $nb categories...");
                $i = 0;
                foreach ($categories as $cat_ar){
                    $cat = new Category();
                    $cat->setId($cat_ar["pk"]);
                    $f = $cat_ar["fields"];
                    $cat->setLabel($f["label"]);
                    if(!$simulate){
                        $em->persist($cat);
                        // Temporary stop doctrine2 auto increment
                        $metadata = $em->getClassMetaData(get_class($cat));
                        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
                    }
                    $i++;
                    $this->showProgress($output, $i, $nb, "label : ".$f["label"], 50);
                }
                // We flush here because we need to call the category object when we save the tag's datas.
                if(!$simulate){
                    $em->flush();
                    $em->clear();
                    $output->writeln("Categories have been saved");
                }
            }
        }
        
        if($do_tags){
            $nb = count($tags);
            if($nb>0){
                $output->writeln("Saving $nb tags...");
                $cat_repo = $this->getContainer()->get('doctrine')->getRepository('WikiTagBundle:Category');
                $stop = min($iEnd+1,$nb);
                $output->writeln("Saving from $iBegin to ".($stop-1)." on $nb tags...");
                $nbToSave = $stop - $iBegin;
                for($i=$iBegin;$i<$stop;$i++){
                    $tag = new Tag();
                    $tag->setId($tags[$i]["pk"]);
                    $f = $tags[$i]["fields"];
                    $tag->setLabel($f["label"]);
                    $tag->setNormalizedLabel($f["normalized_label"]);
                    $tag->setOriginalLabel($f["original_label"]);
                    $tag->setAlternativeLabel($f["alternative_label"]);
                    $tag->setAlias($f["alias"]);
                    $tag->setWikipediaUrl($f["wikipedia_url"]);
                    $tag->setAlternativeWikipediaUrl($f["alternative_wikipedia_url"]);
                    $tag->setWikipediaPageId($f["wikipedia_pageid"]);
                    $tag->setAlternativeWikipediaPageId($f["alternative_wikipedia_pageid"]);
                    $tag->setUrlStatus($f["url_status"]); // smallint
                    $tag->setDbpediaUri($f["dbpedia_uri"]);
                    $tag->setPopularity($f["popularity"]);
                    $cat = $f["category"];
                    if($cat!=NULL){
                        $tag->setCategory($cat_repo->findOneById($cat)); // !! OBJECT CATEGORY
                    }
                    $tag->setCreatedAt(new \DateTime($f["created_at"], new \DateTimeZone('UTC')));
                    if(!$simulate){
                        $em->persist($tag);
                        // Temporary stop doctrine2 auto increment
                        $metadata = $em->getClassMetaData(get_class($tag));
                        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
                    }
                    // We save every 10 tags to avoid symfony's UnitOfWork crash
                    if((!$simulate) && ($i%10==0)){
                        // We flush here because we need to call the tag object when we save the document_tag's datas.
                        $em->flush();
                        $em->clear();
                        $em = $this->getContainer()->get('doctrine')->getEntityManager();
                    }
                    $memory = "mem: ".strval(memory_get_usage(true));
                    $this->showProgress($output, (($i-$iBegin)+1), $nbToSave, "$memory - label : ".$f["label"], 50);
                }
                // We flush one last time.
                if(!$simulate){
                    $em->flush();
                    $em->clear();
                    $em = $this->getContainer()->get('doctrine')->getEntityManager();
                    $output->writeln("Tags have been saved");
                }
            }
        }
        
        if($do_doctags){
            $categories = NULL;
            $tags = NULL;
            $nb = count($doctags); 
            if($nb>0){
                $tag_repo = $repository = $this->getContainer()->get('doctrine')->getRepository('WikiTagBundle:Tag');
                $doc_repo = $repository = $this->getContainer()->get('doctrine')->getRepository('WikiTagBundle:Document');
                $stop = min($iEnd,$nb);
                $output->writeln("Saving from $iBegin to $stop on $nb document_tags...");
                $nbToSave = $stop - $iBegin;
                for($i=$iBegin;$i<$stop;$i++){
                    $f = $doctags[$i]["fields"];
                    $dt = new DocumentTag();
                    $dt->setOriginalOrder($f["original_order"]);
                    $dt->setTagOrder($f["order"]);
                    $dt->setIndexNote($f["index_note"]);
                    $dt->setWikipediaRevisionId($f["wikipedia_revision_id"]);
                    $dt->setTag($tag_repo->findOneById($f["tag"]));
                    $doc = $doc_repo->findOneByExternalId($f["datasheet"]);
                    // We save if the document exists
                    if($doc!=NULL){
                        $dt->setDocument($doc);
                        $dt->setCreatedAt(new \DateTime($f["created_at"], new \DateTimeZone('UTC')));
                        if(!$simulate){
                            $em->persist($dt);
                        }
                    }
                    else{
                        $output->writeln("Document with external id ".$f["datasheet"]." was not found !");
                    }
                    // We save every 10 tags to avoid symfony's UnitOfWork crash
                    if((!$simulate) && ($i%10==0)){
                        // We flush here because we need to call the tag object when we save the document_tag's datas.
                        $em->flush();
                        $em->clear();
                        $em = $this->getContainer()->get('doctrine')->getEntityManager();
                    }
                    $memory = "mem: ".strval(memory_get_usage(true));
                    $this->showProgress($output, (($i-$iBegin)+1), $nbToSave, $memory, 50);
                }
                if(!$simulate){
                    $em->flush();
                    $em->clear();
                    $output->writeln("DocumentTags have been saved");
                }
            }
        }
        
        
        if($simulate)
        {
            $output->writeln("you simulated.");
        }
        else
        {
            //$output->writeln("file $file_path loaded.");
            //$output->writeln(var_dump($value));
            //$output->writeln("s = ".$s);
            $output->writeln("End of fixture loading");
        }
    }
}
