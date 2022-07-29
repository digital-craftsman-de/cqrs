<?php

declare(strict_types=1);

namespace DigitalCraftsman\CQRS\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/** @coversDefaultClass \DigitalCraftsman\CQRS\DependencyInjection\CQRSExtension */
final class CQRSExtensionTest extends TestCase
{
    /**
     * @test
     * @covers ::load
     *
     * The auto tagging works only for files in "src" and therefore the others below the "tests" directory can't be validated.
     * Still looking for an easy way to register the services to be able to test the tagging.
     */
    public function load_works(): void
    {
        // -- Arrange
        $container = new ContainerBuilder();
        $cqrsExtension = new CQRSExtension();

        // -- Act
        $cqrsExtension->load([], $container);

        // -- Assert
        self::assertCount(1, $container->findTaggedServiceIds('cqrs.request_decoder'));
        self::assertCount(1, $container->findTaggedServiceIds('cqrs.dto_constructor'));
        self::assertCount(4, $container->findTaggedServiceIds('cqrs.response_constructor'));

        // No data is supplied as config, therefore the parameters are set, but empty
        /** @psalm-suppress PossiblyInvalidArgument */
        self::assertCount(0, $container->getParameter('cqrs.query_controller.default_request_validator_classes'));
        self::assertNull($container->getParameter('cqrs.query_controller.default_request_decoder_class'));
        /** @psalm-suppress PossiblyInvalidArgument */
        self::assertCount(0, $container->getParameter('cqrs.query_controller.default_request_data_transformer_classes'));
        self::assertNull($container->getParameter('cqrs.query_controller.default_dto_constructor_class'));
        /** @psalm-suppress PossiblyInvalidArgument */
        self::assertCount(0, $container->getParameter('cqrs.query_controller.default_dto_validator_classes'));
        /** @psalm-suppress PossiblyInvalidArgument */
        self::assertCount(0, $container->getParameter('cqrs.query_controller.default_handler_wrapper_classes'));
        self::assertNull($container->getParameter('cqrs.query_controller.default_response_constructor_class'));

        /** @psalm-suppress PossiblyInvalidArgument */
        self::assertCount(0, $container->getParameter('cqrs.command_controller.default_request_validator_classes'));
        self::assertNull($container->getParameter('cqrs.command_controller.default_request_decoder_class'));
        /** @psalm-suppress PossiblyInvalidArgument */
        self::assertCount(0, $container->getParameter('cqrs.command_controller.default_request_data_transformer_classes'));
        self::assertNull($container->getParameter('cqrs.command_controller.default_dto_constructor_class'));
        /** @psalm-suppress PossiblyInvalidArgument */
        self::assertCount(0, $container->getParameter('cqrs.command_controller.default_dto_validator_classes'));
        /** @psalm-suppress PossiblyInvalidArgument */
        self::assertCount(0, $container->getParameter('cqrs.command_controller.default_handler_wrapper_classes'));
        self::assertNull($container->getParameter('cqrs.command_controller.default_response_constructor_class'));

        /** @psalm-suppress PossiblyInvalidArgument */
        self::assertCount(0, $container->getParameter('cqrs.serializer_context'));
    }
}
