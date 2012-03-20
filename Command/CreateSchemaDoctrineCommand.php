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

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\CreateCommand;
use Symfony\Bundle\DoctrineBundle\Command\Proxy\DoctrineCommandHelper;
use IRI\Bundle\WikiTagBundle\Utils\FilteredSchemaTool;

/**
 * Command to execute the SQL needed to generate the database schema for
 * a given entity manager.
 *
 * This file is a direct adaptation of the Symfony\Bundle\DoctrineBundle\Command\Proxy\CreateSchemaDoctrineCommand
 *
 */
class CreateSchemaDoctrineCommand extends CreateCommand implements ContainerAwareInterface
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('wikitag:schema:create')
            ->setDescription('Executes (or dumps) the SQL needed to generate the database schema')
            ->addOption('em', null, InputOption::VALUE_OPTIONAL, 'The entity manager to use for this command')
            ->setHelp(<<<EOT
The <info>doctrine:schema:create</info> command executes the SQL needed to
generate the database schema for the default entity manager:

<info>php app/console doctrine:schema:create</info>

You can also generate the database schema for a specific entity manager:

<info>php app/console doctrine:schema:create --em=default</info>

Finally, instead of executing the SQL, you can output the SQL:

<info>php app/console doctrine:schema:create --dump-sql</info>
EOT
        );
    }
    
   /**
    * @var ContainerInterface
    */
    private $container;
    
    protected function getContainer()
    {
        if (null === $this->container) {
            $this->container = $this->getApplication()->getKernel()->getContainer();
        }
    
        return $this->container;
    }
    
    /**
     * @see ContainerAwareInterface::setContainer()
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
    
        
    protected function executeSchemaCommand(InputInterface $input, OutputInterface $output, SchemaTool $schemaTool, array $metadatas)
    {
        
        $filteredSchemaTool = new FilteredSchemaTool($this->getHelper("em")->getEntityManager(), $this->getContainer());
        
        parent::executeSchemaCommand($input, $output, $filteredSchemaTool, $metadatas);
                
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        DoctrineCommandHelper::setApplicationEntityManager($this->getApplication(), $input->getOption('em'));

        parent::execute($input, $output);
    }
}
