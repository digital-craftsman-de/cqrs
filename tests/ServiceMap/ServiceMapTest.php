<?php

declare(strict_types=1);

namespace DigitalCraftsman\CQRS\ServiceMap;

use DigitalCraftsman\CQRS\DTO\HandlerWrapperConfiguration;
use DigitalCraftsman\CQRS\DTOConstructor\SerializerDTOConstructor;
use DigitalCraftsman\CQRS\RequestDecoder\JsonRequestDecoder;
use DigitalCraftsman\CQRS\RequestValidator\GuardAgainstFileWithVirusRequestValidator;
use DigitalCraftsman\CQRS\RequestValidator\GuardAgainstTokenInHeaderRequestValidator;
use DigitalCraftsman\CQRS\ResponseConstructor\EmptyJsonResponseConstructor;
use DigitalCraftsman\CQRS\ResponseConstructor\EmptyResponseConstructor;
use DigitalCraftsman\CQRS\ServiceMap\Exception\ConfiguredCommandHandlerNotAvailable;
use DigitalCraftsman\CQRS\ServiceMap\Exception\ConfiguredDTOConstructorNotAvailable;
use DigitalCraftsman\CQRS\ServiceMap\Exception\ConfiguredDTOValidatorNotAvailable;
use DigitalCraftsman\CQRS\ServiceMap\Exception\ConfiguredHandlerWrapperNotAvailable;
use DigitalCraftsman\CQRS\ServiceMap\Exception\ConfiguredQueryHandlerNotAvailable;
use DigitalCraftsman\CQRS\ServiceMap\Exception\ConfiguredRequestDataTransformerNotAvailable;
use DigitalCraftsman\CQRS\ServiceMap\Exception\ConfiguredRequestDecoderNotAvailable;
use DigitalCraftsman\CQRS\ServiceMap\Exception\ConfiguredRequestValidatorNotAvailable;
use DigitalCraftsman\CQRS\ServiceMap\Exception\ConfiguredResponseConstructorNotAvailable;
use DigitalCraftsman\CQRS\ServiceMap\Exception\DTOConstructorOrDefaultDTOConstructorMustBeConfigured;
use DigitalCraftsman\CQRS\ServiceMap\Exception\RequestDecoderOrDefaultRequestDecoderMustBeConfigured;
use DigitalCraftsman\CQRS\ServiceMap\Exception\ResponseConstructorOrDefaultResponseConstructorMustBeConfigured;
use DigitalCraftsman\CQRS\Test\Application\AddActionIdRequestDataTransformer;
use DigitalCraftsman\CQRS\Test\Application\Authentication\UserIdValidator;
use DigitalCraftsman\CQRS\Test\Application\ConnectionTransactionWrapper;
use DigitalCraftsman\CQRS\Test\Application\FileSizeValidator;
use DigitalCraftsman\CQRS\Test\Application\SilentExceptionWrapper;
use DigitalCraftsman\CQRS\Test\AppTestCase;
use DigitalCraftsman\CQRS\Test\Domain\News\WriteSide\CreateNewsArticle\CreateNewsArticleRequestDataTransformer;
use DigitalCraftsman\CQRS\Test\Domain\Tasks\ReadSide\GetTasks\GetTasksQueryHandler;
use DigitalCraftsman\CQRS\Test\Domain\Tasks\WriteSide\CreateTask\CreateTaskCommandHandler;
use DigitalCraftsman\CQRS\Test\Domain\Tasks\WriteSide\CreateTask\CreateTaskDTOConstructor;
use DigitalCraftsman\CQRS\Test\Domain\Tasks\WriteSide\CreateTask\CreateTaskRequestDecoder;
use DigitalCraftsman\CQRS\Test\Domain\Tasks\WriteSide\DefineTaskHourContingent\DefineTaskHourContingentRequestDataTransformer;
use DigitalCraftsman\CQRS\Test\Domain\Tasks\WriteSide\MarkTaskAsAccepted\Exception\TaskAlreadyAccepted;
use DigitalCraftsman\CQRS\Test\Domain\Tasks\WriteSide\MarkTaskAsAccepted\MarkTaskAsAcceptedCommandHandler;
use DigitalCraftsman\CQRS\Test\Helper\ServiceMapHelper;
use DigitalCraftsman\CQRS\Test\Repository\TasksInMemoryRepository;
use DigitalCraftsman\CQRS\Test\Utility\ConnectionSimulator;
use DigitalCraftsman\CQRS\Test\Utility\SecuritySimulator;
use DigitalCraftsman\CQRS\Test\Utility\ServiceLocatorSimulator;
use DigitalCraftsman\CQRS\Test\Utility\VirusScannerSimulator;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/** @coversDefaultClass \DigitalCraftsman\CQRS\ServiceMap\ServiceMap */
final class ServiceMapTest extends AppTestCase
{
    private DenormalizerInterface $serializer;
    private SecuritySimulator $securitySimulator;

