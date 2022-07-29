<?php

declare(strict_types=1);

namespace DigitalCraftsman\CQRS\ResponseConstructor;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A response constructor is there to construct a response from the data that is returned from the handler. A command handler won't return
 * anything. Therefore, most of the time a response handler like the `EmptyResponseConstructor` will be configured for it.
 *
 * The query handler on the other hand will return a value nearly every time and depending on the use case, the value might be serialized to
 * JSON, send as binary data or even be streamed as part of a streamed response.
 *
 * @see https://github.com/digital-craftsman-de/cqrs/blob/main/docs/process.md
 * @see https://github.com/digital-craftsman-de/cqrs/blob/main/docs/examples/response-constructor.md
 */
interface ResponseConstructorInterface
{
    /** @param ?mixed $data */
    public function constructResponse($data, Request $request): Response;
}
