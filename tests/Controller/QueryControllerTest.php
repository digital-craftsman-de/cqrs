<?php

declare(strict_types=1);

namespace DigitalCraftsman\CQRS\Controller;

use DigitalCraftsman\CQRS\DTO\Configuration;
use DigitalCraftsman\CQRS\DTO\HandlerWrapperConfiguration;
use DigitalCraftsman\CQRS\DTOConstructor\SerializerDTOConstructor;
use DigitalCraftsman\CQRS\RequestDecoder\JsonRequestDecoder;
use DigitalCraftsman\CQRS\RequestValidator\GuardAgainstFileWithVirusRequestValidator;
use DigitalCraftsman\CQRS\ResponseConstructor\SerializerJsonResponseConstructor;
use DigitalCraftsman\CQRS\Test\Application\AddActionIdRequestDataTransformer;
use DigitalCraftsman\CQRS\Test\Application\UserIdValidator;
use DigitalCraftsman\CQRS\Test\Domain\Tasks\ReadSide\GetTasks\Exception\TasksNotAccessible;
use DigitalCraftsman\CQRS\Test\Domain\Tasks\ReadSide\GetTasks\FailingGetTasksQueryHandler;
use DigitalCraftsman\CQRS\Test\Domain\Tasks\ReadSide\GetTasks\GetTasksHandlerWrapper;
use DigitalCraftsman\CQRS\Test\Domain\Tasks\ReadSide\GetTasks\GetTasksQuery;
use DigitalCraftsman\CQRS\Test\Domain\Tasks\ReadSide\GetTasks\GetTasksQueryHandler;
use DigitalCraftsman\CQRS\Test\Entity\Task;
use DigitalCraftsman\CQRS\Test\Helper\ServiceMapHelper;
use DigitalCraftsman\CQRS\Test\Repository\TasksInMemoryRepository;
use DigitalCraftsman\CQRS\Test\Utility\LockSimulator;
use DigitalCraftsman\CQRS\Test\Utility\SecuritySimulator;
use DigitalCraftsman\CQRS\Test\Utility\VirusScannerSimulator;
use DigitalCraftsman\CQRS\Test\ValueObject\TaskId;
use DigitalCraftsman\CQRS\Test\ValueObject\UserId;
use DigitalCraftsman\Ids\Serializer\IdNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

/** @coversDefaultClass \DigitalCraftsman\CQRS\Controller\QueryController */
final class QueryControllerTest extends TestCase
{
    /**
     * @test
     * @covers ::handle
     */
    public function query_controller_works_with_all_components(): void
    {
        // -- Arrange

        $authenticatedUserId = UserId::generateRandom();
        $serializer = new Serializer([
            new ArrayDenormalizer(),
            new IdNormalizer(),
            new PropertyNormalizer(
                null,
                null,
                new PropertyInfoExtractor([], [new PhpDocExtractor(), new ReflectionExtractor()]),
            ),
        ], [
            new JsonEncoder(),
        ]);
        $tasksInMemoryRepository = new TasksInMemoryRepository([
            new Task(
                TaskId::generateRandom(),
                $authenticatedUserId,
                'Finish all tests',
            ),
            new Task(
                TaskId::generateRandom(),
                $authenticatedUserId,
                'Improve documentation',
            ),
        ]);
        $lockSimulator = new LockSimulator();

        $securitySimulator = new SecuritySimulator();
        $securitySimulator->fixateAuthenticatedUserId($authenticatedUserId);

        $controller = new QueryController(
            ServiceMapHelper::serviceMap(
                requestValidators: [
                    new GuardAgainstFileWithVirusRequestValidator(new VirusScannerSimulator()),
                ],
                requestDecoders: [
                    new JsonRequestDecoder(),
                ],
                requestDataTransformers: [
                    new AddActionIdRequestDataTransformer(),
                ],
                dtoConstructors: [
                    new SerializerDTOConstructor($serializer),
                ],
                dtoValidators: [
                    new UserIdValidator($securitySimulator),
                ],
                handlerWrappers: [
                    new GetTasksHandlerWrapper($lockSimulator),
                ],
                queryHandlers: [
                    new GetTasksQueryHandler($tasksInMemoryRepository),
                ],
                responseConstructors: [
                    new SerializerJsonResponseConstructor($serializer, []),
                ],
            ),
            [],
            JsonRequestDecoder::class,
            [],
            SerializerDTOConstructor::class,
            [],
            [],
            SerializerJsonResponseConstructor::class,
        );

        $content = [
            'userId' => (string) $authenticatedUserId,
        ];

        $request = new Request(content: json_encode($content, JSON_THROW_ON_ERROR));
        $routePayload = Configuration::routePayload(
            dtoClass: GetTasksQuery::class,
            handlerClass: GetTasksQueryHandler::class,
            requestValidatorClasses: [
                GuardAgainstFileWithVirusRequestValidator::class,
            ],
            requestDataTransformerClasses: [
                AddActionIdRequestDataTransformer::class,
            ],
            dtoValidatorClasses: [
                UserIdValidator::class,
            ],
            handlerWrapperConfigurations: [
                new HandlerWrapperConfiguration(GetTasksHandlerWrapper::class),
            ],
        );

        // -- Act
        $response = $controller->handle($request, $routePayload);

        // -- Assert
        /** @var string $body */
        $body = $response->getContent();
        $tasks = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(2, $tasks);
        self::assertCount(1, $lockSimulator->lockedActions);
        self::assertCount(1, $lockSimulator->unlockedActions);
    }

