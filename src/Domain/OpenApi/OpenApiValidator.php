<?php

declare(strict_types=1);

namespace App\Domain\OpenApi;

use App\Api\Publication\V1\PublicationV1Api;
use App\Domain\OpenApi\Exceptions\FormatMismatchException;
use App\Domain\OpenApi\Exceptions\KeywordMismatchException;
use App\Domain\OpenApi\Exceptions\SchemaMismatchException;
use App\Domain\OpenApi\Exceptions\SpecException;
use App\Domain\OpenApi\Exceptions\ValidatonException;
use cebe\openapi\exceptions\TypeErrorException;
use League\OpenAPIValidation\PSR7\Exception\ValidationFailed;
use League\OpenAPIValidation\PSR7\OperationAddress;
use League\OpenAPIValidation\PSR7\ValidatorBuilder;
use League\OpenAPIValidation\Schema\Exception\FormatMismatch;
use League\OpenAPIValidation\Schema\Exception\KeywordMismatch;
use League\OpenAPIValidation\Schema\Exception\SchemaMismatch;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
class OpenApiValidator
{
    public function __construct(
        private readonly ValidatorBuilder $validatorBuilder,
        private readonly PsrHttpFactory $psrHttpFactory,
        private readonly OpenApiSpecGenerator $specGenerator,
    ) {
    }

    /**
     * @throws FormatMismatchException
     * @throws KeywordMismatchException
     * @throws SchemaMismatchException
     * @throws SpecException
     * @throws ValidatonException
     */
    public function validateRequest(Request $request, string $path, string $method): void
    {
        $psrRequest = $this->psrHttpFactory->createRequest($request);
        $operationAddress = $this->getOperationAddress($path, $method);
        $spec = $this->specGenerator->getSpec(PublicationV1Api::API_TAG);

        $this->validate(function () use ($spec, $operationAddress, $psrRequest): void {
            $this->validatorBuilder
                ->fromSchema($spec)
                ->getRoutedRequestValidator()
                ->validate($operationAddress, $psrRequest);
        });
    }

    /**
     * @throws FormatMismatchException
     * @throws KeywordMismatchException
     * @throws SchemaMismatchException
     * @throws SpecException
     * @throws ValidatonException
     */
    public function validateResponse(Response $response, string $path, string $method): void
    {
        $psrResponse = $this->psrHttpFactory->createResponse($response);
        $operationAddress = $this->getOperationAddress($path, $method);
        $spec = $this->specGenerator->getSpec(PublicationV1Api::API_TAG);

        $this->validate(function () use ($spec, $operationAddress, $psrResponse): void {
            $this->validatorBuilder
                ->fromSchema($spec)
                ->getResponseValidator()
                ->validate($operationAddress, $psrResponse);
        });
    }

    /**
     * @throws FormatMismatchException
     * @throws KeywordMismatchException
     * @throws SchemaMismatchException
     * @throws ValidatonException
     */
    private function validate(callable $validationCallback): void
    {
        try {
            $validationCallback();
        } catch (FormatMismatch $formatMismatch) {
            throw FormatMismatchException::fromFormatMismatch($formatMismatch);
        } catch (KeywordMismatch $keywordMismatch) {
            throw KeywordMismatchException::fromKeywordMismatch($keywordMismatch);
        } catch (SchemaMismatch $schemaMismatch) {
            throw SchemaMismatchException::fromSchemaMismatch($schemaMismatch);
        } catch (TypeErrorException | ValidationFailed $exception) {
            throw ValidatonException::fromThrowable($exception);
        }
    }

    private function getOperationAddress(string $path, string $method): OperationAddress
    {
        $path = sprintf('/%s', ltrim($path, '/'));

        return new OperationAddress($path, strtolower($method));
    }
}
