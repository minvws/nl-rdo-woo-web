<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\DocumentFileService;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use App\Domain\Upload\WooDecision\DocumentPostUpload;
use App\Service\Uploader\UploadGroupId;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Oneup\UploaderBundle\Event\PostUploadEvent;

final class DocumentPostUploadTest extends UnitTestCase
{
    private WooDecisionRepository&MockInterface $wooDecisionRepository;
    private DocumentFileService&MockInterface $documentFileService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wooDecisionRepository = \Mockery::mock(WooDecisionRepository::class);
        $this->documentFileService = \Mockery::mock(DocumentFileService::class);
    }

    public function testDocumentPostUploadDoesNotAddUploadWithIncorrectUploadGroupId(): void
    {
        /** @var PostUploadEvent&MockInterface $postUploadEvent */
        $postUploadEvent = \Mockery::mock(PostUploadEvent::class);
        $postUploadEvent->shouldReceive('getRequest->getPayload->getString')
            ->with('groupId')
            ->andReturn(UploadGroupId::MAIN_DOCUMENTS->value);

        $this->documentFileService->shouldNotReceive('addUpload');

        $documentPostUpload = new DocumentPostUpload($this->wooDecisionRepository, $this->documentFileService);
        $documentPostUpload->onPostUpload($postUploadEvent);
    }
}