    public function setUp(): void
    {
        parent::setUp();

        $this->serializer = $this->getContainerService(DenormalizerInterface::class);
        $this->securitySimulator = new SecuritySimulator();
    }

    // -- Construction

    /**
     * @test
     * @covers ::__construct
     * @doesNotPerformAssertions
     * @noinspection PhpExpressionResultUnusedInspection
     */
    public function construction_works(): void
    {
        // -- Arrange & Act & Assert
        new ServiceMap(
            requestValidators: new ServiceLocatorSimulator([
                GuardAgainstTokenInHeaderRequestValidator::class => new GuardAgainstTokenInHeaderRequestValidator(),
            ]),
            requestDecoders: new ServiceLocatorSimulator([
                CreateTaskRequestDecoder::class => new CreateTaskRequestDecoder(),
                JsonRequestDecoder::class => new JsonRequestDecoder(),
            ]),
            requestDataTransformers: new ServiceLocatorSimulator([
                CreateNewsArticleRequestDataTransformer::class => new CreateNewsArticleRequestDataTransformer(),
                DefineTaskHourContingentRequestDataTransformer::class => new DefineTaskHourContingentRequestDataTransformer(),
            ]),
            dtoConstructors: new ServiceLocatorSimulator([
                SerializerDTOConstructor::class => new SerializerDTOConstructor($this->serializer),
                CreateTaskDTOConstructor::class => new CreateTaskDTOConstructor(),
            ]),
            dtoValidators: new ServiceLocatorSimulator([
                FileSizeValidator::class => new FileSizeValidator(10),
                UserIdValidator::class => new UserIdValidator($this->securitySimulator),
            ]),
            handlerWrappers: new ServiceLocatorSimulator([
                SilentExceptionWrapper::class => new SilentExceptionWrapper(),
                ConnectionTransactionWrapper::class => new ConnectionTransactionWrapper(new ConnectionSimulator()),
            ]),
            commandHandlers: new ServiceLocatorSimulator([
                CreateTaskCommandHandler::class => new CreateTaskCommandHandler(),
                MarkTaskAsAcceptedCommandHandler::class => new MarkTaskAsAcceptedCommandHandler(),
            ]),
            queryHandlers: new ServiceLocatorSimulator([
                GetTasksQueryHandler::class => new GetTasksQueryHandler(new TasksInMemoryRepository()),
            ]),
            responseConstructors: new ServiceLocatorSimulator([
                EmptyResponseConstructor::class => new EmptyResponseConstructor(),
                EmptyJsonResponseConstructor::class => new EmptyJsonResponseConstructor(),
            ]),
        );
    }

    // -- Request validators

    /**
     * @test
     * @covers ::getRequestValidators
     * @covers ::__construct
     */
    public function get_request_validators_works_with_request_validator_classes(): void
    {
        // -- Arrange
        $allRequestValidators = [
            new GuardAgainstTokenInHeaderRequestValidator(),
            $guardAgainstFileWithVirusRequestValidator = new GuardAgainstFileWithVirusRequestValidator(new VirusScannerSimulator()),
        ];
        $serviceMap = ServiceMapHelper::serviceMap(requestValidators: $allRequestValidators);

        // -- Act
        $requestValidators = $serviceMap->getRequestValidators(
            [GuardAgainstFileWithVirusRequestValidator::class],
            null,
        );

        // -- Assert
        self::assertCount(1, $requestValidators);
        self::assertContains($guardAgainstFileWithVirusRequestValidator, $requestValidators);
    }

