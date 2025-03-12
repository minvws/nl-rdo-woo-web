<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Document;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawReason;
use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use App\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\FileInfo;
use Mockery\Adapter\Phpunit\MockeryTestCase;

final class DocumentTest extends MockeryTestCase
{
    public function testAddAndRemoveDossier(): void
    {
        $document = new Document();
        $wooDecision = \Mockery::mock(WooDecision::class);

        $document->addDossier($wooDecision);
        self::assertFalse($document->getDossiers()->isEmpty());

        $document->removeDossier($wooDecision);
        self::assertTrue($document->getDossiers()->isEmpty());
    }

    public function testSetAndGetPageCount(): void
    {
        $document = new Document();

        $document->setPageCount($pageCount = 10);
        self::assertEquals($pageCount, $document->getPageCount());
    }

    public function testSetAndGetSummary(): void
    {
        $document = new Document();

        $document->setSummary($summary = 'foo');
        self::assertEquals($summary, $document->getSummary());
    }

    public function testSetAndGetTitle(): void
    {
        $document = new Document();

        $document->setTitle($title = 'foo');
        self::assertEquals($title, $document->getTitle());
    }

    public function testSetAndGetDocumentNr(): void
    {
        $document = new Document();

        $document->setDocumentNr($nr = 'foo');
        self::assertEquals($nr, $document->getDocumentNr());
    }

    public function testSetAndGetDocumentDate(): void
    {
        $document = new Document();

        $document->setDocumentDate($date = new \DateTimeImmutable());
        self::assertEquals($date, $document->getDocumentDate());
    }

    public function testSetAndGetFamilyId(): void
    {
        $document = new Document();

        $document->setFamilyId($id = 123);
        self::assertEquals($id, $document->getFamilyId());
    }

    public function testSetAndGetDocumentId(): void
    {
        $document = new Document();

        $document->setDocumentId($id = 'foo-123');
        self::assertEquals($id, $document->getDocumentId());
    }

    public function testSetAndGetThreadId(): void
    {
        $document = new Document();

        $document->setThreadId($id = 123);
        self::assertEquals($id, $document->getThreadId());
    }

    public function testSetAndGetJudgement(): void
    {
        $document = new Document();

        $document->setJudgement($judgement = Judgement::PUBLIC);
        self::assertEquals($judgement, $document->getJudgement());
    }

    public function testSetAndGetGroundsResetsKeys(): void
    {
        $document = new Document();

        $document->setGrounds([
            2 => 'foo',
            4 => 'bar',
        ]);

        self::assertEquals(
            [
                0 => 'foo',
                1 => 'bar',
            ],
            $document->getGrounds(),
        );
    }

    public function testSetAndGetPeriod(): void
    {
        $document = new Document();

        $document->setPeriod($period = 'now');
        self::assertEquals($period, $document->getPeriod());
    }

    public function testSetSuspended(): void
    {
        $document = new Document();

        $document->setSuspended(true);
        self::assertTrue($document->isSuspended());

        $document->setSuspended(false);
        self::assertFalse($document->isSuspended());
    }

    public function testWithdrawAndRepublish(): void
    {
        $document = new Document();

        $document->withdraw($reason = DocumentWithdrawReason::DATA_IN_DOCUMENT, $explanation = 'oops');
        self::assertTrue($document->isWithdrawn());
        self::assertEquals($reason, $document->getWithdrawReason());
        self::assertEquals($explanation, $document->getWithdrawExplanation());
        self::assertNotNull($document->getWithdrawDate());

        $document->republish();
        self::assertFalse($document->isWithdrawn());
        self::assertNull($document->getWithdrawReason());
        self::assertEquals('', $document->getWithdrawExplanation());
        self::assertNull($document->getWithdrawDate());
    }

    public function testHasPubliclyAvailableDossier(): void
    {
        $wooDecision = \Mockery::mock(WooDecision::class);
        $wooDecision->shouldReceive('getStatus')->andReturn(DossierStatus::CONCEPT);

        $document = new Document();
        $document->addDossier($wooDecision);
        self::assertFalse($document->hasPubliclyAvailableDossier());

        $wooDecisionB = \Mockery::mock(WooDecision::class);
        $wooDecisionB->shouldReceive('getStatus')->andReturn(DossierStatus::PUBLISHED);

        $document->addDossier($wooDecisionB);
        self::assertTrue($document->hasPubliclyAvailableDossier());
    }

    public function testAddAndRemoveInquiry(): void
    {
        $document = new Document();
        $inquiry = \Mockery::mock(Inquiry::class);

        $document->addInquiry($inquiry);
        self::assertFalse($document->getInquiries()->isEmpty());

        $inquiry->expects('removeDocument')->with($document);
        $document->removeInquiry($inquiry);
        self::assertTrue($document->getInquiries()->isEmpty());
    }

    public function testSetAndGetLinks(): void
    {
        $document = new Document();
        $document->setLinks($links = [
            'foo',
            'bar',
        ]);

        self::assertEquals($links, $document->getLinks());
    }

    public function testIsUploaded(): void
    {
        $fileInfo = new FileInfo();
        $fileInfo->setUploaded(true);

        $document = new Document();
        $document->setFileInfo($fileInfo);

        self::assertTrue($document->isUploaded());
    }

    public function testShouldBeUploadedReturnsFalseWhenSuspended(): void
    {
        $document = new Document();
        $document->setSuspended(true);
        $document->setJudgement(Judgement::PUBLIC);

        self::assertFalse($document->shouldBeUploaded());
    }

    public function testShouldBeUploadedReturnsFalseWhenWithdrawn(): void
    {
        $document = new Document();
        $document->withdraw(DocumentWithdrawReason::DATA_IN_FILE, 'oops');
        $document->setJudgement(Judgement::PUBLIC);

        self::assertFalse($document->shouldBeUploaded());
    }

    public function testShouldBeUploadedReturnsTrueWhenWithdrawnButIgnored(): void
    {
        $document = new Document();
        $document->setJudgement(Judgement::PUBLIC);
        $document->withdraw(DocumentWithdrawReason::DATA_IN_FILE, 'oops');

        self::assertTrue($document->shouldBeUploaded(true));
    }

    public function testShouldBeUploadedReturnsFalseWhenJudgementIsMissing(): void
    {
        $document = new Document();

        self::assertFalse($document->shouldBeUploaded());
    }

    public function testShouldBeUploadedReturnsFalseJudgementIsNotPublic(): void
    {
        $document = new Document();
        $document->setJudgement(Judgement::NOT_PUBLIC);

        self::assertFalse($document->shouldBeUploaded());
    }

    public function testShouldBeUploadedReturnsTrueJudgementIsPublic(): void
    {
        $document = new Document();
        $document->setJudgement(Judgement::PUBLIC);

        self::assertTrue($document->shouldBeUploaded());
    }

    public function testSetAndGetRemark(): void
    {
        $document = new Document();

        $document->setRemark($remark = 'foo-123');
        self::assertEquals($remark, $document->getRemark());
    }

    public function testAddAndRemoveReferral(): void
    {
        $document = new Document();
        $referredDocument = new Document();

        $document->addReferralTo($referredDocument);
        self::assertEquals([$referredDocument], $document->getRefersTo()->toArray());

        $document->removeReferralTo($referredDocument);
        self::assertTrue($document->getRefersTo()->isEmpty());
    }
}
