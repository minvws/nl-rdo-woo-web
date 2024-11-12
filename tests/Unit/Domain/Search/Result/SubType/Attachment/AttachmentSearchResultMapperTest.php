<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Result\SubType\Attachment;

use App\Domain\Publication\Attachment\AbstractAttachment;
use App\Domain\Publication\Attachment\AttachmentRepository;
use App\Domain\Publication\Attachment\ViewModel\Attachment;
use App\Domain\Publication\Attachment\ViewModel\AttachmentViewFactory;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Result\SubType\Attachment\AttachmentSearchResultMapper;
use App\Domain\Search\Result\SubType\SubTypeSearchResultEntry;
use Jaytaph\TypeArray\TypeArray;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class AttachmentSearchResultMapperTest extends MockeryTestCase
{
    private AttachmentRepository&MockInterface $attachmentRepository;
    private AttachmentViewFactory&MockInterface $attachmentViewFactory;
    private AttachmentSearchResultMapper $mapper;

    public function setUp(): void
    {
        $this->attachmentRepository = \Mockery::mock(AttachmentRepository::class);
        $this->attachmentViewFactory = \Mockery::mock(AttachmentViewFactory::class);

        $this->mapper = new AttachmentSearchResultMapper(
            $this->attachmentRepository,
            $this->attachmentViewFactory,
        );
    }

    public function testSupports(): void
    {
        self::assertTrue($this->mapper->supports(ElasticDocumentType::ATTACHMENT));
        self::assertFalse($this->mapper->supports(ElasticDocumentType::COMPLAINT_JUDGEMENT_MAIN_DOCUMENT));
    }

    public function testMapReturnsNullWhenIdIsMissing(): void
    {
        $hit = \Mockery::mock(TypeArray::class);
        $hit->shouldReceive('getStringOrNull')->with('[_id]')->andReturnNull();

        $this->assertNull($this->mapper->map($hit));
    }

    public function testMapReturnsNullWhenAttachmentCannotBeLoaded(): void
    {
        $hit = \Mockery::mock(TypeArray::class);
        $hit->shouldReceive('getStringOrNull')->with('[_id]')->andReturn('foo');

        $this->attachmentRepository->shouldReceive('find')->with('foo')->andReturnNull();

        $this->assertNull($this->mapper->map($hit));
    }

    public function testMapSuccessful(): void
    {
        $hit = \Mockery::mock(TypeArray::class);
        $hit->shouldReceive('getStringOrNull')->with('[_id]')->andReturn('foo');
        $hit->shouldReceive('exists')->with('[highlight][pages.content]')->andReturnTrue();
        $hit->shouldReceive('getTypeArray->toArray')->andReturn(['x', 'y']);
        $hit->shouldReceive('exists')->with('[highlight][dossiers.title]')->andReturnFalse();
        $hit->shouldReceive('exists')->with('[highlight][dossiers.summary]')->andReturnFalse();

        $dossier = \Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getDossierNr')->andReturn($dossierNr = '123');
        $dossier->shouldReceive('getDocumentPrefix')->andReturn($documentPrefix = 'foo');
        $dossier->shouldReceive('getTitle')->andReturn($title = 'bar');
        $dossier->shouldReceive('getType')->andReturn($dossierType = DossierType::COVENANT);

        $attachment = \Mockery::mock(AbstractAttachment::class);
        $attachment->shouldReceive('getDossier')->andReturn($dossier);

        /** @var Attachment&MockInterface $viewModel */
        $viewModel = \Mockery::mock(Attachment::class);

        $this->attachmentRepository->shouldReceive('find')->with('foo')->andReturn($attachment);
        $this->attachmentViewFactory->expects('make')->with($dossier, $attachment)->andReturn($viewModel);

        $entry = $this->mapper->map($hit);

        self::assertInstanceOf(SubTypeSearchResultEntry::class, $entry);

        $dossierReference = $entry->getDossiers()[0];

        $this->assertInstanceOf(SubTypeSearchResultEntry::class, $entry);
        $this->assertSame($viewModel, $entry->getViewModel());
        $this->assertSame($dossierNr, $dossierReference->getDossierNr());
        $this->assertSame($documentPrefix, $dossierReference->getDocumentPrefix());
        $this->assertSame($dossierType, $dossierReference->getType());
        $this->assertSame($title, $dossierReference->getTitle());
        $this->assertSame(['x', 'y'], $entry->getHighlights());
        $this->assertSame(ElasticDocumentType::ATTACHMENT, $entry->getType());
    }
}