    /**
     * @test
     * @covers ::getRequestValidators
     */
    public function get_request_validator_works_with_default_request_validator_classes(): void
    {
        // -- Arrange
        $allRequestValidators = [
            new GuardAgainstTokenInHeaderRequestValidator(),
            $defaultRequestDataTransformer = new GuardAgainstFileWithVirusRequestValidator(new VirusScannerSimulator()),
        ];
        $serviceMap = ServiceMapHelper::serviceMap(requestValidators: $allRequestValidators);

        // -- Act
        $requestValidators = $serviceMap->getRequestValidators(
            null,
            [GuardAgainstFileWithVirusRequestValidator::class],
        );

        // -- Assert
        self::assertCount(1, $requestValidators);
        self::assertContains($defaultRequestDataTransformer, $requestValidators);
    }

    /**
     * @test
     * @covers ::getRequestValidators
     */
    public function get_request_validators_works_with_no_dto_validator_classes_and_no_default_request_validator_classes(): void
    {
        // -- Arrange
        $allRequestValidators = [
            new GuardAgainstTokenInHeaderRequestValidator(),
            new GuardAgainstFileWithVirusRequestValidator(new VirusScannerSimulator()),
        ];
        $serviceMap = ServiceMapHelper::serviceMap(requestValidators: $allRequestValidators);

        // -- Act
        $requestValidators = $serviceMap->getRequestValidators(
            null,
            null,
        );

        // -- Assert
        self::assertCount(0, $requestValidators);
    }

    /**
     * @test
     * @covers ::getRequestValidators
     */
    public function get_request_validators_fails_when_request_validator_classes_are_not_available(): void
    {
        // -- Assert
        $this->expectException(ConfiguredRequestValidatorNotAvailable::class);

        // -- Arrange
        $allRequestValidators = [
            new GuardAgainstTokenInHeaderRequestValidator(),
        ];
        $serviceMap = ServiceMapHelper::serviceMap(requestValidators: $allRequestValidators);

        // -- Act
        $serviceMap->getRequestValidators(
            [GuardAgainstFileWithVirusRequestValidator::class],
            null,
        );
    }

    /**
     * @test
     * @covers ::getRequestValidators
     */
    public function get_request_validators_fails_when_default_request_validator_classes_are_not_available(): void
    {
        // -- Assert
        $this->expectException(ConfiguredRequestValidatorNotAvailable::class);

        // -- Arrange
        $allRequestValidators = [
            new GuardAgainstTokenInHeaderRequestValidator(),
        ];
        $serviceMap = ServiceMapHelper::serviceMap(requestValidators: $allRequestValidators);

        // -- Act
        $serviceMap->getRequestValidators(
            null,
            [GuardAgainstFileWithVirusRequestValidator::class],
        );
    }

    // -- Request decoders

    /**
     * @test
     * @covers ::getRequestDecoder
     * @covers ::__construct
     */
    public function get_request_decoder_works_with_request_decoder_class(): void
    {
        // -- Arrange
        $requestDecoders = [
            $createTaskRequestDecoder = new CreateTaskRequestDecoder(),
            new JsonRequestDecoder(),
        ];
        $serviceMap = ServiceMapHelper::serviceMap(requestDecoders: $requestDecoders);

        // -- Act
        $requestDecoder = $serviceMap->getRequestDecoder(CreateTaskRequestDecoder::class, null);

        // -- Assert
        self::assertSame($createTaskRequestDecoder, $requestDecoder);
    }

    /**
     * @test
     * @covers ::getRequestDecoder
     */
    public function get_request_decoder_works_with_default_request_decoder_class(): void
    {
        // -- Arrange
        $requestDecoders = [
            new CreateTaskRequestDecoder(),
            $defaultRequestDecoder = new JsonRequestDecoder(),
        ];
        $serviceMap = ServiceMapHelper::serviceMap(requestDecoders: $requestDecoders);

        // -- Act
        $requestDecoder = $serviceMap->getRequestDecoder(null, JsonRequestDecoder::class);

        // -- Assert
        self::assertSame($defaultRequestDecoder, $requestDecoder);
    }

    /**
     * @test
     * @covers ::getRequestDecoder
     */
    public function get_request_decoder_fails_when_request_decoder_class_is_not_available(): void
    {
        // -- Assert
        $this->expectException(ConfiguredRequestDecoderNotAvailable::class);

        // -- Arrange
        $requestDecoders = [
            new JsonRequestDecoder(),
        ];
        $serviceMap = ServiceMapHelper::serviceMap(requestDecoders: $requestDecoders);

        // -- Act
        $serviceMap->getRequestDecoder(CreateTaskRequestDecoder::class, null);
    }

