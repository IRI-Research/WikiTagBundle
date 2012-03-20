<?php
/*
 * This file is part of the WikiTagBundle package.
 *
 * (c) IRI <http://www.iri.centrepompidou.fr/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace IRI\Bundle\WikiTagBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class WikiTagExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        
        if(isset($config['document_class']))
        {
            $container->setParameter("wiki_tag.document_class", $config['document_class']);
        }

        if(isset($config['ignore_wikipedia_error']))
        {
            $container->setParameter("wiki_tag.ignore_wikipedia_error", $config['ignore_wikipedia_error']);
        }
        
        
        if(isset($config['document_id_column']))
        {
            $document_id = $config['document_id_column'];
        }
        
        if(!isset($document_id) || is_null($document_id) || strlen($document_id) == 0) {
            $document_id = "id";
        }
        $container->setParameter("wiki_tag.document_id_column", $document_id);
        
        $fields = $config['fields'];
        $container->setParameter("wiki_tag.fields", $fields);
        $fields['tagsStr'] = array("type"=>"text");
        $container->setParameter("wiki_tag.fields_all", $fields);
        $container->setParameter("wiki_tag.route_for_documents_by_tag", $config['route_for_documents_by_tag']);
        $container->setParameter("wiki_tag.reactive_selectors", $config['reactive_selectors']);
        $container->setParameter("wiki_tag.document_list_profile", $config['document_list_profile']);
        $container->setParameter("wiki_tag.curl_options", $config['curl_options']);
    }
}
