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

class GenerateDocumentClassCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        parent::configure();
        
        $this
            ->setName('wikitag:generate-document-class')
            ->setDescription('Generate the document class')
            ->addArgument('path', InputArgument::OPTIONAL, 'The generation path')
            ->addOption("simulate","S",InputOption::VALUE_NONE, "Simulate generation");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $input->getArgument('path');
        if(is_null($path) || strlen($path) == 0)
        {
            $path = realpath($this->getContainer()->get('kernel')->getRootDir()."/../src");
        }
        
        $schema_utils = $this->getContainer()->get("wikitag.shema_utils");
        
        $classCode = $schema_utils->generateDocumentClass();
        
        if($input->getOption('simulate'))
        {
            $output->writeln($classCode);
        }
        else
        {
            $file = "$path/IRI/Bundle/WikiTagBundle/Entity/Document.php";
            $output->writeln("Creating IRI\\Bundle\\WikiTagBundle\\Entity\\Document in $file");
            
            if(!file_exists(dirname($file)) && !mkdir(dirname($file),0777,true))
            {
                $output->writeln("Impossible to create folder exitiing.");
                die;
            }
            file_put_contents($file, $classCode);
        }

    }
}