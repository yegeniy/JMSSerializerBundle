<?php

/*
 * Copyright 2011 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace JMS\SerializerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class Configuration implements ConfigurationInterface
{
    private $debug;
    private $factories;

    public function __construct($debug = false, array $factories = array())
    {
        $this->debug = $debug;
        $this->factories = $factories;
    }

    public function getConfigTreeBuilder()
    {
        $tb = new TreeBuilder();
        $tb
            ->root('jms_serializer', 'array')
                ->children()
                    ->append($this->getPropertyNamingDef())
                    ->append($this->getMetadataDef())

                    ->fixXmlConfig('handler')
                    ->append($this->getHandlerDef())

                    ->fixXmlConfig('serializer')
                    ->arrayNode('serializers')
                        ->validate()
                            ->ifTrue(function($v) { return isset($v['default']); })
                            ->thenInvalid('The name "default" is reserved, and must not be used.')
                        ->end()
                        ->prototype('array')
                            ->useAttributeAsKey('name')
                            ->children()
                                ->append($this->getPropertyNamingDef())
                                ->append($this->getMetadataDef())

                                ->fixXmlConfig('handler')
                                ->append($this->getHandlerDef())
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $tb;
    }

    private function getHandlerDef()
    {
        $def = new ArrayNodeDefinition('handlers');
        $handlerNode = $def
            ->addDefaultsIfNotSet()
            ->disallowNewKeysInSubsequentConfigs()
            ->children()
        ;

        foreach ($this->factories as $factory) {
            $factory->addConfiguration(
                $handlerNode->arrayNode($factory->getConfigKey())->canBeUnset());
        }

        return $def;
    }

    private function getPropertyNamingDef()
    {
        $def = new ArrayNodeDefinition('property_naming');
        $def
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('id')->cannotBeEmpty()->end()
                ->scalarNode('separator')->defaultValue('_')->end()
                ->booleanNode('lower_case')->defaultTrue()->end()
                ->booleanNode('enable_cache')->defaultTrue()->end()
            ->end()
        ;

        return $def;
    }

    private function getMetadataDef()
    {
        $def = new ArrayNodeDefinition('metadata');
        $def
            ->addDefaultsIfNotSet()
            ->fixXmlConfig('directory', 'directories')
            ->children()
                ->scalarNode('cache')->defaultValue('file')->end()
                ->booleanNode('debug')->defaultValue($this->debug)->end()
                ->arrayNode('file_cache')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('dir')->defaultValue('%kernel.cache_dir%/jms_serializer')->end()
                    ->end()
                ->end()
                ->booleanNode('auto_detection')->defaultTrue()->end()
                ->arrayNode('directories')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('path')->isRequired()->end()
                            ->scalarNode('namespace_prefix')->defaultValue('')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $def;
    }
}