    /**
     * @test
     * @covers ::getRequestDecoder
     */
    public function get_request_decoder_fails_when_default_request_decoder_class_is_not_available(): void
    {
        // -- Assert
        $this->expectException(ConfiguredRequestDecoderNotAvailable::class);

        // -- Arrange
        $requestDecoders = [
            new CreateTaskRequestDecoder(),
        ];
        $serviceMap = ServiceMapHelper::serviceMap(requestDecoders: $requestDecoders);

        // -- Act
        $serviceMap->getRequestDecoder(null, JsonRequestDecoder::class);
    }

    /**
     * @test
     * @covers ::getRequestDecoder
     */
    public function get_request_decoder_fails_when_no_request_decoder_class_and_default_request_decoder_class_is_defined(): void
    {
        // -- Assert
        $this->expectException(RequestDecoderOrDefaultRequestDecoderMustBeConfigured::class);

        // -- Arrange
        $serviceMap = ServiceMapHelper::serviceMap(requestDecoders: []);

        // -- Act
        $serviceMap->getRequestDecoder(null, null);
    }

    // -- Request data transformers

    /**
     * @test
     * @covers ::getRequestDataTransformers
     * @covers ::__construct
     */
    public function get_request_data_transformers_works_with_request_data_transformer_classes(): void
    {
        // -- Arrange
        $allRequestDataTransformers = [
            new CreateNewsArticleRequestDataTransformer(),
            $defineTaskHourContingentRequestDataTransformer = new DefineTaskHourContingentRequestDataTransformer(),
        ];
        $serviceMap = ServiceMapHelper::serviceMap(requestDataTransformers: $allRequestDataTransformers);

        // -- Act
        $requestDataTransformers = $serviceMap->getRequestDataTransformers(
            [DefineTaskHourContingentRequestDataTransformer::class],
            null,
        );

        // -- Assert
        self::assertCount(1, $requestDataTransformers);
        self::assertContains($defineTaskHourContingentRequestDataTransformer, $requestDataTransformers);
    }

    /**
     * @test
     * @covers ::getRequestDataTransformers
     */
    public function get_request_data_transformers_works_with_default_request_data_transformer_classes(): void
    {
        // -- Arrange
        $allRequestDataTransformers = [
            new CreateNewsArticleRequestDataTransformer(),
            new DefineTaskHourContingentRequestDataTransformer(),
            $defaultRequestDataTransformer = new AddActionIdRequestDataTransformer(),
        ];
        $serviceMap = ServiceMapHelper::serviceMap(requestDataTransformers: $allRequestDataTransformers);

        // -- Act
        $requestDataTransformers = $serviceMap->getRequestDataTransformers(
            null,
            [AddActionIdRequestDataTransformer::class],
        );

        // -- Assert
        self::assertCount(1, $requestDataTransformers);
        self::assertContains($defaultRequestDataTransformer, $requestDataTransformers);
    }

    /**
     * @test
     * @covers ::getRequestDataTransformers
     */
    public function get_request_data_transformers_works_with_no_dto_data_transformer_classes_and_no_default_request_data_transformer_classes(): void
    {
        // -- Arrange
        $allRequestDataTransformers = [
            new CreateNewsArticleRequestDataTransformer(),
            new DefineTaskHourContingentRequestDataTransformer(),
        ];
        $serviceMap = ServiceMapHelper::serviceMap(requestDataTransformers: $allRequestDataTransformers);

        // -- Act
        $requestDataTransformers = $serviceMap->getRequestDataTransformers(
            null,
            null,
        );

        // -- Assert
        self::assertCount(0, $requestDataTransformers);
    }

    /**
     * @test
     * @covers ::getRequestDataTransformers
     */
    public function get_request_data_transformers_fails_when_request_data_transformer_classes_are_not_available(): void
    {
        // -- Assert
        $this->expectException(ConfiguredRequestDataTransformerNotAvailable::class);

        // -- Arrange
        $allRequestDataTransformers = [
            new CreateNewsArticleRequestDataTransformer(),
            new AddActionIdRequestDataTransformer(),
        ];
        $serviceMap = ServiceMapHelper::serviceMap(requestDataTransformers: $allRequestDataTransformers);

        // -- Act
        $serviceMap->getRequestDataTransformers(
            [DefineTaskHourContingentRequestDataTransformer::class],
            null,
        );
    }

