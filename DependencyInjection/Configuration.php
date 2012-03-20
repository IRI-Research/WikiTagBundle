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

use Symfony\Component\Config\Definition\ScalarNode;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('wiki_tag');
        
        $rootNode
            ->children()
                ->scalarNode('route_for_documents_by_tag')->defaultNull()->end()
                ->booleanNode('ignore_wikipedia_error')->defaultFalse()->end()
                ->scalarNode('document_class')->isRequired()->end()
                ->scalarNode('document_id_column')->defaultValue('id')->end()
            ->end()
            ->fixXmlConfig('field')
            ->children()
                ->arrayNode('fields')
                    ->treatNullLike(array())
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->performNoDeepMerging()
                        ->children()
                            ->scalarNode('type')->defaultValue('text')->end()
                            ->scalarNode('length')->defaultValue(1024)
                                ->beforeNormalization()
                                    ->ifString()
                                    ->then(function($v) { return intval($v); })
                                ->end()
                            ->end()
                            ->scalarNode('weight')->defaultValue(1.0)
                                ->beforeNormalization()
                                    ->ifString()
                                    ->then(function($v) { return floatval($v); })
                                ->end()
                            ->end()
                            ->scalarNode('accessor')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('reactive_selectors')
                    ->treatNullLike(array())
                    ->useAttributeAsKey('name')
                    ->prototype('variable')
                    ->end()
                ->end()
                ->arrayNode('document_list_profile')
                    ->treatNullLike(array())
                    ->useAttributeAsKey('name')
                    ->prototype('variable')
                    ->end()
                ->end()
                ->arrayNode('curl_options')
                    ->treatNullLike(array())
                    ->useAttributeAsKey('name')
                    ->prototype('variable')
                    ->end()
                ->end()
            ->end();
        
        return $treeBuilder;
    }
}
