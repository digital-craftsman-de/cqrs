<?php

declare(strict_types=1);

namespace DigitalCraftsman\CQRS\RequestDataTransformer;

/**
 * The data transformer can have three kinds of tasks and multiple data transformers can be used with one request.
 * - Cast existing data into other formats.
 * - Sanitize existing data.
 * - Add additional data not present in the request.

 *  It must not be used to:
 * - Validate the request data in any way. That must be handled in the DTO validator.
 *
 * @see https://github.com/digital-craftsman-de/cqrs/blob/main/docs/process.md
 * @see https://github.com/digital-craftsman-de/cqrs/blob/main/docs/examples/request-data-transformer.md
 */
interface RequestDataTransformerInterface
{
    /** @param class-string $dtoClass */
    public function transformRequestData(string $dtoClass, array $requestData): array;
}
