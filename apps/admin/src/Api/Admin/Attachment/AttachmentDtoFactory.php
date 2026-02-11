<?php

declare(strict_types=1);

namespace Admin\Api\Admin\Attachment;

use Admin\Api\Admin\Dossier\DossierReferenceDto;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webmozart\Assert\Assert;

readonly class AttachmentDtoFactory
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * @param class-string<AbstractAttachmentDto> $dtoClass
     */
    public function make(string $dtoClass, AbstractAttachment $entity): AbstractAttachmentDto
    {
        $mimeType = $entity->getFileInfo()->getMimeType();
        Assert::notNull($mimeType);

        return new $dtoClass(
            id: $entity->getId()->toRfc4122(),
            dossier: DossierReferenceDto::fromEntity($entity->getDossier()),
            name: $entity->getFileInfo()->getName() ?? '',
            formalDate: $entity->getFormalDate(),
            type: $entity->getType()->value,
            mimeType: $mimeType,
            size: $entity->getFileInfo()->getSize(),
            internalReference: $entity->getInternalReference(),
            language: $entity->getLanguage()->value,
            grounds: $entity->getGrounds(),
            withdrawUrl: $this->urlGenerator->generate(
                'app_admin_dossier_attachment_withdraw',
                [
                    'prefix' => $entity->getDossier()->getDocumentPrefix(),
                    'dossierId' => $entity->getDOssier()->getDossierNr(),
                    'attachmentId' => $entity->getId(),
                ],
            ),
        );
    }
}
