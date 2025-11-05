<?php

declare(strict_types=1);

namespace App\Domain\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use App\Domain\OpenApi\Exceptions\SpecException;
use cebe\openapi\exceptions\IOException;
use cebe\openapi\exceptions\TypeErrorException;
use cebe\openapi\exceptions\UnresolvableReferenceException;
use cebe\openapi\json\InvalidJsonPointerSyntaxException;
use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi as OpenApiSpec;
use Symfony\Component\Serializer\SerializerInterface;
use Webmozart\Assert\Assert;

class OpenApiSpecGenerator
{
    /**
     * @var array<OpenApiSpec>
     */
    private array $specs = [];

    public function __construct(
        private readonly OpenApiFactoryInterface $openApiFactory,
        private readonly SerializerInterface $serializer,
    ) {
    }

    /**
     * @throws SpecException
     */
    public function getSpec(string $tag): OpenApiSpec
    {
        if (! \array_key_exists($tag, $this->specs)) {
            $this->specs[$tag] = $this->generateSpec($tag);
        }

        return $this->specs[$tag];
    }

    /**
     * @throws SpecException
     */
    private function generateSpec(string $tag): OpenApiSpec
    {
        $openApi = ($this->openApiFactory)(['filter_tags' => $tag]);
        $json = $this->serializer->serialize($openApi, 'json');

        $temp = \tmpfile();
        \fwrite($temp, $json);
        $streamMetaData = \stream_get_meta_data($temp);
        Assert::keyExists($streamMetaData, 'uri');

        try {
            return Reader::readFromJsonFile($streamMetaData['uri']);
        } catch (IOException | InvalidJsonPointerSyntaxException | UnresolvableReferenceException | TypeErrorException $exception) {
            throw new SpecException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