    /**
     * @test
     * @covers ::getRequestDataTransformers
     */
    public function get_request_data_transformers_fails_when_default_request_data_transformer_classes_are_not_available(): void
    {
        // -- Assert
        $this->expectException(ConfiguredRequestDataTransformerNotAvailable::class);

        // -- Arrange
        $allRequestDataTransformers = [
            new CreateNewsArticleRequestDataTransformer(),
            new DefineTaskHourContingentRequestDataTransformer(),
        ];
        $serviceMap = ServiceMapHelper::serviceMap(requestDataTransformers: $allRequestDataTransformers);

        // -- Act
        $serviceMap->getRequestDataTransformers(
            null,
            [AddActionIdRequestDataTransformer::class],
        );
    }

    // -- DTO constructors

    /**
     * @test
     * @covers ::getDTOConstructor
     * @covers ::__construct
     */
    public function get_dto_constructor_works_with_dto_constructor_class(): void
    {
        // -- Arrange
        $dtoConstructors = [
            new SerializerDTOConstructor($this->serializer),
            $createTaskDTOConstructor = new CreateTaskDTOConstructor(),
        ];
        $serviceMap = ServiceMapHelper::serviceMap(dtoConstructors: $dtoConstructors);

        // -- Act
        $dtoConstructor = $serviceMap->getDTOConstructor(
            CreateTaskDTOConstructor::class,
            null,
        );

        // -- Assert
        self::assertSame($createTaskDTOConstructor, $dtoConstructor);
    }

    /**
     * @test
     * @covers ::getDTOConstructor
     */
    public function get_dto_constructor_works_with_default_dto_constructor_class(): void
    {
        // -- Arrange
        $dtoConstructors = [
            $defaultDTOConstructor = new SerializerDTOConstructor($this->serializer),
            new CreateTaskDTOConstructor(),
        ];
        $serviceMap = ServiceMapHelper::serviceMap(dtoConstructors: $dtoConstructors);

        // -- Act
        $dtoConstructor = $serviceMap->getDTOConstructor(
            null,
            SerializerDTOConstructor::class,
        );

        // -- Assert
        self::assertSame($defaultDTOConstructor, $dtoConstructor);
    }

    /**
     * @test
     * @covers ::getDTOConstructor
     */
    public function get_dto_constructor_fails_when_dto_constructor_class_is_not_available(): void
    {
        // -- Assert
        $this->expectException(ConfiguredDTOConstructorNotAvailable::class);

        // -- Arrange
        $dtoConstructors = [
            new SerializerDTOConstructor($this->serializer),
        ];
        $serviceMap = ServiceMapHelper::serviceMap(dtoConstructors: $dtoConstructors);

        // -- Act
        $serviceMap->getDTOConstructor(CreateTaskDTOConstructor::class, null);
    }

    /**
     * @test
     * @covers ::getDTOConstructor
     */
    public function get_dto_constructor_fails_when_default_dto_constructor_class_is_not_available(): void
    {
        // -- Assert
        $this->expectException(ConfiguredDTOConstructorNotAvailable::class);

        // -- Arrange
        $dtoConstructors = [
            new CreateTaskDTOConstructor(),
        ];
        $serviceMap = ServiceMapHelper::serviceMap(dtoConstructors: $dtoConstructors);

        // -- Act
        $serviceMap->getDTOConstructor(null, SerializerDTOConstructor::class);
    }

    /**
     * @test
     * @covers ::getDTOConstructor
     */
    public function get_dto_constructor_fails_when_no_dto_constructor_class_and_default_dto_constructor_class_is_defined(): void
    {
        // -- Assert
        $this->expectException(DTOConstructorOrDefaultDTOConstructorMustBeConfigured::class);

        // -- Arrange
        $serviceMap = ServiceMapHelper::serviceMap(dtoConstructors: []);

        // -- Act
        $serviceMap->getDTOConstructor(null, null);
    }

    // -- DTO validators

