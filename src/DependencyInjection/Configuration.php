<?php

declare(strict_types=1);

namespace DigitalCraftsman\CQRS\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    /**
     * @noinspection NullPointerExceptionInspection
     * @psalm-suppress MixedMethodCall
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('cqrs');
        $rootNode = $treeBuilder->getRootNode();

        /** @psalm-suppress PossiblyUndefinedMethod */
        $rootNode
            ->children()
                ->arrayNode('command_controller')
                    ->info('Add default instances for command controller')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('default_request_validator_classes')
                            ->beforeNormalization()->castToArray()->end()
                            ->defaultValue([])
                            ->prototype('scalar')->end()
                        ->end()
                        ->scalarNode('default_request_decoder_class')->defaultNull()->end()
                        ->arrayNode('default_request_data_transformer_classes')
                            ->beforeNormalization()->castToArray()->end()
                            ->defaultValue([])
                            ->prototype('scalar')->end()
                        ->end()
                        ->scalarNode('default_dto_constructor_class')->defaultNull()->end()
                        ->arrayNode('default_dto_validator_classes')
                            ->beforeNormalization()->castToArray()->end()
                            ->defaultValue([])
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('default_handler_wrapper_classes')
                            ->beforeNormalization()->castToArray()->end()
                            ->defaultValue([])
                            ->prototype('scalar')->end()
                        ->end()
                        ->scalarNode('default_response_constructor_class')->defaultNull()->end()
                    ->end()
                ->end()
                ->arrayNode('query_controller')
                    ->info('Add default instances for query controller')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('default_request_validator_classes')
                            ->beforeNormalization()->castToArray()->end()
                            ->defaultValue([])
                            ->prototype('scalar')->end()
                        ->end()
                        ->scalarNode('default_request_decoder_class')->defaultNull()->end()
                        ->arrayNode('default_request_data_transformer_classes')
                            ->beforeNormalization()->castToArray()->end()
                            ->defaultValue([])
                            ->prototype('scalar')->end()
                        ->end()
                        ->scalarNode('default_dto_constructor_class')->defaultNull()->end()
                        ->arrayNode('default_dto_validator_classes')
                            ->beforeNormalization()->castToArray()->end()
                            ->defaultValue([])
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('default_handler_wrapper_classes')
                            ->beforeNormalization()->castToArray()->end()
                            ->defaultValue([])
                            ->prototype('scalar')->end()
                        ->end()
                        ->scalarNode('default_response_constructor_class')->defaultNull()->end()
                    ->end()
                ->end()
                ->arrayNode('serializer_context')
                    ->beforeNormalization()->castToArray()->end()
                    ->defaultValue([])
                    ->prototype('scalar')->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
