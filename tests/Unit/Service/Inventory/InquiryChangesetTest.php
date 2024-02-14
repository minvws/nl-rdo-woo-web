<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Inventory;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\Inquiry;
use App\Entity\Organisation;
use App\Service\Inventory\InquiryChangeset;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Component\Uid\Uuid;

class InquiryChangesetTest extends MockeryTestCase
{
    private InquiryChangeset $changeset;

    public function setUp(): void
    {
        $organisationId = Uuid::v6();

        $organisation = \Mockery::mock(Organisation::class);
        $organisation->shouldReceive('getId')->andReturn($organisationId);

        $this->changeset = new InquiryChangeset(
            $organisation,
        );

        parent::setUp();
    }

    public function testAllAddedChangesAreStored(): void
    {
        // Has no linked inquiries yet, so should be linked twice
        $docId123 = Uuid::v6();
        $this->changeset->updateCaseNrsForDocument(
            $this->createDocument($docId123, []),
            ['case-1', 'case-2'],
        );

        // Has two new inquiry links (case-1 and case-3), one unmodified/existing (case-2) and one removed ('case-4')
        $docId456 = Uuid::v6();
        $this->changeset->updateCaseNrsForDocument(
            $this->createDocument($docId456, ['case-2', 'case-4']),
            ['case-1', 'case-2', 'case-3']
        );

        // Dossier is added to case 3 and 4
        $dossierId = Uuid::v6();
        $dossier = \Mockery::mock(Dossier::class);
        $dossier->shouldReceive('getId')->andReturn($dossierId);
        $dossier->shouldReceive('getInquiries')->andReturn(new ArrayCollection());
        $this->changeset->addCaseNrsForDossier($dossier, ['case-3', 'case-4']);

        $this->assertEquals(
            [
                'case-1' => [
                    'add_documents' => [$docId123, $docId456],
                    'del_documents' => [],
                    'add_dossiers' => [],
                ],
                'case-2' => [
                    'add_documents' => [$docId123],
                    'del_documents' => [],
                    'add_dossiers' => [],
                ],
                'case-3' => [
                    'add_documents' => [$docId456],
                    'del_documents' => [],
                    'add_dossiers' => [$dossierId],
                ],
                'case-4' => [
                    'add_documents' => [],
                    'del_documents' => [$docId456],
                    'add_dossiers' => [$dossierId],
                ],
            ],
            $this->changeset->getChanges()
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
        $document->shouldReceive('getId')->andReturn($id);

        return $document;
    }
}
