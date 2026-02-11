<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Upload\Exception\UploadException;
use Shared\Domain\Upload\Exception\UploadValidationException;
use Shared\Domain\Upload\UploadEntity;
use Shared\Domain\Upload\UploadStatus;
use Shared\Service\Security\User;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\InputBag;

class UploadEntityTest extends UnitTestCase
{
    private User&MockInterface $user;
    private InputBag $context;

    protected function setUp(): void
    {
        $this->user = Mockery::mock(User::class);
        $this->context = new InputBag(['foo' => 'bar']);
    }

    public function testFinishUploading(): void
    {
        $uploadEntity = new UploadEntity(
            'foo-bar-123',
            UploadGroupId::WOO_DECISION_DOCUMENTS,
            $this->user,
            $this->context,
        );

        self::assertEquals(UploadStatus::INCOMPLETE, $uploadEntity->getStatus());

        $uploadEntity->finishUploading($filename = 'foo.bar', $filesize = 123);

        self::assertEquals(UploadStatus::UPLOADED, $uploadEntity->getStatus());
        self::assertEquals($filename, $uploadEntity->getFilename());
        self::assertEquals($filesize, $uploadEntity->getSize());

        $this->expectException(UploadException::class);
        $uploadEntity->finishUploading($filename, $filesize);
    }

    public function testAbort(): void
    {
        $uploadEntity = new UploadEntity(
            'foo-bar-123',
            UploadGroupId::WOO_DECISION_DOCUMENTS,
            $this->user,
            $this->context,
        );
        self::assertEquals(UploadStatus::INCOMPLETE, $uploadEntity->getStatus());

        $uploadEntity->abort();
        self::assertEquals(UploadStatus::ABORTED, $uploadEntity->getStatus());

        $this->expectException(UploadException::class);
        $uploadEntity->abort();
    }

    public function testPassValidation(): void
    {
        $uploadEntity = new UploadEntity(
            'foo-bar-123',
            UploadGroupId::WOO_DECISION_DOCUMENTS,
            $this->user,
            $this->context,
        );

        $uploadEntity->finishUploading('foo.bar', 123);
        self::assertEquals(UploadStatus::UPLOADED, $uploadEntity->getStatus());

        $uploadEntity->passValidation($mimetype = 'foo/bar');
        self::assertEquals(UploadStatus::VALIDATION_PASSED, $uploadEntity->getStatus());
        self::assertEquals($mimetype, $uploadEntity->getMimetype());

        $this->expectException(UploadException::class);
        $uploadEntity->passValidation($mimetype);
    }

    public function testFailValidation(): void
    {
        $uploadEntity = new UploadEntity(
            'foo-bar-123',
            UploadGroupId::WOO_DECISION_DOCUMENTS,
            $this->user,
            $this->context,
        );

        $uploadEntity->finishUploading('foo.bar', 123);
        self::assertEquals(UploadStatus::UPLOADED, $uploadEntity->getStatus());

        $exception = new UploadValidationException($message = 'oops');
        $uploadEntity->failValidation($exception);
        self::assertEquals(UploadStatus::VALIDATION_FAILED, $uploadEntity->getStatus());
        self::assertEquals([$message], $uploadEntity->getError());

        $this->expectException(UploadException::class);
        $uploadEntity->failValidation($exception);
    }

    public function testMarkAsStored(): void
    {
        $uploadEntity = new UploadEntity(
            'foo-bar-123',
            UploadGroupId::WOO_DECISION_DOCUMENTS,
            $this->user,
            $this->context,
        );

        $uploadEntity->finishUploading('foo.bar', 123);
        $uploadEntity->passValidation('foo/bar');
        $uploadEntity->markAsStored();

        self::assertEquals(UploadStatus::STORED, $uploadEntity->getStatus());

        $this->expectException(UploadException::class);
        $uploadEntity->markAsStored();
    }

    public function testGetters(): void
    {
        $uploadEntity = new UploadEntity(
            $uploadId = 'foo-bar-123',
            $groupId = UploadGroupId::WOO_DECISION_DOCUMENTS,
            $this->user,
            $this->context,
        );

        $uploadEntity->setExternalId($externalId = 'bar789');
        $uploadEntity->finishUploading($filename = 'foo.bar', $size = 123);
        $uploadEntity->passValidation($mimetype = 'foo/bar');
        $uploadEntity->markAsStored();

        self::assertEquals($uploadId, $uploadEntity->getUploadId());
        self::assertEquals($externalId, $uploadEntity->getExternalId());
        self::assertEquals($groupId, $uploadEntity->getUploadGroupId());
        self::assertEquals($this->user, $uploadEntity->getUser());
        self::assertEquals($size, $uploadEntity->getSize());
        self::assertEquals($mimetype, $uploadEntity->getMimetype());
        self::assertEquals($filename, $uploadEntity->getFilename());
        self::assertEquals(['foo' => 'bar'], $uploadEntity->getContext()->all());
    }
}
