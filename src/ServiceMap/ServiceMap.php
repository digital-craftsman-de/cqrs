<?php

declare(strict_types=1);

namespace DigitalCraftsman\CQRS\ServiceMap;

use DigitalCraftsman\CQRS\Command\CommandHandlerInterface;
use DigitalCraftsman\CQRS\DTOConstructor\DTOConstructorInterface;
use DigitalCraftsman\CQRS\DTOValidator\DTOValidatorInterface;
use DigitalCraftsman\CQRS\HandlerWrapper\HandlerWrapperInterface;
use DigitalCraftsman\CQRS\Query\QueryHandlerInterface;
use DigitalCraftsman\CQRS\RequestDataTransformer\RequestDataTransformerInterface;
use DigitalCraftsman\CQRS\RequestDecoder\RequestDecoderInterface;
use DigitalCraftsman\CQRS\RequestValidator\RequestValidatorInterface;
use DigitalCraftsman\CQRS\ResponseConstructor\ResponseConstructorInterface;
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
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;

/** @internal */
final class ServiceMap
{
    public function __construct(
        private readonly ServiceProviderInterface $requestValidators,
        private readonly ServiceProviderInterface $requestDecoders,
        private readonly ServiceProviderInterface $requestDataTransformers,
        private readonly ServiceProviderInterface $dtoConstructors,
        private readonly ServiceProviderInterface $dtoValidators,
        private readonly ServiceProviderInterface $handlerWrappers,
        private readonly ServiceProviderInterface $commandHandlers,
        private readonly ServiceProviderInterface $queryHandlers,
        private readonly ServiceProviderInterface $responseConstructors,
    ) {
    }

    /**
     * @param array<array-key, class-string<RequestValidatorInterface>>|null $requestValidatorClasses
     * @param array<array-key, class-string<RequestValidatorInterface>>|null $defaultRequestValidatorClasses
     *
     * @return array<array-key, RequestValidatorInterface>
     */
    public function getRequestValidators(?array $requestValidatorClasses, ?array $defaultRequestValidatorClasses): array
    {
        if ($requestValidatorClasses === null && $defaultRequestValidatorClasses === null) {
            return [];
        }

        $selectedRequestValidatorClasses = $requestValidatorClasses ?? $defaultRequestValidatorClasses;

        return array_map(
            fn (string $requestValidatorClass) => $this->requestValidators->has($requestValidatorClass)
                ? $this->requestValidators->get($requestValidatorClass)
                : throw new ConfiguredRequestValidatorNotAvailable($requestValidatorClass),
            $selectedRequestValidatorClasses,
        );
    }

    /**
     * @param class-string<RequestDecoderInterface>|null $requestDecoderClass
     * @param class-string<RequestDecoderInterface>|null $defaultRequestDecoderClass
     */
    public function getRequestDecoder(?string $requestDecoderClass, ?string $defaultRequestDecoderClass): RequestDecoderInterface
    {
        $selectedRequestDecoderClass = $requestDecoderClass ?? $defaultRequestDecoderClass;
        if ($selectedRequestDecoderClass === null) {
            throw new RequestDecoderOrDefaultRequestDecoderMustBeConfigured();
        }

        try {
            return $this->requestDecoders->get($selectedRequestDecoderClass);
        } catch (NotFoundExceptionInterface) {
            throw new ConfiguredRequestDecoderNotAvailable($selectedRequestDecoderClass);
        }
    }

    /**
     * @param array<array-key, class-string<RequestDataTransformerInterface>>|null $requestDataTransformerClasses
     * @param array<array-key, class-string<RequestDataTransformerInterface>>|null $defaultRequestDataTransformerClasses
     *
     * @return array<array-key, RequestDataTransformerInterface>
     */
    public function getRequestDataTransformers(?array $requestDataTransformerClasses, ?array $defaultRequestDataTransformerClasses): array
    {
        if ($requestDataTransformerClasses === null && $defaultRequestDataTransformerClasses === null) {
            return [];
        }

        $selectedRequestDataTransformerClasses = $requestDataTransformerClasses ?? $defaultRequestDataTransformerClasses;

        return array_map(
            fn (string $requestDataTransformerClass) => $this->requestDataTransformers->has($requestDataTransformerClass)
                ? $this->requestDataTransformers->get($requestDataTransformerClass)
                : throw new ConfiguredRequestDataTransformerNotAvailable($requestDataTransformerClass),
            $selectedRequestDataTransformerClasses,
        );
    }

