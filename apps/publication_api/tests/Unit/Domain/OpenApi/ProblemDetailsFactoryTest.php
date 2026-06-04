<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Domain\OpenApi;

use Exception;
use League\OpenAPIValidation\Schema\BreadCrumb;
use League\OpenAPIValidation\Schema\Exception\FormatMismatch;
use League\OpenAPIValidation\Schema\Exception\KeywordMismatch;
use League\OpenAPIValidation\Schema\Exception\SchemaMismatch;
use PublicationApi\Domain\OpenApi\Exception\FormatMismatchException;
use PublicationApi\Domain\OpenApi\Exception\KeywordMismatchException;
use PublicationApi\Domain\OpenApi\Exception\SchemaMismatchException;
use PublicationApi\Domain\OpenApi\Exception\ValidationException;
use PublicationApi\Domain\OpenApi\ProblemDetailsFactory;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use function sprintf;

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

    public function testBuildFromSchemaMismatchException(): void
    {
        $message = 'message';

        $factory = new ProblemDetailsFactory();
        $result = $factory->build(SchemaMismatchException::fromSchemaMismatch(new SchemaMismatch($message)));

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

    public function testBuildFromSchemaMismatchExceptionWithBreadcrumb(): void
    {
        $message = 'message';
        $compoundIndex = 'compound';

        $schemaMismatch = new SchemaMismatch($message);
        $schemaMismatch->withBreadCrumb(new BreadCrumb($compoundIndex));

        $factory = new ProblemDetailsFactory();
        $result = $factory->build(SchemaMismatchException::fromSchemaMismatch($schemaMismatch));

        self::assertNotNull($result);
        self::assertSame(
            [
                'type' => 'errors#openapi-validation',
                'title' => 'Invalid API Request',
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'detail' => $message,
                'field' => $compoundIndex,
            ],
            $result->jsonSerialize(),
        );
    }

    public function testBuildFromSchemaMismatchExceptionWithBreadcrumbButNullCompound(): void
    {
        $message = 'message';

        $schemaMismatch = new SchemaMismatch($message);
        $schemaMismatch->withBreadCrumb(new BreadCrumb(null));

        $factory = new ProblemDetailsFactory();
        $result = $factory->build(SchemaMismatchException::fromSchemaMismatch($schemaMismatch));

        self::assertNotNull($result);
        self::assertSame(
            [
                'type' => 'errors#openapi-validation',
                'title' => 'Invalid API Request',
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'detail' => $message,
                'field' => '',
            ],
            $result->jsonSerialize(),
        );
    }

    public function testBuildFromKeywordMismatch(): void
    {
        $message = 'message';
        $keyword = 'keyword';

        $factory = new ProblemDetailsFactory();
        $result = $factory->build(
            KeywordMismatchException::fromKeywordMismatch(KeywordMismatch::fromKeyword($keyword, [], $message)),
        );

        self::assertNotNull($result);
        self::assertSame(
            [
                'type' => 'errors#openapi-validation',
                'title' => 'Invalid API Request',
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'detail' => sprintf('Keyword validation failed: %s', $message),
                'keyword' => $keyword,
            ],
            $result->jsonSerialize(),
        );
    }

    public function testBuildFromFormatMismatch(): void
    {
        $format = 'format';
        $value = 'value';
        $type = 'type';

        $factory = new ProblemDetailsFactory();
        $result = $factory->build(
            FormatMismatchException::fromFormatMismatch(FormatMismatch::fromFormat($format, $value, $type)),
        );

        self::assertNotNull($result);
        self::assertSame(
            [
                'type' => 'errors#openapi-validation',
                'title' => 'Invalid API Request',
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'detail' => sprintf("Value '%s' does not match format %s of type %s", $value, $format, $type),
                'format' => $format,
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
}
