<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\NoticeNotPublic;

use Mockery;
use Mockery\MockInterface;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicResponseDto;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicResponseDtoFactory;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\NoticeNotPublic;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Uid\Uuid;

class NoticeNotPublicResponseDtoFactoryTest extends UnitTestCase
{
    private NoticeNotPublic&MockInterface $noticeNotPublic;
    private NoticeNotPublicResponseDtoFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->noticeNotPublic = Mockery::mock(NoticeNotPublic::class);
        $this->factory = new NoticeNotPublicResponseDtoFactory();
    }

    public function testFromEntityCreatesCorrectDto(): void
    {
        $id = Uuid::v6();
        $formalDate = PlainDate::create('2024-03-15');
        $documentName = 'Test Notice Document';
        $grounds = ['1.1', '1.2'];
        $explanation = 'This document is not public because...';

        $this->noticeNotPublic->expects('getId')->andReturn($id);
        $this->noticeNotPublic->expects('getFormalDate')->andReturn($formalDate);
        $this->noticeNotPublic->expects('getDocumentName')->andReturn($documentName);
        $this->noticeNotPublic->expects('getGrounds')->andReturn($grounds);
        $this->noticeNotPublic->expects('getExplanation')->andReturn($explanation);

        $dto = $this->factory->fromEntity($this->noticeNotPublic);

        $this->assertInstanceOf(NoticeNotPublicResponseDto::class, $dto);
        $this->assertSame($id, $dto->id);
        $this->assertSame($formalDate, $dto->formalDate);
        $this->assertSame($documentName, $dto->documentName);
        $this->assertSame($grounds, $dto->grounds);
        $this->assertSame($explanation, $dto->explanation);
    }

    public function testFromEntityWithOptionalNulls(): void
    {
        $id = Uuid::v6();
        $formalDate = PlainDate::create('2024-03-15');

        $this->noticeNotPublic->expects('getId')->andReturn($id);
        $this->noticeNotPublic->expects('getFormalDate')->andReturn($formalDate);
        $this->noticeNotPublic->expects('getDocumentName')->andReturn(null);
        $this->noticeNotPublic->expects('getGrounds')->andReturn([]);
        $this->noticeNotPublic->expects('getExplanation')->andReturn(null);

        $dto = $this->factory->fromEntity($this->noticeNotPublic);

        $this->assertInstanceOf(NoticeNotPublicResponseDto::class, $dto);
        $this->assertNull($dto->documentName);
        $this->assertSame([], $dto->grounds);
        $this->assertNull($dto->explanation);
    }
}