    /**
     * @test
     * @covers ::handle
     */
    public function query_controller_works_with_handler_wrapper_in_catch_case_with_multiple_catches(): void
    {
        // -- Assert
        $this->expectException(TasksNotAccessible::class);

        // -- Arrange

        $authenticatedUserId = UserId::generateRandom();
        $serializer = new Serializer([
            new ArrayDenormalizer(),
            new IdNormalizer(),
            new PropertyNormalizer(
                null,
                null,
                new PropertyInfoExtractor([], [new PhpDocExtractor(), new ReflectionExtractor()]),
            ),
        ], [
            new JsonEncoder(),
        ]);
        $lockSimulator = new LockSimulator();

        $securitySimulator = new SecuritySimulator();
        $securitySimulator->fixateAuthenticatedUserId($authenticatedUserId);

        $controller = new QueryController(
            ServiceMapHelper::serviceMap(
                requestDecoders: [
                    new JsonRequestDecoder(),
                ],
                requestDataTransformers: [
                    new AddActionIdRequestDataTransformer(),
                ],
                dtoConstructors: [
                    new SerializerDTOConstructor($serializer),
                ],
                dtoValidators: [
                    new UserIdValidator($securitySimulator),
                ],
                handlerWrappers: [
                    new GetTasksHandlerWrapper($lockSimulator),
                ],
                queryHandlers: [
                    new FailingGetTasksQueryHandler(),
                ],
                responseConstructors: [
                    new SerializerJsonResponseConstructor($serializer, []),
                ],
            ),
            [],
            JsonRequestDecoder::class,
            [],
            SerializerDTOConstructor::class,
            [],
            [],
            SerializerJsonResponseConstructor::class,
        );

        $content = [
            'userId' => (string) $authenticatedUserId,
        ];

        $request = new Request(content: json_encode($content, JSON_THROW_ON_ERROR));
        $routePayload = Configuration::routePayload(
            dtoClass: GetTasksQuery::class,
            handlerClass: FailingGetTasksQueryHandler::class,
            requestDataTransformerClasses: [
                AddActionIdRequestDataTransformer::class,
            ],
            dtoValidatorClasses: [
                UserIdValidator::class,
            ],
            handlerWrapperConfigurations: [
                new HandlerWrapperConfiguration(GetTasksHandlerWrapper::class),
            ],
        );

        // -- Act
        $controller->handle($request, $routePayload);
    }
}
