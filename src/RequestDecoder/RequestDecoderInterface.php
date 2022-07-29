<?php

declare(strict_types=1);

namespace DigitalCraftsman\CQRS\RequestDecoder;

use Symfony\Component\HttpFoundation\Request;

/**
 * The request decoder takes the request object and turns its content into request data in form of an array. It doesn't matter how this data
 * is collected. It might be GET parameters, the body as JSON or files as part of the request.
 *
 * It must not be used to:
 * - Validate the request in any way.
 *
 * @see https://github.com/digital-craftsman-de/cqrs/blob/main/docs/process.md
 * @see https://github.com/digital-craftsman-de/cqrs/blob/main/docs/examples/request-decoder.md
 */
interface RequestDecoderInterface
{
    public function decodeRequest(Request $request): array;
}
