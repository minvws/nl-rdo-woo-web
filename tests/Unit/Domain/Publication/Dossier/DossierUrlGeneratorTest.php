<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier;

use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Controller\Public\Dossier\Covenant\CovenantController;
use Shared\Domain\Publication\Dossier\DossierUrlGenerator;
use Shared\Domain\Publication\Dossier\Type\DossierReference;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\DossierTypeException;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Shared\ValueObject\Url;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

final class DossierUrlGeneratorTest extends UnitTestCase
{
    public function testDetailPath(): void
    {
        $dossierNumber = $this->getFaker()->dossierNumber();
        $documentPrefix = $this->getFaker()->documentPrefix();
        $path = $this->getFaker()->slug();

        $urlGenerator = Mockery::mock(UrlGeneratorInterface::class);
        $urlGenerator
            ->expects('generate')
            ->with(CovenantController::ROUTE_NAME_DETAIL, [
                'documentPrefix' => $documentPrefix,
                'dossierNumber' => $dossierNumber,
            ])
            ->andReturn($path);

        $dossierUrlGenerator = new DossierUrlGenerator(Mockery::mock(PublicUrlGenerator::class), $urlGenerator);
        $dossier = new DossierReference($dossierNumber, $documentPrefix, DossierTitle::create($this->getFaker()->sentence()), DossierType::COVENANT);

        self::assertSame($path, $dossierUrlGenerator->detailPath($dossier));
    }

    public function testDocumentDetailPath(): void
    {
        $dossierNumber = $this->getFaker()->dossierNumber();
        $documentPrefix = $this->getFaker()->documentPrefix();
        $path = $this->getFaker()->slug();

        $urlGenerator = Mockery::mock(UrlGeneratorInterface::class);
        $urlGenerator->expects('generate')
            ->with(CovenantController::ROUTE_NAME_DOCUMENT_DETAIL, [
                'documentPrefix' => $documentPrefix,
                'dossierNumber' => $dossierNumber,
            ])
            ->andReturn($path);

        $dossierUrlGenerator = new DossierUrlGenerator(Mockery::mock(PublicUrlGenerator::class), $urlGenerator);
        $dossier = new DossierReference($dossierNumber, $documentPrefix, DossierTitle::create($this->getFaker()->sentence()), DossierType::COVENANT);

        self::assertSame($path, $dossierUrlGenerator->documentDetailPath($dossier));
    }

    public function testAttachmentDetailPath(): void
    {
        $dossierNumber = $this->getFaker()->dossierNumber();
        $documentPrefix = $this->getFaker()->documentPrefix();
        $attachmentId = Uuid::v6();
        $path = $this->getFaker()->slug();

        $urlGenerator = Mockery::mock(UrlGeneratorInterface::class);
        $urlGenerator->expects('generate')
            ->with(CovenantController::ROUTE_NAME_ATTACHMENT_DETAIL, [
                'documentPrefix' => $documentPrefix,
                'dossierNumber' => $dossierNumber,
                'attachmentId' => $attachmentId,
            ])
            ->andReturn($path);

        $dossierUrlGenerator = new DossierUrlGenerator(Mockery::mock(PublicUrlGenerator::class), $urlGenerator);
        $dossier = new DossierReference($dossierNumber, $documentPrefix, DossierTitle::create($this->getFaker()->sentence()), DossierType::COVENANT);

        self::assertSame($path, $dossierUrlGenerator->attachmentDetailPath($dossier, $attachmentId));
    }

    public function testNoticeNotPublicDetailPath(): void
    {
        $dossierNumber = $this->getFaker()->dossierNumber();
        $documentPrefix = $this->getFaker()->documentPrefix();
        $path = $this->getFaker()->slug();

        $urlGenerator = Mockery::mock(UrlGeneratorInterface::class);
        $urlGenerator->expects('generate')
            ->with(CovenantController::ROUTE_NAME_NOTICE_NOT_PUBLIC_DETAIL, [
                'documentPrefix' => $documentPrefix,
                'dossierNumber' => $dossierNumber,
            ])
            ->andReturn($path);

        $dossierUrlGenerator = new DossierUrlGenerator(Mockery::mock(PublicUrlGenerator::class), $urlGenerator);
        $dossier = new DossierReference($dossierNumber, $documentPrefix, DossierTitle::create($this->getFaker()->sentence()), DossierType::COVENANT);

        self::assertSame($path, $dossierUrlGenerator->noticeNotPublicDetailPath($dossier));
    }

