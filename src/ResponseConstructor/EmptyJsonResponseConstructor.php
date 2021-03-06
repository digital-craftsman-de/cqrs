<?php

declare(strict_types=1);

namespace DigitalCraftsman\CQRS\ResponseConstructor;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class EmptyJsonResponseConstructor implements ResponseConstructorInterface
{
    public function constructResponse($data, Request $request): JsonResponse
    {
        return new JsonResponse('', Response::HTTP_NO_CONTENT, [], true);
    }
}