    /**
     * @test
     * @covers ::getDTOValidators
     * @covers ::__construct
     */
    public function get_dto_validators_works_with_dto_validators_classes(): void
    {
        // -- Arrange
        $allDTOValidators = [
            $fileSizeValidator = new FileSizeValidator(10),
            new UserIdValidator($this->securitySimulator),
        ];
        $serviceMap = ServiceMapHelper::serviceMap(dtoValidators: $allDTOValidators);

        // -- Act
        $dtoValidators = $serviceMap->getDTOValidators(
            [FileSizeValidator::class],
            null,
        );

        // -- Assert
        self::assertCount(1, $dtoValidators);
        self::assertContains($fileSizeValidator, $dtoValidators);
    }

    /**
     * @test
     * @covers ::getDTOValidators
     */
    public function get_dto_validators_works_with_default_dto_validator_classes(): void
    {
        // -- Arrange
        $allDTOValidators = [
            new FileSizeValidator(10),
            $defaultDataTransformer = new UserIdValidator($this->securitySimulator),
        ];
        $serviceMap = ServiceMapHelper::serviceMap(dtoValidators: $allDTOValidators);

        // -- Act
        $dtoValidators = $serviceMap->getDTOValidators(
            null,
            [UserIdValidator::class],
        );

        // -- Assert
        self::assertCount(1, $dtoValidators);
        self::assertContains($defaultDataTransformer, $dtoValidators);
    }

    /**
     * @test
     * @covers ::getDTOValidators
     */
    public function get_dto_validators_works_with_no_dto_validator_classes_and_no_default_dto_validator_classes(): void
    {
        // -- Arrange
        $allDTOValidators = [
            new FileSizeValidator(10),
            new UserIdValidator($this->securitySimulator),
        ];
        $serviceMap = ServiceMapHelper::serviceMap(dtoValidators: $allDTOValidators);

        // -- Act
        $dtoValidators = $serviceMap->getDTOValidators(
            null,
            null,
        );

        // -- Assert
        self::assertCount(0, $dtoValidators);
    }

    /**
     * @test
     * @covers ::getDTOValidators
     */
    public function get_dto_validators_fails_when_dto_validator_classes_are_not_available(): void
    {
        // -- Assert
        $this->expectException(ConfiguredDTOValidatorNotAvailable::class);

        // -- Arrange
        $allDTOValidators = [
            new UserIdValidator($this->securitySimulator),
        ];
        $serviceMap = ServiceMapHelper::serviceMap(dtoValidators: $allDTOValidators);

        // -- Act
        $serviceMap->getDTOValidators(
            [FileSizeValidator::class],
            null,
        );
    }

    /**
     * @test
     * @covers ::getDTOValidators
     */
    public function get_dto_validators_fails_when_default_dto_data_transformer_classes_are_not_available(): void
    {
        // -- Assert
        $this->expectException(ConfiguredDTOValidatorNotAvailable::class);

        // -- Arrange
        $allDTOValidators = [
            new FileSizeValidator(10),
        ];
        $serviceMap = ServiceMapHelper::serviceMap(dtoValidators: $allDTOValidators);

        // -- Act
        $serviceMap->getDTOValidators(
            null,
            [UserIdValidator::class],
        );
    }

    // -- Handler wrappers

    /**
     * @test
     * @covers ::getHandlerWrappersWithParameters
     * @covers ::__construct
     */
    public function get_handler_wrappers_works_with_handler_wrapper_configurations(): void
    {
        // -- Arrange
        $handlerWrappers = [
            new SilentExceptionWrapper(),
            new ConnectionTransactionWrapper(new ConnectionSimulator()),
        ];
        $serviceMap = ServiceMapHelper::serviceMap(handlerWrappers: $handlerWrappers);

        $handlerWrapperConfiguration = new HandlerWrapperConfiguration(
            SilentExceptionWrapper::class,
            [
                TaskAlreadyAccepted::class,
            ],
        );

        // -- Act
        $handlerWrappersWithParameters = $serviceMap->getHandlerWrappersWithParameters(
            [$handlerWrapperConfiguration],
            null,
        );

        // -- Assert
        self::assertCount(1, $handlerWrappersWithParameters);
        self::assertSame(SilentExceptionWrapper::class, $handlerWrappersWithParameters[0]->handlerWrapper::class);
        self::assertSame([TaskAlreadyAccepted::class], $handlerWrappersWithParameters[0]->parameters);
    }

