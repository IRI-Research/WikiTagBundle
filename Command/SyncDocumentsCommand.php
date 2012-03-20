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

use IRI\Bundle\WikiTagBundle\Event\WikiTagEvents;
use IRI\Bundle\WikiTagBundle\Event\DocumentTagEvent;
use Doctrine\ORM\Query;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 * This class implement a command to synchronize the wikita document table and index to the host application documents.
 * options :
 * - tags: Synchronize the tag string of the wikitag documents, otherwise, synchronize the document themselves (default not set).
 *     The tags string is the concatenation (with comma) of all tag's label of a wikita document. It allows the indexation of the tags.
 * - clear: Clear the wikitag documents. (default not set)
 * - all: if set, process all objects, if not process only those that need it. (default not set)
 *     - --tags not set : if --all is set, recreate a wikitag document for all host document (you may have to run the commant with the clear option first),
 *         otherwise create only the missing wikitag document.
 *     - --tags set: if --all is set, recalculate the tags string for all document, otherwise, only calculate the tag string wher it is null.
 *     - --clear: if --all is set, delete all wikitag documents, otherwise clear only those not linked to a host document.
 * - force: force document deletion (default not set)
 *
 * @author ymh
 *
 */
class SyncDocumentsCommand extends ProgressContainerAwareCommand
{
    protected function configure()
    {
        parent::configure();
        
        $this
            ->setName('wikitag:sync-doc')
            ->setDescription('Synchronize and index document class')
            ->addOption('force', 'f', InputOption::VALUE_NONE, "Force document deletion")
            ->addOption('tags', 't', InputOption::VALUE_NONE, "update tags")
            ->addOption('clear', 'c', InputOption::VALUE_NONE, "Clear documents")
            ->addOption('all', 'a', InputOption::VALUE_NONE, "clear all docs");
        
    }
    
    private function execute_tags(InputInterface $input, OutputInterface $output)
    {
        $doctrine = $this->getContainer()->get('doctrine');
        
        $docrep = $doctrine->getRepository('WikiTagBundle:Document');
        $all = $input->getOption('all');
        
        if($all)
        {
            $docquery = $doctrine->getEntityManager()->createQuery("SELECT doc from WikiTagBundle:Document doc");
            $doccountquery = $doctrine->getEntityManager()->createQuery("SELECT COUNT(doc.id) from WikiTagBundle:Document doc");
        }
        else
        {
            $docquery = $doctrine->getEntityManager()->createQuery("SELECT doc from WikiTagBundle:Document doc WHERE doc.tagsStr IS NULL");
            $doccountquery = $doctrine->getEntityManager()->createQuery("SELECT COUNT(doc.id) from WikiTagBundle:Document doc WHERE doc.tagsStr IS NULL");
        }
        
        
        $total = $doccountquery->getSingleScalarResult();
        $done = 0;
        $iterable = $docquery->iterate();
        $todetach = array();
        while (($row = $iterable->next()) !== false) {
            $done++;
            $memory = ((($done%10)==0)?" - mem: ".strval(memory_get_usage(true)):"");
            $doc = $row[0];
            $todetach[] = $doc;
        
            $this->showProgress($output, $done, $total, "id : ".$doc->getId()."%$memory", 50);
            $docrep->updateTagsStr($doc);
            //dispatch event
            $event_dispatcher = $this->getContainer()->get('event_dispatcher');
            $event = new DocumentTagEvent($doc);
            $event_dispatcher->dispatch(WikiTagEvents::onTagChanged, $event);
        
            if($done%10 == 0)
            {
                $doctrine->getEntityManager()->flush();
                foreach($todetach as $obj)
                {
                    $doctrine->getEntityManager()->detach($obj);
                }
                $todetach = array();
            }
        }
        $doctrine->getEntityManager()->flush();
        $doctrine->getEntityManager()->clear();
    }

