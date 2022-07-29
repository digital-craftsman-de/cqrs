<?php

declare(strict_types=1);

namespace DigitalCraftsman\CQRS\DependencyInjection;

use DigitalCraftsman\CQRS\Command\CommandHandlerInterface;
use DigitalCraftsman\CQRS\DTOConstructor\DTOConstructorInterface;
use DigitalCraftsman\CQRS\DTOValidator\DTOValidatorInterface;
use DigitalCraftsman\CQRS\HandlerWrapper\HandlerWrapperInterface;
use DigitalCraftsman\CQRS\Query\QueryHandlerInterface;
use DigitalCraftsman\CQRS\RequestDataTransformer\RequestDataTransformerInterface;
use DigitalCraftsman\CQRS\RequestDecoder\RequestDecoderInterface;
use DigitalCraftsman\CQRS\RequestValidator\RequestValidatorInterface;
use DigitalCraftsman\CQRS\ResponseConstructor\ResponseConstructorInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class CQRSExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        $container
            ->registerForAutoconfiguration(RequestValidatorInterface::class)
            ->addTag('cqrs.request_validator');

        $container
            ->registerForAutoconfiguration(RequestDecoderInterface::class)
            ->addTag('cqrs.request_decoder');

        $container
            ->registerForAutoconfiguration(RequestDataTransformerInterface::class)
            ->addTag('cqrs.request_data_transformer');

        $container
            ->registerForAutoconfiguration(DTOConstructorInterface::class)
            ->addTag('cqrs.dto_constructor');

        $container
            ->registerForAutoconfiguration(DTOValidatorInterface::class)
            ->addTag('cqrs.dto_validator');

        $container
            ->registerForAutoconfiguration(HandlerWrapperInterface::class)
            ->addTag('cqrs.handler_wrapper');

        $container
            ->registerForAutoconfiguration(CommandHandlerInterface::class)
            ->addTag('cqrs.command_handler');

        $container
            ->registerForAutoconfiguration(QueryHandlerInterface::class)
            ->addTag('cqrs.query_handler');

        $container
            ->registerForAutoconfiguration(ResponseConstructorInterface::class)
            ->addTag('cqrs.response_constructor');

        $configuration = new Configuration();

        /**
         * @psalm-var array{
         *   query_controller: array{
         *     default_request_validator_classes: ?array<int, string>,
         *     default_request_decoder_class: ?string,
         *     default_request_data_tranformer_classes: ?array<int, string>,
         *     default_dto_constructor_class: ?string,
         *     default_dto_validator_classes: ?array<int, string>,
         *     default_handler_wrapper_classes: ?array<int, string>,
         *     default_response_constructor_class: ?string,
         *   },
         *   command_controller: array{
         *     default_request_validator_classes: ?array<int, string>,
         *     default_request_decoder_class: ?string,
         *     default_request_data_tranformer_classes: ?array<int, string>,
         *     default_dto_constructor_class: ?string,
         *     default_dto_validator_classes: ?array<int, string>,
         *     default_handler_wrapper_classes: ?array<int, string>,
         *     default_response_constructor_class: ?string,
         *   },
         *   serializer_context: array,
         * }
         */
        $config = $this->processConfiguration($configuration, $configs);

        foreach ($config['query_controller'] as $key => $value) {
            $container->setParameter('cqrs.query_controller.'.$key, $value);
        }

        foreach ($config['command_controller'] as $key => $value) {
            $container->setParameter('cqrs.command_controller.'.$key, $value);
        }

        $container->setParameter('cqrs.serializer_context', $config['serializer_context']);
    }
}
