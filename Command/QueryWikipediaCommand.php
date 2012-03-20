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

use IRI\Bundle\WikiTagBundle\Utils\WikiTagUtils;

use IRI\Bundle\WikiTagBundle\Model\Tag;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class QueryWikipediaCommand extends ProgressContainerAwareCommand
{

    private function processTag($tag, $em)
    {
        $tag_label_normalized = WikiTagUtils::normalizeTag($tag->getLabel());
        $wp_response = WikiTagUtils::getWikipediaInfo($tag_label_normalized, null, $this->getContainer()->getParameter('wiki_tag.ignore_wikipedia_error'), $this->getContainer()->get('logger'));
        
        $tag->setWikipediaInfo($wp_response);
        
        // Save datas.
        $em->persist($tag);
        
    }
    
          
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('wikitag:query-wikipedia')
            ->setDescription('Query wikipedia for tags.')
            ->addOption("force","f",InputOption::VALUE_NONE, "Force processing tags, will ask no confirmation")
            ->addOption("all","a",InputOption::VALUE_NONE, "Search all tags")
            ->addOption("null","n",InputOption::VALUE_NONE, "Treat only non processed tags")
            ->addOption("redirection",null,InputOption::VALUE_NONE, "Treat redirections")
            ->addOption("site","S",InputOption::VALUE_OPTIONAL, "the url for the wikipedia site", "http://fr.wikipedia.org/w/api.php")
            ->addOption("limit","l",InputOption::VALUE_OPTIONAL, "number of tag to process", -1)
            ->addOption("start",null,InputOption::VALUE_OPTIONAL, "number of tag to ignore", 0);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        
        $force = $input->getOption('force');
        $all = $input->getOption('all');
        $redirection = $input->getOption('redirection');
        $site = $input->getOption('site');
        $limit = intval($input->getOption('limit'));
        $start = intval($input->getOption('start'));
        $null = $input->getOption('null');
        
        $doctrine = $this->getContainer()->get('doctrine');
        $qb = $doctrine->getEntityManager()->createQueryBuilder();
        
        
        $qb->from('WikiTagBundle:Tag','t');
        
        if(!$all)
        {
            if($redirection) {
                $qb->where($qb->expr()->andx($qb->expr()->eq("t.urlStatus",Tag::$TAG_URL_STATUS_DICT['redirection']), $qb->expr()->isNull("t.alternativeLabel")));
            }
            elseif($null) {
                $qb->where($qb->expr()->isNull("t.urlStatus"));
            }
            else {
                $qb->where($qb->expr()->orx($qb->expr()->isNull("t.urlStatus"), $qb->expr()->eq("t.urlStatus", Tag::$TAG_URL_STATUS_DICT['null_result'])));
            }
        }
        
        if($start > 0)
        {
            $qb->setFirstResult($start);
        }
        
        if($limit>=0)
        {
            $qb->setMaxResults($limit);
        }

        $qb_count = clone $qb;
        
        $qb_count->select("t.id");
        
        $count = count($qb_count->getQuery()->getScalarResult());
        $doctrine->getEntityManager()->clear();
        
        if($count === 0)
        {
            $output->writeln("No tag to process, exit.");
            return;
        }
        
        if(! $force && $input->isInteractive())
        {
            $dialog = $this->getHelper('dialog');
            if (!$dialog->askConfirmation($output, "<question>This command will process $count tag(s). Continue ? (y/N) : </question>", false)) {
                return;
            }
        }
        
        $qb->select("t");
        
        $done = 0;
        $iterable = $qb->getQuery()->iterate();
        $doctrine->getEntityManager()->beginTransaction();
        while (($row = $iterable->next()) !== false)
        {
            $done++;
            $tag = $row[0];
            
            $this->showProgress($output, $done, $count, $tag->getLabel(), 50);
            
            // process tag
            $this->processTag($tag, $doctrine->getEntityManager());
                        
            if($done%100 == 0)
            {
                $doctrine->getEntityManager()->flush();
                $doctrine->getEntityManager()->commit();
                $doctrine->getEntityManager()->clear();
                $doctrine->getEntityManager()->beginTransaction();
            }
        }
        $doctrine->getEntityManager()->flush();
        $doctrine->getEntityManager()->commit();
        
    }
}
