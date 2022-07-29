<?php

declare(strict_types=1);

namespace DigitalCraftsman\CQRS\DTOConstructor;

use DigitalCraftsman\CQRS\Command\Command;
use DigitalCraftsman\CQRS\Query\Query;

/**
 * The DTO constructor is there to construct the command or query from the request data. It also has to throw exceptions when there is data
 * missing. Depending on the implementation that might already be handled by the hydration method.
 *
 * @see https://github.com/digital-craftsman-de/cqrs/blob/main/docs/process.md
 * @see https://github.com/digital-craftsman-de/cqrs/blob/main/docs/examplesl/dto-constructor.md
 */
interface DTOConstructorInterface
{
    /**
     * @psalm-template T of Command|Query
     * @psalm-param class-string<T> $dtoClass
     * @psalm-return T
     */
    public function constructDTO(array $requestData, string $dtoClass): Command|Query;
}
