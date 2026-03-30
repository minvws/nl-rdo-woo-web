<?php

declare(strict_types=1);

namespace Integration\Domain\Upload;

use Carbon\CarbonImmutable;
use Shared\Domain\Upload\UploadEntity;
use Shared\Domain\Upload\UploadEntityRepository;
use Shared\Tests\Factory\UploadEntityFactory;
use Shared\Tests\Integration\SharedWebTestCase;
use Symfony\Component\HttpFoundation\InputBag;

class UploadEntityRepositoryTest extends SharedWebTestCase
{
    public function testFindLatestByContextData(): void
    {
        $key = $this->getFaker()->word();
        $value = $this->getFaker()->word();

        UploadEntityFactory::createOne([
            'context' => new InputBag([$key => $value]),
        ]);

        $uploadEntityRepository = self::fromContainer(UploadEntityRepository::class);
        $result = $uploadEntityRepository->findLatestByContextData($key, $value);

        self::assertInstanceOf(UploadEntity::class, $result);
    }

    public function testFindLatestByContextDataWithOrdering(): void
    {
        $key = $this->getFaker()->word();
        $value = $this->getFaker()->word();

        $expectedResult = UploadEntityFactory::createOne([
            'context' => new InputBag([$key => $value]),
            'createdAt' => CarbonImmutable::now()->toDateTime(),
        ]);
        UploadEntityFactory::createOne([
            'context' => new InputBag([$key => $value]),
            'createdAt' => CarbonImmutable::now()->subDay()->toDateTime(),
        ]);

        /** @var UploadEntityRepository $uploadEntityRepository */
        $uploadEntityRepository = self::getContainer()->get(UploadEntityRepository::class);
        $result = $uploadEntityRepository->findLatestByContextData($key, $value);

        self::assertInstanceOf(UploadEntity::class, $result);
        self::assertTrue($expectedResult->getId()->equals($result->getId()));
    }

    public function testFindLatestByContextDataKeyNotFound(): void
    {
        $value = $this->getFaker()->word();

        UploadEntityFactory::createOne([
            'context' => new InputBag([$this->getFaker()->word() => $value]),
        ]);

        /** @var UploadEntityRepository $uploadEntityRepository */
        $uploadEntityRepository = self::getContainer()->get(UploadEntityRepository::class);
        $result = $uploadEntityRepository->findLatestByContextData($this->getFaker()->word(), $value);

        self::assertNull($result);
    }

    public function testFindLatestByContextDataValueNotFound(): void
    {
        $key = $this->getFaker()->word();

        UploadEntityFactory::createOne([
            'context' => new InputBag([$key => $this->getFaker()->word()]),
        ]);

        /** @var UploadEntityRepository $uploadEntityRepository */
        $uploadEntityRepository = self::getContainer()->get(UploadEntityRepository::class);
        $result = $uploadEntityRepository->findLatestByContextData($key, $this->getFaker()->word());

        self::assertNull($result);
    }

    public function testFindLatestByContextDataKeyAndValueNotFound(): void
    {
        UploadEntityFactory::createOne([
            'context' => new InputBag([$this->getFaker()->unique()->word() => $this->getFaker()->word()]),
        ]);

        /** @var UploadEntityRepository $uploadEntityRepository */
        $uploadEntityRepository = self::getContainer()->get(UploadEntityRepository::class);
        $result = $uploadEntityRepository->findLatestByContextData($this->getFaker()->unique()->word(), $this->getFaker()->word());

        self::assertNull($result);
    }

    public function testFindLatestByContextDataWhenTableEmpty(): void
    {
        /** @var UploadEntityRepository $uploadEntityRepository */
        $uploadEntityRepository = self::getContainer()->get(UploadEntityRepository::class);
        $result = $uploadEntityRepository->findLatestByContextData($this->getFaker()->word(), $this->getFaker()->word());

        self::assertNull($result);
    }
}
