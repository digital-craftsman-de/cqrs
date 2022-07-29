<?php

declare(strict_types=1);

namespace DigitalCraftsman\CQRS\RequestValidator;

use DigitalCraftsman\CQRS\Test\Utility\VirusScannerSimulator;
use Symfony\Component\HttpFoundation\Request;

final class GuardAgainstFileWithVirusRequestValidator implements RequestValidatorInterface
{
    public function __construct(
        private VirusScannerSimulator $virusScanner,
    ) {
    }

    public function validateRequest(Request $request): void
    {
        foreach ($request->files as $file) {
            $this->virusScanner->scanForVirus($file);
        }
    }
}
