<?php

declare(strict_types=1);

namespace DigitalCraftsman\CQRS\DTOValidator;

use DigitalCraftsman\CQRS\Command\Command;
use DigitalCraftsman\CQRS\Query\Query;
use Symfony\Component\HttpFoundation\Request;

/**
 * DTO validators are there to validate data within the DTO against information on an application and infrastructure level.
 *
 * Multiple DTO validators can be applied on each request.
 *
 * It must not be used to:
 * - Validate the integrity of the DTO itself
 * - Validate any of the value objects in it (that's the task of the constructors).
 * - Validate any kind of business logic including access validation.
 *
 * @see https://github.com/digital-craftsman-de/cqrs/blob/main/docs/process.md
 * @see https://github.com/digital-craftsman-de/cqrs/blob/main/docs/examples/dto-validator.md
 */
interface DTOValidatorInterface
{
    public function validateDTO(Request $request, Command|Query $dto): void;
}