    private function execute_clear(InputInterface $input, OutputInterface $output)
    {
        $class = $this->getContainer()->getParameter('wiki_tag.document_class');
        $doctrine = $this->getContainer()->get('doctrine');
        $all = $input->getOption('all');
        $force = $input->getOption('force');
        
        
        if($all)
        {
            // delete all documents
            $query_str = "DELETE WikiTagBundle:Document wtdoc";
            $count_query_str = "SELECT COUNT(wtdoc.id) FROM WikiTagBundle:Document wtdoc";
        }
        else
        {
            // delete only wikitag document that have no conterpart
            $query_str = "DELETE WikiTagBundle:Document wtdoc WHERE wtdoc.externalId IS NULL OR wtdoc.externalId NOT IN (SELECT doc FROM $class doc)";
            $count_query_str = "SELECT COUNT(wtdoc.id) FROM WikiTagBundle:Document wtdoc WHERE wtdoc.externalId IS NULL OR wtdoc.externalId NOT IN (SELECT doc FROM $class doc)";
        }
        
        $count_query = $doctrine->getEntityManager()->createQuery($count_query_str);
        $total = $count_query->getSingleScalarResult();
        
        if($total === 0)
        {
            $output->writeln("No wikitag document to delete. Exit.");
            return;
        }
        
        $output->writeln("$total wikitag document(s) to delete.");
        if(!$force && $input->isInteractive())
        {
            $dialog = $this->getHelper('dialog');
            if (!$dialog->askConfirmation($output, '<question>Confirm deletion? (y/N) : </question>', false)) {
                return;
            }
        }
               
        $req = $doctrine->getEntityManager()->createQuery($query_str);
        
        $nb_deleted = $req->getResult();
        
        $output->writeln("$nb_deleted wikitag document(s) deleted.");
        
        $doctrine->getEntityManager()->flush();
    
    }
    
    private function execute_docs(InputInterface $input, OutputInterface $output)
    {
        $class = $this->getContainer()->getParameter('wiki_tag.document_class');
        $all = $input->getOption('all');
        $doctrine = $this->getContainer()->get('doctrine');
        $docrep = $doctrine->getRepository('WikiTagBundle:Document');
        
        if($all)
        {
            $docquery = $doctrine->getEntityManager()->createQuery("SELECT doc FROM $class doc");
            $doccountquery = $doctrine->getEntityManager()->createQuery("SELECT count(doc.id) FROM $class doc");
        }
        else
        {
            $docquery = $doctrine->getEntityManager()->createQuery("SELECT doc FROM $class doc WHERE doc.id not in (SELECT wtdoc FROM WikiTagBundle:Document wtdoc)");
            $doccountquery = $doctrine->getEntityManager()->createQuery("SELECT count(doc.id) FROM $class doc WHERE doc.id not in (SELECT wtdoc FROM WikiTagBundle:Document wtdoc)");
        }
        $total = $doccountquery->getSingleScalarResult();
        $done = 0;
        $iterable = $docquery->iterate();
        while (($row = $iterable->next()) !== false) {
            $done++;
            $doc = $row[0];
            $memory = ((($done%10)==0)?" - mem: ".strval(memory_get_usage(true)):"");
            $this->showProgress($output, $done, $total, "id : ".$doc->getId()."%$memory", 50);
            $docrep->writeDocument($doc, $this->getContainer()->getParameter('wiki_tag.document_id_column'), $this->getContainer()->getParameter('wiki_tag.fields'));
            if($done%10 == 0)
            {
                $doctrine->getEntityManager()->flush();
                $doctrine->getEntityManager()->clear();
            }
        }
        $doctrine->getEntityManager()->flush();
        $doctrine->getEntityManager()->clear();
        
    }
    
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $clear = $input->getOption('clear');
        $tags = $input->getOption('tags');
        
     
        $class = $this->getContainer()->getParameter('wiki_tag.document_class');
        $doctrine = $this->getContainer()->get('doctrine');
        $rep = $doctrine->getRepository($class);
        
        if(is_null($rep))
        {
            $output->writeln("$class does not have a repository : exiting.");
            return ;
        }
        
        if($tags)
        {
            $this->execute_tags($input, $output);
        }
        elseif($clear)
        {
            $this->execute_clear($input, $output);
        }
        else
        {
            $this->execute_docs($input, $output);
        }

    }
}