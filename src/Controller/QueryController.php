<?php

declare(strict_types=1);

namespace DigitalCraftsman\CQRS\Controller;

use DigitalCraftsman\CQRS\DTO\Configuration;
use DigitalCraftsman\CQRS\DTOConstructor\DTOConstructorInterface;
use DigitalCraftsman\CQRS\DTOValidator\DTOValidatorInterface;
use DigitalCraftsman\CQRS\HandlerWrapper\DTO\HandlerWrapperStep;
use DigitalCraftsman\CQRS\HandlerWrapper\HandlerWrapperInterface;
use DigitalCraftsman\CQRS\Query\Query;
use DigitalCraftsman\CQRS\RequestDataTransformer\RequestDataTransformerInterface;
use DigitalCraftsman\CQRS\RequestDecoder\RequestDecoderInterface;
use DigitalCraftsman\CQRS\RequestValidator\RequestValidatorInterface;
use DigitalCraftsman\CQRS\ResponseConstructor\ResponseConstructorInterface;
use DigitalCraftsman\CQRS\ServiceMap\ServiceMap;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class QueryController extends AbstractController
{
    /**
     * @psalm-param array<int, class-string<RequestValidatorInterface>>|null $defaultRequestValidatorClasses
     * @psalm-param class-string<RequestDecoderInterface>|null $defaultRequestDecoderClass
     * @psalm-param array<int, class-string<RequestDataTransformerInterface>>|null $defaultRequestDataTransformerClasses
     * @psalm-param class-string<DTOConstructorInterface>|null $defaultDTOConstructorClass
     * @psalm-param array<int, class-string<DTOValidatorInterface>>|null $defaultDTOValidatorClasses
     * @psalm-param array<int, class-string<HandlerWrapperInterface>>|null $defaultHandlerWrapperClasses
     * @psalm-param class-string<ResponseConstructorInterface>|null $defaultResponseConstructorClass
     *
     * @codeCoverageIgnore
     */
    public function __construct(
        private ServiceMap $serviceMap,
        private ?array $defaultRequestValidatorClasses,
        private ?string $defaultRequestDecoderClass,
        private ?array $defaultRequestDataTransformerClasses,
        private ?string $defaultDTOConstructorClass,
        private ?array $defaultDTOValidatorClasses,
        private ?array $defaultHandlerWrapperClasses,
        private ?string $defaultResponseConstructorClass,
    ) {
    }

    /** We don't type the $routePayload because we never trigger it manually, it's only supplied through Symfony. */
    public function handle(
        Request $request,
        array $routePayload,
    ): Response {
        /** @psalm-suppress MixedArgumentTypeCoercion */
        $configuration = Configuration::fromRoutePayload($routePayload);

        // Validate request
        $requestValidators = $this->serviceMap->getRequestValidators(
            $configuration->requestValidatorClasses,
            $this->defaultRequestValidatorClasses,
        );
        foreach ($requestValidators as $requestValidator) {
            $requestValidator->validateRequest($request);
        }

        // Get request data from request
        $requestDecoder = $this->serviceMap->getRequestDecoder($configuration->requestDecoderClass, $this->defaultRequestDecoderClass);
        $requestData = $requestDecoder->decodeRequest($request);

        // Transform request data
        $requestDataTransformers = $this->serviceMap->getRequestDataTransformers(
            $configuration->requestDataTransformerClasses,
            $this->defaultRequestDataTransformerClasses,
        );
        foreach ($requestDataTransformers as $requestDataTransformer) {
            $requestData = $requestDataTransformer->transformRequestData($configuration->dtoClass, $requestData);
        }

        // Construct query from request data
        $dtoConstructor = $this->serviceMap->getDTOConstructor($configuration->dtoConstructorClass, $this->defaultDTOConstructorClass);

        /** @var Query $query */
        $query = $dtoConstructor->constructDTO($requestData, $configuration->dtoClass);

        // Validate query
        $dtoValidators = $this->serviceMap->getDTOValidators($configuration->dtoValidatorClasses, $this->defaultDTOValidatorClasses);
        foreach ($dtoValidators as $dtoValidator) {
            $dtoValidator->validateDTO($request, $query);
        }

        // Wrap handlers
        /** The wrapper handlers are quite complex, so additional explanation can be found in @HandlerWrapperStep */
        $handlerWrappersWithParameters = $this->serviceMap->getHandlerWrappersWithParameters(
            $configuration->handlerWrapperConfigurations,
            $this->defaultHandlerWrapperClasses,
        );

        $handlerWrapperPrepareStep = HandlerWrapperStep::prepare($handlerWrappersWithParameters);
        foreach ($handlerWrapperPrepareStep->orderedHandlerWrappersWithParameters as $handlerWrapperWithParameters) {
            $handlerWrapperWithParameters->handlerWrapper->prepare(
                $query,
                $request,
                $handlerWrapperWithParameters->parameters,
            );
        }

        // Trigger query through query handler
        /** @psalm-suppress PossiblyInvalidArgument */
        $queryHandler = $this->serviceMap->getQueryHandler($configuration->handlerClass);

        $result = null;

        try {
            $result = $queryHandler->handle($query);

            $handlerWrapperThenStep = HandlerWrapperStep::then($handlerWrappersWithParameters);
            foreach ($handlerWrapperThenStep->orderedHandlerWrappersWithParameters as $handlerWrapperWithParameters) {
                $handlerWrapperWithParameters->handlerWrapper->then(
                    $query,
                    $request,
                    $handlerWrapperWithParameters->parameters,
                );
            }
        } catch (\Exception $exception) {
            // Exception is handled by every handler wrapper until one does not return the exception anymore.
            $exceptionToHandle = $exception;
            $handlerWrapperCatchStep = HandlerWrapperStep::catch($handlerWrappersWithParameters);
            foreach ($handlerWrapperCatchStep->orderedHandlerWrappersWithParameters as $handlerWrapperWithParameters) {
                if ($exceptionToHandle !== null) {
                    $exceptionToHandle = $handlerWrapperWithParameters->handlerWrapper->catch(
                        $query,
                        $request,
                        $handlerWrapperWithParameters->parameters,
                        $exceptionToHandle,
                    );
                }
            }

            if ($exceptionToHandle !== null) {
                throw $exceptionToHandle;
            }
        }

        // Construct and return response
        $responseConstructor = $this->serviceMap->getResponseConstructor(
            $configuration->responseConstructorClass,
            $this->defaultResponseConstructorClass,
        );

        return $responseConstructor->constructResponse($result, $request);
    }
}
