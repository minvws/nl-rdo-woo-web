<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use cebe\openapi\exceptions\IOException;
use cebe\openapi\exceptions\TypeErrorException;
use cebe\openapi\exceptions\UnresolvableReferenceException;
use cebe\openapi\json\InvalidJsonPointerSyntaxException;
use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi as OpenApiSpec;
use PublicationApi\Domain\OpenApi\Exception\SpecException;
use Symfony\Component\Serializer\SerializerInterface;
use Webmozart\Assert\Assert;

use function fwrite;
use function stream_get_meta_data;
use function tmpfile;

class OpenApiSpecGenerator
{
    private ?OpenApiSpec $spec = null;

    public function __construct(
        private readonly OpenApiFactoryInterface $openApiFactory,
        private readonly SerializerInterface $serializer,
    ) {
    }

    /**
     * @throws SpecException
     */
    public function getSpec(): OpenApiSpec
    {
        if ($this->spec === null) {
            $this->spec = $this->generateSpec();
        }

        return $this->spec;
    }

    /**
     * @throws SpecException
     */
    private function generateSpec(): OpenApiSpec
    {
        $openApi = ($this->openApiFactory)();
        $json = $this->serializer->serialize($openApi, 'json');

        $temp = tmpfile();
        fwrite($temp, $json);
        $streamMetaData = stream_get_meta_data($temp);
        Assert::keyExists($streamMetaData, 'uri');

        try {
            return Reader::readFromJsonFile($streamMetaData['uri']);
        } catch (IOException | InvalidJsonPointerSyntaxException | UnresolvableReferenceException | TypeErrorException $exception) {
            throw new SpecException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
