<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\DraftDecision;

use PublicationApi\Api\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Dossier\DossierAttachmentSynchronizer;
use Shared\Domain\Publication\Attachment\Event\AbstractAttachmentEvent;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecision;

use function array_map;
use function array_values;

readonly class DraftDecisionAttachmentSynchronizer
{
    public function __construct(
        private DossierAttachmentSynchronizer $dossierAttachmentSynchronizer,
    ) {
    }

    /**
     * @param list<DraftDecisionAttachmentRequestDto> $draftDecisionAttachmentRequestDtos
     *
     * @return list<AbstractAttachmentEvent>
     */
    public function create(DraftDecision $draftDecision, array $draftDecisionAttachmentRequestDtos): array
    {
        return $this->dossierAttachmentSynchronizer->create(
            $draftDecision,
            self::toAttachmentRequestDtos($draftDecisionAttachmentRequestDtos),
        );
    }

    /**
     * @param list<DraftDecisionAttachmentRequestDto> $draftDecisionAttachmentRequestDtos
     *
     * @return list<AbstractAttachmentEvent>
     */
    public function update(DraftDecision $draftDecision, array $draftDecisionAttachmentRequestDtos): array
    {
        return $this->dossierAttachmentSynchronizer->update(
            $draftDecision,
            self::toAttachmentRequestDtos($draftDecisionAttachmentRequestDtos),
        );
    }

    /**
     * @param list<DraftDecisionAttachmentRequestDto> $draftDecisionAttachmentRequestDtos
     *
     * @return list<AttachmentRequestDto>
     */
    private static function toAttachmentRequestDtos(array $draftDecisionAttachmentRequestDtos): array
    {
        return array_values(array_map(
            static fn (DraftDecisionAttachmentRequestDto $draftDecisionAttachmentRequestDto): AttachmentRequestDto
                => $draftDecisionAttachmentRequestDto->toAttachmentRequestDto(),
            $draftDecisionAttachmentRequestDtos,
        ));
    }
}
