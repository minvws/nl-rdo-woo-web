<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi;

use PublicationApi\Domain\Exception\EntityNotFoundException;
use PublicationApi\Domain\OpenApi\Exception\FormatMismatchException;
use PublicationApi\Domain\OpenApi\Exception\KeywordMismatchException;
use PublicationApi\Domain\OpenApi\Exception\SchemaMismatchException;
use PublicationApi\Domain\OpenApi\Exception\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Throwable;

use function sprintf;

class ProblemDetailsFactory
{
    private const string BASE_URI = 'errors#';

    public function build(Throwable $exception): ?ProblemDetails
    {
        return match (true) {
            $exception instanceof AuthenticationException => $this->buildAuthenticationProblem($exception),
            $exception instanceof EntityNotFoundException => $this->buildEntityNotFoundProblem($exception),
            $exception instanceof NotFoundHttpException => $this->buildNotFoundProblem($exception),
            $exception instanceof ValidationException => $this->buildValidationProblem($exception),
            default => null,
        };
    }

    private function buildAuthenticationProblem(AuthenticationException $exception): ProblemDetails
    {
        return new ProblemDetails(
            type: self::BASE_URI . 'authentication-failed',
            title: 'Authentication Failed',
            status: Response::HTTP_UNAUTHORIZED,
            detail: $exception->getMessage(),
        );
    }

    private function buildEntityNotFoundProblem(EntityNotFoundException $exception): ProblemDetails
    {
        return new ProblemDetails(
            type: self::BASE_URI . 'resource-not-found',
            title: 'Resource Not Found',
            status: Response::HTTP_NOT_FOUND,
            detail: sprintf('%s with id %s was not found', $exception->entityName, $exception->id),
        );
    }

    private function buildNotFoundProblem(NotFoundHttpException $exception): ProblemDetails
    {
        return new ProblemDetails(
            type: self::BASE_URI . 'resource-not-found',
            title: 'Resource Not Found',
            status: Response::HTTP_NOT_FOUND,
            detail: $exception->getMessage(),
        );
    }

    private function buildValidationProblem(ValidationException $exception): ProblemDetails
    {
        return new ProblemDetails(
            type: self::BASE_URI . 'openapi-validation',
            title: 'Invalid API Request',
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
            detail: $exception->getMessage(),
            field: $exception instanceof SchemaMismatchException ? $exception->getBreadCrumb() : null,
            keyword: $exception instanceof KeywordMismatchException ? $exception->getKeyword() : null,
            format: $exception instanceof FormatMismatchException ? $exception->getFormat() : null,
        );
    }
}
