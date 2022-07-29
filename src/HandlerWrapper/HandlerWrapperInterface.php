<?php

declare(strict_types=1);

namespace DigitalCraftsman\CQRS\HandlerWrapper;

use DigitalCraftsman\CQRS\Command\Command;
use DigitalCraftsman\CQRS\Query\Query;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handler wrappers are components that allow execution of code before (`prepare`), after success (`then`) and after error (`catch`) of a
 * handler. Each method has its own priority with which it's executed in relation to other handler wrappers. Through this priority it's
 * possible to have the `prepare` method be called first for one handler wrapper but the `catch` method be triggered last.
 * This way it's possible to configure a `prepare` method of TransactionWrapper to be executed *before* the method for the LockWrapper,
 * but have the `catch` method of TransactionWrapper be triggered *after* the method of LockWrapper.
 * The priority mirrors the event listener logic from Symfony in that it's `0` as default and can usually range from `-256` to `256`.
 *
 * With handle wrappers it's possible to implement automatic transaction rollbacks, locking of requests or silent exceptions. All things
 * that are generally part of an application layer and not part of the domain.
 *
 * For now there are no built-in handler wrappers because they are highly dependant of the domain implementation and / or depend on external
 * libraries.
 *
 * It must not be used to:
 * - Handle any kind of business logic.
 *
 * @see https://github.com/digital-craftsman-de/cqrs/blob/main/docs/process.md
 * @see https://github.com/digital-craftsman-de/cqrs/blob/main/docs/examples/handler-wrapper.md
 */
interface HandlerWrapperInterface
{
    /**
     * Triggered right before the handler is triggered.
     *
     * @psalm-param array<int, string|int|float|bool>|string|int|float|bool|null $parameters
     */
    public function prepare(
        Command|Query $dto,
        Request $request,
        mixed $parameters,
    ): void;

    /**
     * Triggered only if the handler was run without exception.
     *
     * @psalm-param array<int, string|int|float|bool>|string|int|float|bool|null $parameters
     */
    public function then(
        Command|Query $dto,
        Request $request,
        mixed $parameters,
    ): void;

    /**
     * Triggered only when an exception occurred while executing the handler.
     * The exception must be returned if it's not explicitly the last exception that should be handled.
     *
     * @psalm-param array<int, string|int|float|bool>|string|int|float|bool|null $parameters
     */
    public function catch(
        Command|Query $dto,
        Request $request,
        mixed $parameters,
        \Exception $exception,
    ): ?\Exception;

    public static function preparePriority(): int;

    public static function thenPriority(): int;

    public static function catchPriority(): int;
}