    /**
     * @param class-string<DTOConstructorInterface>|null $dtoConstructorClass
     * @param class-string<DTOConstructorInterface>|null $defaultDTOConstructorClass
     */
    public function getDTOConstructor(?string $dtoConstructorClass, ?string $defaultDTOConstructorClass): DTOConstructorInterface
    {
        $selectedDTOConstructorClass = $dtoConstructorClass ?? $defaultDTOConstructorClass;
        if ($selectedDTOConstructorClass === null) {
            throw new DTOConstructorOrDefaultDTOConstructorMustBeConfigured();
        }

        try {
            return $this->dtoConstructors->get($selectedDTOConstructorClass);
        } catch (ContainerExceptionInterface) {
            throw new ConfiguredDTOConstructorNotAvailable($selectedDTOConstructorClass);
        }
    }

    /**
     * @param array<array-key, class-string<DTOValidatorInterface>>|null $dtoValidatorClasses
     * @param array<array-key, class-string<DTOValidatorInterface>>|null $defaultDTOValidatorClasses
     *
     * @return array<array-key, DTOValidatorInterface>
     */
    public function getDTOValidators(?array $dtoValidatorClasses, ?array $defaultDTOValidatorClasses): array
    {
        if ($dtoValidatorClasses === null && $defaultDTOValidatorClasses === null) {
            return [];
        }

        $selectedDTOValidatorClasses = $dtoValidatorClasses ?? $defaultDTOValidatorClasses;

        return array_map(
            fn (string $dtoValidatorClass) => $this->dtoValidators->has($dtoValidatorClass)
                ? $this->dtoValidators->get($dtoValidatorClass)
                : throw new ConfiguredDTOValidatorNotAvailable($dtoValidatorClass),
            $selectedDTOValidatorClasses,
        );
    }

    /**
     * @param array<class-string<HandlerWrapperInterface>, scalar|array<array-key, scalar|null>|null>|null $handlerWrapperClasses
     * @param array<class-string<HandlerWrapperInterface>, scalar|array<array-key, scalar|null>|null>|null $defaultHandlerWrapperClasses
     *
     * @return array<class-string<HandlerWrapperInterface>, scalar|array<array-key, scalar|null>|null>
     */
    public function getHandlerWrapperClasses(
        ?array $handlerWrapperClasses,
        ?array $defaultHandlerWrapperClasses,
    ): array {
        if ($handlerWrapperClasses === null
            && $defaultHandlerWrapperClasses === null
        ) {
            return [];
        }

        $mergedHandlerWrappers = $defaultHandlerWrapperClasses ?? [];
        if ($handlerWrapperClasses !== null) {
            foreach ($handlerWrapperClasses as $handlerWrapperClass => $parameters) {
                $mergedHandlerWrappers[$handlerWrapperClass] = $parameters;
            }
        }

        return $mergedHandlerWrappers;
    }

    public function getHandlerWrapper(string $handlerWrapperClass): HandlerWrapperInterface
    {
        try {
            return $this->handlerWrappers->get($handlerWrapperClass);
        } catch (ContainerExceptionInterface) {
            throw new ConfiguredHandlerWrapperNotAvailable($handlerWrapperClass);
        }
    }

    /** @param class-string<CommandHandlerInterface> $handlerClass */
    public function getCommandHandler(string $handlerClass): CommandHandlerInterface
    {
        try {
            return $this->commandHandlers->get($handlerClass);
        } catch (ContainerExceptionInterface) {
            throw new ConfiguredCommandHandlerNotAvailable($handlerClass);
        }
    }

    /** @param class-string<QueryHandlerInterface> $handlerClass */
    public function getQueryHandler(string $handlerClass): QueryHandlerInterface
    {
        try {
            return $this->queryHandlers->get($handlerClass);
        } catch (ContainerExceptionInterface) {
            throw new ConfiguredQueryHandlerNotAvailable($handlerClass);
        }
    }

    /** @param class-string<ResponseConstructorInterface> $responseConstructorClass */
    public function getResponseConstructor(
        ?string $responseConstructorClass,
        ?string $defaultResponseConstructorClass,
    ): ResponseConstructorInterface {
        $selectedResponseConstructorClass = $responseConstructorClass ?? $defaultResponseConstructorClass;
        if ($selectedResponseConstructorClass === null) {
            throw new ResponseConstructorOrDefaultResponseConstructorMustBeConfigured();
        }

        try {
            return $this->responseConstructors->get($selectedResponseConstructorClass);
        } catch (ContainerExceptionInterface) {
            throw new ConfiguredResponseConstructorNotAvailable($selectedResponseConstructorClass);
        }
    }
}
