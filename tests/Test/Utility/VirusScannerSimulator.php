<?php

declare(strict_types=1);

namespace DigitalCraftsman\CQRS\Test\Utility;

use Symfony\Component\HttpFoundation\File\UploadedFile;

final class VirusScannerSimulator
{
    public function scanForVirus(UploadedFile $file): void
    {
        // Here would be virus validation. For example through an external API.
    }
}
