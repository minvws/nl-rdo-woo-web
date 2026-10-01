<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\DraftDecision;

use PublicationApi\Api\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Dossier\DossierAttachmentValidator;
use PublicationApi\Domain\Dossier\AttachmentSynchronizer;
use Shared\Domain\Publication\Attachment\Event\AbstractAttachmentEvent;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecision;

use function array_map;
use function array_values;

readonly class DraftDecisionAttachmentSynchronizer
{
    public function __construct(
        private AttachmentSynchronizer $attachmentSynchronizer,
        private DossierAttachmentValidator $dossierAttachmentValidator,
    ) {
    }

    /**
     * @param list<DraftDecisionAttachmentRequestDto> $draftDecisionAttachmentRequestDtos
     *
     * @return list<AbstractAttachmentEvent>
     */
    public function create(DraftDecision $draftDecision, array $draftDecisionAttachmentRequestDtos): array
    {
        $attachmentRequestDtos = self::toAttachmentRequestDtos($draftDecisionAttachmentRequestDtos);

        $this->dossierAttachmentValidator->assertUniqueExternalIds($attachmentRequestDtos);

        return $this->synchronize($draftDecision, $attachmentRequestDtos);
    }

    /**
     * @param list<DraftDecisionAttachmentRequestDto> $draftDecisionAttachmentRequestDtos
     *
     * @return list<AbstractAttachmentEvent>
     */
    public function update(DraftDecision $draftDecision, array $draftDecisionAttachmentRequestDtos): array
    {
        $attachmentRequestDtos = self::toAttachmentRequestDtos($draftDecisionAttachmentRequestDtos);

        $this->dossierAttachmentValidator->assertUniqueExternalIds($attachmentRequestDtos);
        $this->dossierAttachmentValidator->assertNoAttachmentRemovalInNonConcept($draftDecision, $attachmentRequestDtos);

        return $this->synchronize($draftDecision, $attachmentRequestDtos);
    }

    /**
     * @param list<AttachmentRequestDto> $attachmentRequestDtos
     *
     * @return list<AbstractAttachmentEvent>
     */
    private function synchronize(DraftDecision $draftDecision, array $attachmentRequestDtos): array
    {
        $attachmentEvents = $this->attachmentSynchronizer->sync($draftDecision, $attachmentRequestDtos);
        $this->dossierAttachmentValidator->validate($draftDecision->getAttachments()->getValues(), $draftDecision->getStatus());

        return $attachmentEvents;
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
