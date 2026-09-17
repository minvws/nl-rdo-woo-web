<?php

declare(strict_types=1);

namespace Shared\Tests\Snapshots;

use Shared\Serializer\DocumentIdNormalizer;
use Shared\Serializer\FileNameNormalizer;
use Shared\Serializer\PlainDateNormalizer;
use Spatie\Snapshots\Drivers\ObjectDriver as SpatieObjectDriver;
use Symfony\Component\Serializer\Encoder\YamlEncoder;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

final class ObjectDriver extends SpatieObjectDriver
{
    public function serialize(mixed $data): string
    {
        $serializer = new Serializer(
            [
                new PlainDateNormalizer(),
                new DocumentIdNormalizer(),
                new FileNameNormalizer(),
                new DateTimeNormalizer(),
                new ObjectNormalizer(),
            ],
            [
                new YamlEncoder(),
            ],
        );

        return $this->dedent(
            $serializer->serialize($data, 'yaml', $this->yamlConfig),
        );
    }
}
