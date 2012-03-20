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

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ReorderTagsCommand extends ContainerAwareCommand
{
    
    protected function configure()
    {
        parent::configure();
    
        $this
        ->setName('wikitag:reorder-tags')
        ->setDescription('Reorder tags')
        ->addOption("force","f",InputOption::VALUE_NONE, "Force all reorder");
    }
    
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $configuration = $doctrine->getConnection()->getConfiguration();
        $configuration->setSQLLogger(null);
        
        $force = $input->getOption('force');
        
        if($force)
        {
            $query = $doctrine->getEntityManager()->createQuery("SELECT doc from WikiTagBundle:Document doc");
            $querycount = $doctrine->getEntityManager()->createQuery("SELECT count(doc) from WikiTagBundle:Document doc");
        }
        else
        {
            $query = $doctrine->getEntityManager()->createQuery("SELECT doc from WikiTagBundle:Document doc WHERE doc.manualOrder = FALSE");
            $querycount = $doctrine->getEntityManager()->createQuery("SELECT count(doc) from WikiTagBundle:Document doc WHERE doc.manualOrder = FALSE");
        }
        
        $total = $querycount->getSingleScalarResult();
        $search_service = $this->getContainer()->get('wiki_tag.search');
        
        $done = 0;
        $iterable = $query->iterate();
        $doctrine->getEntityManager()->beginTransaction();
        while (($row = $iterable->next()) !== false)
        {
            $done++;
            $doc = $row[0];
            
            $output->writeln("Process doc id ".$doc->getId()." $done/$total ".strval(intval(floatval($done)/floatval($total)*100.0))."%");
            
            $doc->setManualOrder(false);
            $doctrine->getEntityManager()->persist($doc);
            
            $search_service->reorderTagsForDocument($doc);
            
            
            if($done%100 == 0)
            {
                $doctrine->getEntityManager()->flush();
                $doctrine->getEntityManager()->commit();
                $doctrine->getEntityManager()->clear();
                $output->writeln("memory : ".strval(memory_get_usage(true)));
                $doctrine->getEntityManager()->beginTransaction();
            }
            
        }
        
        $doctrine->getEntityManager()->commit();
        
    }

}
