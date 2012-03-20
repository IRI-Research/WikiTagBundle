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

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PurgeTagsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        parent::configure();
    
        $this
            ->setName('wikitag:purge-tags')
            ->setDescription('Purge tags')
            ->addOption("list","l",InputOption::VALUE_NONE, "List tags tp remove")
            ->addOption("force","f",InputOption::VALUE_NONE, "Force remove tags");
    }
    
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $force = $input->getOption('force');
        $list = $input->getOption('list');
            
        //get tags with no documents
        $doctrine = $this->getContainer()->get('doctrine');
        
        $qb = $doctrine->getEntityManager()->createQueryBuilder();
        $qb->select('t');
        $qb->from('WikiTagBundle:Tag','t');
        $qb->leftJoin('t.documents', 'dt', 'WITH', 't = dt.tag');
        $qb->addGroupBy('t.id');
        $qb->having($qb->expr()->eq($qb->expr()->count('dt.id'),':count'));
        $qb->setParameter("count", 0);


        $rsm = new ResultSetMapping();
        $rsm->addScalarResult("C","C");
        $count_query = $doctrine->getEntityManager()->createNativeQuery("SELECT COUNT(*) AS C FROM (".$qb->getQuery()->getSQL().") AS T", $rsm);
        $count_query->setParameter(1, 0);
        
        $count = $count_query->getSingleScalarResult();
        
        $output->writeln("<comment>$count tag(s) to delete.</comment>\n");
        
        if($list)
        {
            $query = $qb->getQuery();
            $result = $query->getResult();
           
            $i = 1;
            foreach($result as $tag)
            {
                $output->writeln(strval($i++)."- ".$tag->getLabel());
            }
            $output->writeln("");
        }
        else
        {
            if(! $force && $input->isInteractive())
            {
                $dialog = $this->getHelper('dialog');
                if (!$dialog->askConfirmation($output, '<question>Confirm deletion? (y/N) : </question>', false)) {
                    return;
                }
            }
            
            
            $id_delete = array();
            foreach($qb->getQuery()->getResult() as $tag)
            {
                $id_delete[] = $tag->getId();
            }
            
            $delete_qb = $doctrine->getEntityManager()->createQueryBuilder();
            $delete_qb->delete('WikiTagBundle:Tag','tag');
            $delete_qb->where($delete_qb->expr()->in('tag.id', $id_delete));
                        
            $result = $delete_qb->getQuery()->getResult();
            
            $output->writeln("Tag deleted : $result \n");
            
        }
        
    }
}