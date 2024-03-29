<?php

declare(strict_types=1);

namespace DigitalCraftsman\CQRS\Test\Domain\Tasks\WriteSide\MarkTaskAsAccepted;

use DigitalCraftsman\CQRS\Command\Command;
use DigitalCraftsman\CQRS\Test\ValueObject\TaskId;

final readonly class MarkTaskAsAcceptedCommand implements Command
{
    public function __construct(
        public TaskId $taskId,
    ) {
    }
}
