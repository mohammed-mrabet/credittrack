<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Lecture du corps JSON et réponses d'erreur homogènes : {"errors": {"champ": "message"}}.
 */
trait JsonPayloadTrait
{
    private function payload(Request $request): array
    {
        $data = json_decode($request->getContent() ?: '{}', true);

        return is_array($data) ? $data : [];
    }

    private function validationError(array $errors): JsonResponse
    {
        return new JsonResponse(['errors' => $errors], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
    }

    private function notFound(string $message): JsonResponse
    {
        return new JsonResponse(['error' => $message], JsonResponse::HTTP_NOT_FOUND);
    }

    private function parseDate(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value)) {
            return null;
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return false === $date ? null : $date;
    }
}
