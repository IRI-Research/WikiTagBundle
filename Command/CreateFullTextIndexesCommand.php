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
use Mandango\Mondator\Definition\Definition;
use Mandango\Mondator\Definition\Property;
use Mandango\Mondator\Definition\Method;
use Mandango\Mondator\Dumper;

class CreateFullTextIndexesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        parent::configure();
        
        $this
            ->setName('wikitag:create-fulltext-indexes')
            ->setDescription('Generate the full text indexes for the document table')
            ->addArgument('path', InputArgument::OPTIONAL, 'The generation path')
            ->addOption("simulate","S",InputOption::VALUE_NONE, "Simulate generation");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file = $input->getArgument('path');
        $simulate = $input->getOption('simulate');
        if(is_null($file) || strlen($file) == 0)
        {
            $simulate = true;
        }
        

        $schema_utils = $this->getContainer()->get("wikitag.shema_utils");
        
        $sql_code = implode(";".PHP_EOL, $schema_utils->createFullTextIndexes());
        
        if($simulate)
        {
            $output->writeln($sql_code);
        }
        else
        {
            $output->writeln("Creating Indexes in $file");
            
            if(!file_exists(dirname($file)) && !mkdir(dirname($file),0777,true))
            {
                $output->writeln("Impossible to create folder exitiing.");
                die;
            }
            file_put_contents($file, $sql_code);
        }

    }
}