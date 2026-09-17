<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Inventory;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Domain\Publication\SourceType;
use Shared\Service\Inquiry\InquiryNumbers;
use Shared\Service\Inventory\DocumentMetadata;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DocumentId;
use Shared\ValueObject\DocumentNumber;
use Shared\ValueObject\PublicationContext;

final class DocumentMetadataTest extends UnitTestCase
{
    public function testItUsesTheDocumentNumberForTheFallbackPdfFilename(): void
    {
        $metadata = new DocumentMetadata(
            date: null,
            filename: '',
            familyId: null,
            sourceType: SourceType::PDF,
            grounds: [],
            id: DocumentId::create('doc-01'),
            judgement: Judgement::PUBLIC,
            period: null,
            threadId: null,
            inquiryNumbers: InquiryNumbers::empty(),
            suspended: false,
            links: [],
            remark: null,
            publicationContext: PublicationContext::fromString('prefix-matter'),
            refersTo: [],
        );

        self::assertSame(
            'prefix-matter-doc-01.pdf',
            $metadata->getFilename(DocumentNumber::fromString('prefix-matter-doc-01')),
        );
    }
}
