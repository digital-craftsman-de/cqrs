<?php

declare(strict_types=1);

namespace DigitalCraftsman\CQRS\ServiceMap\Exception;

/**
 * @psalm-immutable
 * @codeCoverageIgnore
 *
 * @internal
 */
final class ConfiguredRequestValidatorNotAvailable extends \DomainException
{
    public function __construct(string $requestValidatorClass)
    {
        parent::__construct(sprintf('The configured request validator "%s" is not available', $requestValidatorClass));
    }
}
