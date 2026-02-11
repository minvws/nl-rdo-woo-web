<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi;

use cebe\openapi\exceptions\TypeErrorException;
use League\OpenAPIValidation\PSR7\Exception\ValidationFailed;
use League\OpenAPIValidation\PSR7\OperationAddress;
use League\OpenAPIValidation\PSR7\ValidatorBuilder;
use League\OpenAPIValidation\Schema\Exception\FormatMismatch;
use League\OpenAPIValidation\Schema\Exception\KeywordMismatch;
use League\OpenAPIValidation\Schema\Exception\SchemaMismatch;
use PublicationApi\Domain\OpenApi\Exception\FormatMismatchException;
use PublicationApi\Domain\OpenApi\Exception\KeywordMismatchException;
use PublicationApi\Domain\OpenApi\Exception\SchemaMismatchException;
use PublicationApi\Domain\OpenApi\Exception\SpecException;
use PublicationApi\Domain\OpenApi\Exception\ValidatonException;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function ltrim;
use function sprintf;
use function strtolower;

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
        $spec = $this->specGenerator->getSpec();

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
        $spec = $this->specGenerator->getSpec();

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
