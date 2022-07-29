<?php

declare(strict_types=1);

namespace DigitalCraftsman\CQRS\Controller;

use DigitalCraftsman\CQRS\DTO\Configuration;
use DigitalCraftsman\CQRS\DTO\HandlerWrapperConfiguration;
use DigitalCraftsman\CQRS\DTOConstructor\SerializerDTOConstructor;
use DigitalCraftsman\CQRS\RequestDecoder\JsonRequestDecoder;
use DigitalCraftsman\CQRS\RequestValidator\GuardAgainstFileWithVirusRequestValidator;
use DigitalCraftsman\CQRS\ResponseConstructor\EmptyJsonResponseConstructor;
use DigitalCraftsman\CQRS\Test\Application\ConnectionTransactionWrapper;
use DigitalCraftsman\CQRS\Test\Application\SilentExceptionWrapper;
use DigitalCraftsman\CQRS\Test\Application\UserIdValidator;
use DigitalCraftsman\CQRS\Test\Domain\News\WriteSide\CreateNewsArticle\CreateNewsArticleCommand;
use DigitalCraftsman\CQRS\Test\Domain\News\WriteSide\CreateNewsArticle\CreateNewsArticleCommandHandler;
use DigitalCraftsman\CQRS\Test\Domain\News\WriteSide\CreateNewsArticle\CreateNewsArticleHandlerWrapper;
use DigitalCraftsman\CQRS\Test\Domain\News\WriteSide\CreateNewsArticle\CreateNewsArticleRequestDataTransformer;
use DigitalCraftsman\CQRS\Test\Domain\News\WriteSide\CreateNewsArticle\Exception\NewsArticleAlreadyExists;
use DigitalCraftsman\CQRS\Test\Domain\News\WriteSide\CreateNewsArticle\FailingCreateNewsArticleCommandHandler;
use DigitalCraftsman\CQRS\Test\Helper\ServiceMapHelper;
use DigitalCraftsman\CQRS\Test\Repository\NewsArticleInMemoryRepository;
use DigitalCraftsman\CQRS\Test\Utility\ConnectionSimulator;
use DigitalCraftsman\CQRS\Test\Utility\LockSimulator;
use DigitalCraftsman\CQRS\Test\Utility\SecuritySimulator;
use DigitalCraftsman\CQRS\Test\Utility\VirusScannerSimulator;
use DigitalCraftsman\CQRS\Test\ValueObject\UserId;
use DigitalCraftsman\Ids\Serializer\IdNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

/** @coversDefaultClass \DigitalCraftsman\CQRS\Controller\CommandController */
final class CommandControllerTest extends TestCase
{
    /**
     * @test
     * @covers ::handle
     */
    public function command_controller_works_with_all_components(): void
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
        $newsArticleInMemoryRepository = new NewsArticleInMemoryRepository();
        $lockSimulator = new LockSimulator();
        $securitySimulator = new SecuritySimulator();
        $securitySimulator->fixateAuthenticatedUserId($authenticatedUserId);

        $controller = new CommandController(
            ServiceMapHelper::serviceMap(
                requestValidators: [
                    new GuardAgainstFileWithVirusRequestValidator(new VirusScannerSimulator()),
                ],
                requestDecoders: [
                    new JsonRequestDecoder(),
                ],
                requestDataTransformers: [
                    new CreateNewsArticleRequestDataTransformer(),
                ],
                dtoConstructors: [
                    new SerializerDTOConstructor($serializer),
                ],
                dtoValidators: [
                    new UserIdValidator($securitySimulator),
                ],
                handlerWrappers: [
                    new CreateNewsArticleHandlerWrapper($lockSimulator),
                ],
                commandHandlers: [
                    new CreateNewsArticleCommandHandler($newsArticleInMemoryRepository),
                ],
                responseConstructors: [
                    new EmptyJsonResponseConstructor(),
                ],
            ),
            [],
            JsonRequestDecoder::class,
            [],
            SerializerDTOConstructor::class,
            [],
            [],
            EmptyJsonResponseConstructor::class,
        );

        $content = [
            'userId' => (string) $authenticatedUserId,
            'title' => 'New feature released',
            'content' => '<p>We just released <strong>a new feature</strong> <em>but this em is not allowed</em></p>',
            'isPublished' => false,
        ];

