<?php

declare(strict_types=1);

namespace DigitalCraftsman\CQRS\HandlerWrapper;

use DigitalCraftsman\CQRS\Command\Command;
use DigitalCraftsman\CQRS\Query\Query;
use Symfony\Component\HttpFoundation\Request;

final readonly class SilentExceptionWrapper implements HandlerWrapperInterface
{
    /** @param array<int, string> $parameters */
    #[\Override]
    public function prepare(
        Command | Query $dto,
        Request $request,
        mixed $parameters,
    ): void {
        // Nothing to do
    }

    /** @param array<int, string> $parameters Exception class strings to be swallowed */
    #[\Override]
    public function catch(
        Command | Query $dto,
        Request $request,
        mixed $parameters,
        \Exception $exception,
    ): ?\Exception {
        // Catch exception which should be handled silently
        if (in_array(get_class($exception), $parameters, true)) {
            return null;
        }

        return $exception;
    }

    /** @param array<int, string> $parameters */
    #[\Override]
    public function then(
        Command | Query $dto,
        Request $request,
        mixed $parameters,
    ): void {
        // Nothing to do
    }

    // Priorities

    #[\Override]
    /** @codeCoverageIgnore */
    public static function preparePriority(): int
    {
        return 0;
    }

    #[\Override]
    /** @codeCoverageIgnore */
    public static function catchPriority(): int
    {
        return -100;
    }

    #[\Override]
    /** @codeCoverageIgnore */
    public static function thenPriority(): int
    {
        return 0;
    }

    /** @param array<array-key, class-string<\Throwable>> $parameters Needs to be at least one exception class */
    #[\Override]
    public static function areParametersValid(mixed $parameters): bool
    {
        if (!is_array($parameters)) {
            return false;
        }

        if (count($parameters) === 0) {
            return false;
        }

        foreach ($parameters as $exceptionClass) {
            if (!class_exists($exceptionClass)) {
                return false;
            }

            $reflectionClass = new \ReflectionClass($exceptionClass);
            /** @psalm-suppress TypeDoesNotContainType It's possible that someone puts in something other than an exception. */
            if (!$reflectionClass->implementsInterface(\Throwable::class)) {
                return false;
            }
        }

        return true;
    }
}