    public function testDetailUrl(): void
    {
        $dossierNumber = $this->getFaker()->dossierNumber();
        $documentPrefix = $this->getFaker()->documentPrefix();
        $path = $this->getFaker()->slug();
        $url = $this->getFaker()->url();

        $urlGenerator = Mockery::mock(UrlGeneratorInterface::class);
        $urlGenerator->expects('generate')
            ->with(CovenantController::ROUTE_NAME_DETAIL, [
                'documentPrefix' => $documentPrefix,
                'dossierNumber' => $dossierNumber,
            ])
            ->andReturn($path);

        $publicUrlGenerator = Mockery::mock(PublicUrlGenerator::class);
        $publicUrlGenerator
            ->expects('buildUrlFromPath')
            ->with($path)
            ->andReturn(Url::create($url));

        $dossierUrlGenerator = new DossierUrlGenerator($publicUrlGenerator, $urlGenerator);
        $dossier = new DossierReference($dossierNumber, $documentPrefix, DossierTitle::create($this->getFaker()->sentence()), DossierType::COVENANT);

        self::assertSame($url, $dossierUrlGenerator->detailUrl($dossier)->toString());
    }

    public function testDocumentDetailUrl(): void
    {
        $dossierNumber = $this->getFaker()->dossierNumber();
        $documentPrefix = $this->getFaker()->documentPrefix();
        $path = $this->getFaker()->slug();
        $url = $this->getFaker()->url();

        $urlGenerator = Mockery::mock(UrlGeneratorInterface::class);
        $urlGenerator
            ->expects('generate')
            ->with(CovenantController::ROUTE_NAME_DOCUMENT_DETAIL, [
                'documentPrefix' => $documentPrefix,
                'dossierNumber' => $dossierNumber,
            ])
            ->andReturn($path);

        $publicUrlGenerator = Mockery::mock(PublicUrlGenerator::class);
        $publicUrlGenerator
            ->expects('buildUrlFromPath')
            ->with($path)
            ->andReturn(Url::create($url));

        $dossierUrlGenerator = new DossierUrlGenerator($publicUrlGenerator, $urlGenerator);
        $dossier = new DossierReference($dossierNumber, $documentPrefix, DossierTitle::create($this->getFaker()->sentence()), DossierType::COVENANT);

        self::assertSame($url, $dossierUrlGenerator->documentDetailUrl($dossier)->toString());
    }

    public function testAttachmentDetailUrl(): void
    {
        $dossierNumber = $this->getFaker()->dossierNumber();
        $documentPrefix = $this->getFaker()->documentPrefix();
        $attachmentId = Uuid::v6();
        $path = $this->getFaker()->slug();
        $url = $this->getFaker()->url();

        $urlGenerator = Mockery::mock(UrlGeneratorInterface::class);
        $urlGenerator
            ->expects('generate')
            ->with(CovenantController::ROUTE_NAME_ATTACHMENT_DETAIL, [
                'documentPrefix' => $documentPrefix,
                'dossierNumber' => $dossierNumber,
                'attachmentId' => $attachmentId,
            ])
            ->andReturn($path);

        $publicUrlGenerator = Mockery::mock(PublicUrlGenerator::class);
        $publicUrlGenerator
            ->expects('buildUrlFromPath')
            ->with($path)
            ->andReturn(Url::create($url));

        $dossierUrlGenerator = new DossierUrlGenerator($publicUrlGenerator, $urlGenerator);
        $dossier = new DossierReference($dossierNumber, $documentPrefix, DossierTitle::create($this->getFaker()->sentence()), DossierType::COVENANT);

        self::assertSame($url, $dossierUrlGenerator->attachmentDetailUrl($dossier, $attachmentId)->toString());
    }

    public function testAttachmentDetailPathThrowsForComplaintJudgement(): void
    {
        $dossierUrlGenerator = new DossierUrlGenerator(Mockery::mock(PublicUrlGenerator::class), Mockery::mock(UrlGeneratorInterface::class));
        $dossier = new DossierReference(
            $this->getFaker()->dossierNumber(),
            $this->getFaker()->documentPrefix(),
            DossierTitle::create($this->getFaker()->sentence()),
            DossierType::COMPLAINT_JUDGEMENT,
        );

        self::expectException(DossierTypeException::class);

        $dossierUrlGenerator->attachmentDetailPath($dossier, Uuid::v6());
    }

    #[DataProvider('noticeNotPublicDetailUnavailableProvider')]
    public function testNoticeNotPublicDetailPathThrowsWhenUnavailable(DossierType $dossierType): void
    {
        $dossierUrlGenerator = new DossierUrlGenerator(Mockery::mock(PublicUrlGenerator::class), Mockery::mock(UrlGeneratorInterface::class));
        $dossier = new DossierReference(
            $this->getFaker()->dossierNumber(),
            $this->getFaker()->documentPrefix(),
            DossierTitle::create($this->getFaker()->sentence()),
            $dossierType,
        );

        self::expectException(DossierTypeException::class);

        $dossierUrlGenerator->noticeNotPublicDetailPath($dossier);
    }

    /**
     * @return array<string, array{DossierType}>
     */
    public static function noticeNotPublicDetailUnavailableProvider(): array
    {
        return [
            'draft decision' => [DossierType::DRAFT_DECISION],
            'woo decision' => [DossierType::WOO_DECISION],
        ];
    }
}