        $request = new Request(content: json_encode($content, JSON_THROW_ON_ERROR));
        $routePayload = Configuration::routePayload(
            dtoClass: CreateNewsArticleCommand::class,
            handlerClass: CreateNewsArticleCommandHandler::class,
            requestValidatorClasses: [
                GuardAgainstFileWithVirusRequestValidator::class,
            ],
            requestDataTransformerClasses: [
                CreateNewsArticleRequestDataTransformer::class,
            ],
            dtoValidatorClasses: [
                UserIdValidator::class,
            ],
            handlerWrapperConfigurations: [
                new HandlerWrapperConfiguration(CreateNewsArticleHandlerWrapper::class),
            ],
        );

        // -- Act
        $controller->handle($request, $routePayload);

        // -- Assert
        self::assertCount(1, $newsArticleInMemoryRepository->newsArticles);
        self::assertCount(1, $lockSimulator->lockedActions);
        self::assertCount(1, $lockSimulator->unlockedActions);
    }

    /**
     * @test
     * @covers ::handle
     */
    public function command_controller_works_with_handler_wrapper_in_catch_case_with_multiple_catches(): void
    {
        // -- Arrange

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
        $connectionSimulator = new ConnectionSimulator();

        $controller = new CommandController(
            ServiceMapHelper::serviceMap(
                requestDecoders: [
                    new JsonRequestDecoder(),
                ],
                dtoConstructors: [
                    new SerializerDTOConstructor($serializer),
                ],
                handlerWrappers: [
                    new SilentExceptionWrapper(),
                    new ConnectionTransactionWrapper($connectionSimulator),
                ],
                commandHandlers: [
                    new FailingCreateNewsArticleCommandHandler(),
                ],
                responseConstructors: [
                    new EmptyJsonResponseConstructor(),
                ],
            ),
            [],
            JsonRequestDecoder::class,
            [],
            SerializerDTOConstructor::class,
            [],
            [],
            EmptyJsonResponseConstructor::class,
        );

        $content = [
            'userId' => (string) UserId::generateRandom(),
            'title' => 'New feature released',
            'content' => '<p>We just released <strong>a new feature</strong> <em>but this em is not allowed</em></p>',
            'isPublished' => false,
        ];

        $request = new Request(content: json_encode($content, JSON_THROW_ON_ERROR));
        $routePayload = Configuration::routePayload(
            dtoClass: CreateNewsArticleCommand::class,
            handlerClass: FailingCreateNewsArticleCommandHandler::class,
            handlerWrapperConfigurations: [
                new HandlerWrapperConfiguration(SilentExceptionWrapper::class, [
                    NewsArticleAlreadyExists::class,
                ]),
                new HandlerWrapperConfiguration(ConnectionTransactionWrapper::class),
            ],
        );

        // -- Act
        $response = $controller->handle($request, $routePayload);

        // -- Assert
        self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        self::assertFalse($connectionSimulator->hasActiveTransaction);
        self::assertFalse($connectionSimulator->hasCommitted);
    }

    /**
     * @test
     * @covers ::handle
     */
    public function command_controller_works_with_handler_wrapper_with_catch_and_throw(): void
    {
        // -- Assert
        $this->expectException(NewsArticleAlreadyExists::class);

        // -- Arrange

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
        $connectionSimulator = new ConnectionSimulator();

        $controller = new CommandController(
            ServiceMapHelper::serviceMap(
                requestDecoders: [
                    new JsonRequestDecoder(),
                ],
                dtoConstructors: [
                    new SerializerDTOConstructor($serializer),
                ],
                handlerWrappers: [
                    new ConnectionTransactionWrapper($connectionSimulator),
                ],
                commandHandlers: [
                    new FailingCreateNewsArticleCommandHandler(),
                ],
                responseConstructors: [
                    new EmptyJsonResponseConstructor(),
                ],
            ),
            [],
            JsonRequestDecoder::class,
            [],
            SerializerDTOConstructor::class,
            [],
            [],
            EmptyJsonResponseConstructor::class,
        );

        $content = [
            'userId' => (string) UserId::generateRandom(),
            'title' => 'New feature released',
            'content' => '<p>We just released <strong>a new feature</strong> <em>but this em is not allowed</em></p>',
            'isPublished' => false,
        ];

        $request = new Request(content: json_encode($content, JSON_THROW_ON_ERROR));
        $routePayload = Configuration::routePayload(
            dtoClass: CreateNewsArticleCommand::class,
            handlerClass: FailingCreateNewsArticleCommandHandler::class,
            handlerWrapperConfigurations: [
                new HandlerWrapperConfiguration(ConnectionTransactionWrapper::class),
            ],
        );

        // -- Act
        $controller->handle($request, $routePayload);
    }
}
