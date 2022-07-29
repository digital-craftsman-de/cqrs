<?php

declare(strict_types=1);

namespace DigitalCraftsman\CQRS\DTO;

use DigitalCraftsman\CQRS\Command\Command;
use DigitalCraftsman\CQRS\Command\CommandHandlerInterface;
use DigitalCraftsman\CQRS\DTOConstructor\DTOConstructorInterface;
use DigitalCraftsman\CQRS\DTOValidator\DTOValidatorInterface;
use DigitalCraftsman\CQRS\HandlerWrapper\HandlerWrapperInterface;
use DigitalCraftsman\CQRS\Query\Query;
use DigitalCraftsman\CQRS\Query\QueryHandlerInterface;
use DigitalCraftsman\CQRS\RequestDataTransformer\RequestDataTransformerInterface;
use DigitalCraftsman\CQRS\RequestDecoder\RequestDecoderInterface;
use DigitalCraftsman\CQRS\RequestValidator\RequestValidatorInterface;
use DigitalCraftsman\CQRS\ResponseConstructor\ResponseConstructorInterface;

/**
 * The symfony routing does not support the usage of objects as it has to dump them into a php file for caching. Therefore, we create an
 * object and convert into and from an array.
 *
 * @codeCoverageIgnore
 */
final class Configuration
{
    /**
     * @psalm-param class-string<Command>|class-string<Query> $dtoClass
     * @psalm-param class-string<CommandHandlerInterface>|class-string<QueryHandlerInterface> $handlerClass
     * @psalm-param array<int, class-string<RequestValidatorInterface>>|null $requestValidatorClasses
     * @psalm-param class-string<RequestDecoderInterface>|null $requestDecoderClass
     * @psalm-param array<int, class-string<RequestDataTransformerInterface>>|null $requestDataTransformerClasses
     * @psalm-param class-string<DTOConstructorInterface>|null $dtoConstructorClass
     * @psalm-param array<int, class-string<DTOValidatorInterface>>|null $dtoValidatorClasses
     * @psalm-param array<int, HandlerWrapperConfiguration>|null $handlerWrapperConfigurations
     * @psalm-param class-string<ResponseConstructorInterface>|null $responseConstructorClass
     */
    private function __construct(
        public string $dtoClass,
        public string $handlerClass,
        public ?array $requestValidatorClasses = null,
        public ?string $requestDecoderClass = null,
        public ?array $requestDataTransformerClasses = null,
        public ?string $dtoConstructorClass = null,
        public ?array $dtoValidatorClasses = null,
        public ?array $handlerWrapperConfigurations = null,
        public ?string $responseConstructorClass = null,
    ) {
    }

    /**
     * @psalm-param class-string<Command>|class-string<Query> $dtoClass
     * @psalm-param class-string<CommandHandlerInterface>|class-string<QueryHandlerInterface> $handlerClass
     * @psalm-param array<int, class-string<RequestValidatorInterface>>|null $requestValidatorClasses
     * @psalm-param class-string<RequestDecoderInterface>|null $requestDecoderClass
     * @psalm-param array<int, class-string<RequestDataTransformerInterface>>|null $requestDataTransformerClasses
     * @psalm-param class-string<DTOConstructorInterface>|null $dtoConstructorClass
     * @psalm-param array<int, class-string<DTOValidatorInterface>>|null $dtoValidatorClasses
     * @psalm-param array<int, HandlerWrapperConfiguration>|null $handlerWrapperConfigurations
     * @psalm-param class-string<ResponseConstructorInterface>|null $responseConstructorClass
     */
    public static function routePayload(
        string $dtoClass,
        string $handlerClass,
        ?array $requestValidatorClasses = null,
        ?string $requestDecoderClass = null,
        ?array $requestDataTransformerClasses = null,
        ?string $dtoConstructorClass = null,
        ?array $dtoValidatorClasses = null,
        ?array $handlerWrapperConfigurations = null,
        ?string $responseConstructorClass = null,
    ): array {
        $configuration = new self(
            $dtoClass,
            $handlerClass,
            $requestValidatorClasses,
            $requestDecoderClass,
            $requestDataTransformerClasses,
            $dtoConstructorClass,
            $dtoValidatorClasses,
            $handlerWrapperConfigurations,
            $responseConstructorClass,
        );

        return $configuration->toRoutePayload();
    }

