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
        $key = $this->getFaker()->unique()->word();
        $value = $this->getFaker()->unique()->word();

        UploadEntityFactory::createOne([
            'context' => new InputBag([$key => $value]),
        ]);

        $uploadEntityRepository = self::fromContainer(UploadEntityRepository::class);
        $result = $uploadEntityRepository->findLatestByContextData($key, $value);

        self::assertInstanceOf(UploadEntity::class, $result);
    }

    public function testFindLatestByContextDataWithOrdering(): void
    {
        $key = $this->getFaker()->unique()->word();
        $value = $this->getFaker()->unique()->word();

        $expectedResult = UploadEntityFactory::createOne([
            'context' => new InputBag([$key => $value]),
            'createdAt' => CarbonImmutable::now()->toDateTime(),
        ]);
        UploadEntityFactory::createOne([
            'context' => new InputBag([$key => $value]),
            'createdAt' => CarbonImmutable::now()->subDay()->toDateTime(),
        ]);

        $uploadEntityRepository = self::fromContainer(UploadEntityRepository::class);
        $result = $uploadEntityRepository->findLatestByContextData($key, $value);

        self::assertInstanceOf(UploadEntity::class, $result);
        self::assertTrue($expectedResult->getId()->equals($result->getId()));
    }

    public function testFindLatestByContextDataKeyNotFound(): void
    {
        $value = $this->getFaker()->unique()->word();

        UploadEntityFactory::createOne([
            'context' => new InputBag([$this->getFaker()->unique()->word() => $value]),
        ]);

        $uploadEntityRepository = self::fromContainer(UploadEntityRepository::class);
        $result = $uploadEntityRepository->findLatestByContextData($this->getFaker()->unique()->word(), $value);

        self::assertNull($result);
    }

    public function testFindLatestByContextDataValueNotFound(): void
    {
        $key = $this->getFaker()->unique()->word();

        UploadEntityFactory::createOne([
            'context' => new InputBag([$key => $this->getFaker()->unique()->word()]),
        ]);

        $uploadEntityRepository = self::fromContainer(UploadEntityRepository::class);
        $result = $uploadEntityRepository->findLatestByContextData($key, $this->getFaker()->unique()->word());

        self::assertNull($result);
    }

    public function testFindLatestByContextDataKeyAndValueNotFound(): void
    {
        UploadEntityFactory::createOne([
            'context' => new InputBag([$this->getFaker()->unique()->word() => $this->getFaker()->unique()->word()]),
        ]);

        $uploadEntityRepository = self::fromContainer(UploadEntityRepository::class);
        $result = $uploadEntityRepository->findLatestByContextData($this->getFaker()->unique()->word(), $this->getFaker()->unique()->word());

        self::assertNull($result);
    }

    public function testFindLatestByContextDataWhenTableEmpty(): void
    {
        $uploadEntityRepository = self::fromContainer(UploadEntityRepository::class);
        $result = $uploadEntityRepository->findLatestByContextData($this->getFaker()->unique()->word(), $this->getFaker()->unique()->word());

        self::assertNull($result);
    }

    public function testRemoveAllByContextData(): void
    {
        $key = $this->getFaker()->unique()->word();
        $value = $this->getFaker()->unique()->word();

        UploadEntityFactory::createOne(['context' => new InputBag([$key => $value])]);
        UploadEntityFactory::createOne(['context' => new InputBag([$key => $value])]);

        $uploadEntityRepository = self::fromContainer(UploadEntityRepository::class);
        $uploadEntityRepository->removeAllByContextData($key, $value);

        self::assertNull($uploadEntityRepository->findLatestByContextData($key, $value));
    }

    public function testRemoveAllByContextDataOnlyRemovesMatching(): void
    {
        $key = $this->getFaker()->unique()->word();
        $value = $this->getFaker()->unique()->word();

        UploadEntityFactory::createOne(['context' => new InputBag([$key => $value])]);
        UploadEntityFactory::createOne(['context' => new InputBag([$this->getFaker()->unique()->word() => $this->getFaker()->unique()->word()])]);

        $uploadEntityRepository = self::fromContainer(UploadEntityRepository::class);
        $uploadEntityRepository->removeAllByContextData($key, $value);

        self::assertNull($uploadEntityRepository->findLatestByContextData($key, $value));
        self::assertCount(1, $uploadEntityRepository->findAll());
    }

    public function testRemoveAllByContextDataWhenNothingMatches(): void
    {
        $uploadEntityRepository = self::fromContainer(UploadEntityRepository::class);
        $uploadEntityRepository->removeAllByContextData($this->getFaker()->unique()->word(), $this->getFaker()->unique()->word());

        self::assertCount(0, $uploadEntityRepository->findAll());
    }
}
