<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Inventory;

use App\Entity\Document;
use App\Entity\Inquiry;
use App\Entity\Judgement;
use App\Entity\Organisation;
use App\Message\UpdateInquiryLinksMessage;
use App\Service\Inventory\DocumentMetadata;
use App\Service\Inventory\InquiryUpdater;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV6;

class InquiryUpdaterTest extends MockeryTestCase
{
    private InquiryUpdater $inquiryUpdater;
    private Organisation|MockInterface $organisation;
    private MessageBusInterface|MockInterface $messageBus;
    private UuidV6 $organisationId;

    public function setUp(): void
    {
        $this->organisationId = Uuid::v6();

        $this->organisation = \Mockery::mock(Organisation::class);
        $this->organisation->shouldReceive('getId')->andReturn($this->organisationId);

        $this->messageBus = \Mockery::mock(MessageBusInterface::class);

        $this->inquiryUpdater = new InquiryUpdater(
            $this->organisation,
            $this->messageBus,
        );

        parent::setUp();
    }

    public function testAllAddedChangesAreFlushed(): void
    {
        // Has no linked inquiries yet, so should be linked twice
        $docId123 = Uuid::v6();
        $this->inquiryUpdater->addToChangeset(
            $this->createDocumentMetadata('123', ['case-1', 'case-2']),
            $this->createDocument($docId123, []),
        );

        // Has two new inquiry links (case-1 and case-3), one unmodified/existing (case-2) and one removed ('case-4')
        $docId456 = Uuid::v6();
        $this->inquiryUpdater->addToChangeset(
            $this->createDocumentMetadata('456', ['case-1', 'case-2', 'case-3']),
            $this->createDocument($docId456, ['case-2', 'case-4']),
        );

        // Docs 123 and 456 should be added to case-1
        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            function (UpdateInquiryLinksMessage $message) use ($docId123, $docId456) {
                $this->assertEquals($this->organisation->getId(), $message->getOrganisationId());
                $this->assertEquals('case-1', $message->getCaseNr());
                $this->assertEquals([$docId123, $docId456], $message->getDocIdsToAdd());
                $this->assertEquals([], $message->getDocIdsToDelete());

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        // Doc 123 should be added to case-2
        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            function (UpdateInquiryLinksMessage $message) use ($docId123) {
                $this->assertEquals($this->organisation->getId(), $message->getOrganisationId());
                $this->assertEquals('case-2', $message->getCaseNr());
                $this->assertEquals([$docId123], $message->getDocIdsToAdd());
                $this->assertEquals([], $message->getDocIdsToDelete());

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        // Doc 456 should be removed from case-4
        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            function (UpdateInquiryLinksMessage $message) use ($docId456) {
                $this->assertEquals($this->organisation->getId(), $message->getOrganisationId());
                $this->assertEquals('case-4', $message->getCaseNr());
                $this->assertEquals([], $message->getDocIdsToAdd());
                $this->assertEquals([$docId456], $message->getDocIdsToDelete());

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        // Doc 456 should be added to case-3
        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            function (UpdateInquiryLinksMessage $message) use ($docId456) {
                $this->assertEquals($this->organisation->getId(), $message->getOrganisationId());
                $this->assertEquals('case-3', $message->getCaseNr());
                $this->assertEquals([$docId456], $message->getDocIdsToAdd());
                $this->assertEquals([], $message->getDocIdsToDelete());

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->inquiryUpdater->flushChangeset();
    }

    /**
     * @param string[] $caseNumbers
     */
    private function createDocumentMetadata(string $id, array $caseNumbers): DocumentMetadata
    {
        return new DocumentMetadata(
            date: new \DateTimeImmutable(),
            filename: '1234.pdf',
            familyId: 456,
            sourceType: 'pdf',
            grounds: [],
            id: $id,
            judgement: Judgement::PUBLIC,
            period: 'alles',
            subjects: [],
            threadId: 123,
            caseNumbers: $caseNumbers,
            suspended: false,
            links: [''],
            remark: '',
            matter: '',
        );
    }

    /**
     * @param string[] $caseNumbers
     */
    private function createDocument(Uuid $id, array $caseNumbers): Document
    {
        $inquiries = new ArrayCollection();
        foreach ($caseNumbers as $caseNumber) {
            $inquiry = \Mockery::mock(Inquiry::class);
            $inquiry->expects('getCasenr')->andReturn($caseNumber);
            $inquiries->add($inquiry);
        }

        $document = \Mockery::mock(Document::class);
        $document->expects('getInquiries')->andReturn($inquiries);
        $document->expects('getId')->zeroOrMoreTimes()->andReturn($id);

        return $document;
    }
}