    /**
     * @psalm-param array{
     *   dtoClass: class-string<Command>|class-string<Query>,
     *   handlerClass: class-string<CommandHandlerInterface>|class-string<QueryHandlerInterface>,
     *   requestValidatorClasses: array<int, class-string<RequestValidatorInterface>>|null,
     *   requestDecoderClass: class-string<RequestDecoderInterface>|null,
     *   requestDataTransformerClasses: array<int, class-string<RequestDataTransformerInterface>>|null,
     *   dtoConstructorClass: class-string<DTOConstructorInterface>|null,
     *   dtoValidatorClasses: array<int, class-string<DTOValidatorInterface>>|null,
     *   handlerWrapperConfigurations: array<int, array{
     *     handlerWrapperClass: class-string<HandlerWrapperInterface>,
     *     parameters: array<int, string|int|float|bool>|string|int|float|bool|null,
     *   }>|null,
     *   responseConstructorClass: class-string<ResponseConstructorInterface>|null,
     * } $routePayload
     */
    public static function fromRoutePayload(array $routePayload): self
    {
        $handlerWrapperConfigurations = null;
        if ($routePayload['handlerWrapperConfigurations'] !== null) {
            $handlerWrapperConfigurations = array_map(
                /**
                 * @psalm-param array{
                 *   handlerWrapperClass: class-string<HandlerWrapperInterface>,
                 *   parameters: array<int, string|int|float|bool>|string|int|float|bool|null,
                 * } $handlerWrapperRoutePayload
                 */
                static fn (array $handlerWrapperRoutePayload) => HandlerWrapperConfiguration::fromRoutePayload($handlerWrapperRoutePayload),
                $routePayload['handlerWrapperConfigurations'],
            );
        }

        return new self(
            $routePayload['dtoClass'],
            $routePayload['handlerClass'],
            $routePayload['requestValidatorClasses'],
            $routePayload['requestDecoderClass'],
            $routePayload['requestDataTransformerClasses'],
            $routePayload['dtoConstructorClass'],
            $routePayload['dtoValidatorClasses'],
            $handlerWrapperConfigurations,
            $routePayload['responseConstructorClass'],
        );
    }

    /**
     * @psalm-return array{
     *   dtoClass: class-string<Command>|class-string<Query>,
     *   handlerClass: class-string<CommandHandlerInterface>|class-string<QueryHandlerInterface>,
     *   requestValidatorClasses: array<int, class-string<RequestValidatorInterface>>|null,
     *   requestDecoderClass: class-string<RequestDecoderInterface>|null,
     *   requestDataTransformerClasses: array<int, class-string<RequestDataTransformerInterface>>|null,
     *   dtoConstructorClass: class-string<DTOConstructorInterface>|null,
     *   dtoValidatorClasses: array<int, class-string<DTOValidatorInterface>>|null,
     *   handlerWrapperConfigurations: array<int, array{
     *     handlerWrapperClass: class-string<HandlerWrapperInterface>,
     *     parameters: array<int, string|int|float|bool>|string|int|float|bool|null,
     *   }>|null,
     *   responseConstructorClass: class-string<ResponseConstructorInterface>|null,
     * }
     */
    private function toRoutePayload(): array
    {
        return [
            'dtoClass' => $this->dtoClass,
            'handlerClass' => $this->handlerClass,
            'requestValidatorClasses' => $this->requestValidatorClasses,
            'requestDecoderClass' => $this->requestDecoderClass,
            'requestDataTransformerClasses' => $this->requestDataTransformerClasses,
            'dtoConstructorClass' => $this->dtoConstructorClass,
            'dtoValidatorClasses' => $this->dtoValidatorClasses,
            'handlerWrapperConfigurations' => $this->handlerWrapperConfigurations !== null
                ? array_map(
                    static fn (HandlerWrapperConfiguration $handlerWrapperConfiguration) => $handlerWrapperConfiguration->toRoutePayload(),
                    $this->handlerWrapperConfigurations,
                )
                : null,
            'responseConstructorClass' => $this->responseConstructorClass,
        ];
    }
}