    /**
     * @test
     * @covers ::getHandlerWrappersWithParameters
     */
    public function get_handler_wrappers_works_with_default_handler_wrapper_classes(): void
    {
        // -- Arrange
        $handlerWrappers = [
            new SilentExceptionWrapper(),
            new ConnectionTransactionWrapper(new ConnectionSimulator()),
        ];
        $serviceMap = ServiceMapHelper::serviceMap(handlerWrappers: $handlerWrappers);

        // -- Act
        $handlerWrappersWithParameters = $serviceMap->getHandlerWrappersWithParameters(
            null,
            [ConnectionTransactionWrapper::class],
        );

        // -- Assert
        self::assertCount(1, $handlerWrappersWithParameters);
        self::assertSame(ConnectionTransactionWrapper::class, $handlerWrappersWithParameters[0]->handlerWrapper::class);
        self::assertNull($handlerWrappersWithParameters[0]->parameters);
    }

    /**
     * @test
     * @covers ::getHandlerWrappersWithParameters
     */
    public function get_handler_wrappers_works_with_no_handler_wrapper_configurations_and_no_default_handler_wrapper_classes(): void
    {
        // -- Arrange
        $handlerWrappers = [
            new SilentExceptionWrapper(),
            new ConnectionTransactionWrapper(new ConnectionSimulator()),
        ];
        $serviceMap = ServiceMapHelper::serviceMap(handlerWrappers: $handlerWrappers);

        // -- Act
        $handlerWrappersWithParameters = $serviceMap->getHandlerWrappersWithParameters(
            null,
            null,
        );

        // -- Assert
        self::assertCount(0, $handlerWrappersWithParameters);
    }

    /**
     * @test
     * @covers ::getHandlerWrappersWithParameters
     */
    public function get_handler_wrappers_fails_when_handler_wrapper_in_configuration_is_not_available(): void
    {
        // -- Assert
        $this->expectException(ConfiguredHandlerWrapperNotAvailable::class);

        // -- Arrange
        $handlerWrappers = [
            new ConnectionTransactionWrapper(new ConnectionSimulator()),
        ];
        $serviceMap = ServiceMapHelper::serviceMap(handlerWrappers: $handlerWrappers);

        $handlerWrapperConfiguration = new HandlerWrapperConfiguration(
            SilentExceptionWrapper::class,
            [
                TaskAlreadyAccepted::class,
            ],
        );

        // -- Act
        $serviceMap->getHandlerWrappersWithParameters(
            [$handlerWrapperConfiguration],
            null,
        );
    }

    /**
     * @test
     * @covers ::getHandlerWrappersWithParameters
     */
    public function get_handler_wrappers_fails_when_default_handler_wrapper_classes_are_not_available(): void
    {
        // -- Assert
        $this->expectException(ConfiguredHandlerWrapperNotAvailable::class);

        // -- Arrange
        $handlerWrappers = [
            new SilentExceptionWrapper(),
        ];
        $serviceMap = ServiceMapHelper::serviceMap(handlerWrappers: $handlerWrappers);

        // -- Act
        $serviceMap->getHandlerWrappersWithParameters(
            null,
            [ConnectionTransactionWrapper::class],
        );
    }

    // -- Command handler

    /**
     * @test
     * @covers ::getCommandHandler
     * @covers ::__construct
     */
    public function get_command_handler_works(): void
    {
        // -- Arrange
        $commandHandlers = [
            $createTaskCommandHandler = new CreateTaskCommandHandler(),
        ];
        $serviceMap = ServiceMapHelper::serviceMap(commandHandlers: $commandHandlers);

        // -- Act
        $commandHandler = $serviceMap->getCommandHandler(CreateTaskCommandHandler::class);

        // -- Assert
        self::assertSame($createTaskCommandHandler, $commandHandler);
    }

    /**
     * @test
     * @covers ::getCommandHandler
     */
    public function get_command_handler_fails_if_not_available(): void
    {
        // -- Assert
        $this->expectException(ConfiguredCommandHandlerNotAvailable::class);

        // -- Arrange
        $serviceMap = ServiceMapHelper::serviceMap(commandHandlers: []);

        // -- Act
        $serviceMap->getCommandHandler(CreateTaskCommandHandler::class);
    }

