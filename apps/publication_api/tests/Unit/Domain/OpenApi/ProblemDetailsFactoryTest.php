<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Domain\OpenApi;

use Exception;
use PublicationApi\Domain\OpenApi\Exception\ValidationException;
use PublicationApi\Domain\OpenApi\ProblemDetailsFactory;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;

final class ProblemDetailsFactoryTest extends UnitTestCase
{
    public function testBuildReturnsNullForUnknownException(): void
    {
        $factory = new ProblemDetailsFactory();

        self::assertNull($factory->build(new Exception('unknown')));
    }

    public function testBuildFromValidationException(): void
    {
        $message = 'message';

        $factory = new ProblemDetailsFactory();
        $result = $factory->build(new ValidationException($message, 1, new Exception()));

        self::assertNotNull($result);
        self::assertSame(
            [
                'type' => 'errors#openapi-validation',
                'title' => 'Invalid API Request',
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'detail' => $message,
            ],
            $result->jsonSerialize(),
        );
    }

    public function testBuildFromNotFoundHttpException(): void
    {
        $message = 'not found';

        $factory = new ProblemDetailsFactory();
        $result = $factory->build(new NotFoundHttpException($message));

        self::assertNotNull($result);
        self::assertSame(
            [
                'type' => 'errors#resource-not-found',
                'title' => 'Resource Not Found',
                'status' => Response::HTTP_NOT_FOUND,
                'detail' => $message,
            ],
            $result->jsonSerialize(),
        );
    }

    public function testBuildFromNotEncodableValueException(): void
    {
        $factory = new ProblemDetailsFactory();
        $result = $factory->build(new NotEncodableValueException('Syntax error'));

        self::assertNotNull($result);
        self::assertSame(
            [
                'type' => 'errors#invalid-request-body',
                'title' => 'Invalid Request Body',
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'detail' => 'Request body must be a valid JSON object',
            ],
            $result->jsonSerialize(),
        );
    }
}