    // -- Query handler

    /**
     * @test
     * @covers ::getQueryHandler
     * @covers ::__construct
     */
    public function get_query_handler_works(): void
    {
        // -- Arrange
        $queryHandlers = [
            $getTasksQueryHandler = new GetTasksQueryHandler(new TasksInMemoryRepository()),
        ];
        $serviceMap = ServiceMapHelper::serviceMap(queryHandlers: $queryHandlers);

        // -- Act
        $queryHandler = $serviceMap->getQueryHandler(GetTasksQueryHandler::class);

        // -- Assert
        self::assertSame($getTasksQueryHandler, $queryHandler);
    }

    /**
     * @test
     * @covers ::getQueryHandler
     */
    public function get_query_handler_fails_if_not_available(): void
    {
        // -- Assert
        $this->expectException(ConfiguredQueryHandlerNotAvailable::class);

        // -- Arrange
        $serviceMap = ServiceMapHelper::serviceMap(queryHandlers: []);

        // -- Act
        $serviceMap->getQueryHandler(GetTasksQueryHandler::class);
    }

    // -- Response constructors

    /**
     * @test
     * @covers ::getResponseConstructor
     * @covers ::__construct
     */
    public function get_response_constructor_works_with_response_constructor_class(): void
    {
        // -- Arrange
        $responseConstructors = [
            new EmptyResponseConstructor(),
            $emptyJsonResponseConstructor = new EmptyJsonResponseConstructor(),
        ];
        $serviceMap = ServiceMapHelper::serviceMap(responseConstructors: $responseConstructors);

        // -- Act
        $responseConstructor = $serviceMap->getResponseConstructor(
            EmptyJsonResponseConstructor::class,
            null,
        );

        // -- Assert
        self::assertSame($emptyJsonResponseConstructor, $responseConstructor);
    }

    /**
     * @test
     * @covers ::getResponseConstructor
     */
    public function get_response_constructor_works_with_default_response_constructor_class(): void
    {
        // -- Arrange
        $responseConstructors = [
            new EmptyResponseConstructor(),
            $defaultResponseConstructor = new EmptyJsonResponseConstructor(),
        ];
        $serviceMap = ServiceMapHelper::serviceMap(responseConstructors: $responseConstructors);

        // -- Act
        $responseConstructor = $serviceMap->getResponseConstructor(
            null,
            EmptyJsonResponseConstructor::class,
        );

        // -- Assert
        self::assertSame($defaultResponseConstructor, $responseConstructor);
    }

    /**
     * @test
     * @covers ::getResponseConstructor
     */
    public function get_response_constructor_fails_when_response_constructor_class_is_not_available(): void
    {
        // -- Assert
        $this->expectException(ConfiguredResponseConstructorNotAvailable::class);

        // -- Arrange
        $responseConstructors = [
            new EmptyJsonResponseConstructor(),
        ];
        $serviceMap = ServiceMapHelper::serviceMap(responseConstructors: $responseConstructors);

        // -- Act
        $serviceMap->getResponseConstructor(EmptyResponseConstructor::class, null);
    }

    /**
     * @test
     * @covers ::getResponseConstructor
     */
    public function get_response_constructor_fails_when_default_response_constructor_class_is_not_available(): void
    {
        // -- Assert
        $this->expectException(ConfiguredResponseConstructorNotAvailable::class);

        // -- Arrange
        $responseConstructors = [
            new EmptyResponseConstructor(),
        ];
        $serviceMap = ServiceMapHelper::serviceMap(responseConstructors: $responseConstructors);

        // -- Act
        $serviceMap->getResponseConstructor(null, EmptyJsonResponseConstructor::class);
    }

    /**
     * @test
     * @covers ::getResponseConstructor
     */
    public function get_response_constructor_fails_when_no_response_constructor_class_and_default_response_constructor_class_is_defined(): void
    {
        // -- Assert
        $this->expectException(ResponseConstructorOrDefaultResponseConstructorMustBeConfigured::class);

        // -- Arrange
        $serviceMap = ServiceMapHelper::serviceMap(responseConstructors: []);

        // -- Act
        $serviceMap->getResponseConstructor(null, null);
    }
}
