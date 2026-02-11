<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\WooDecision\Uploads\Attachment;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use PublicationApi\Api\Publication\UploadProcessor;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Domain\Publication\Attachment\Repository\AttachmentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Attachment\WooDecisionAttachment;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Service\Uploader\UploadGroupId;
use Symfony\Component\Validator\ConstraintViolationList;
use Webmozart\Assert\Assert;

readonly class WooDecisionUploadAttachmentProcessor implements ProcessorInterface
{
    public function __construct(
        private AttachmentRepository $attachmentRepository,
        private OrganisationRepository $organisationRepository,
        private UploadProcessor $uploadProcessor,
        private WooDecisionRepository $wooDecisionRepository,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        if (! $data instanceof WooDecisionUploadAttachment) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('Invalid attachment request'));
        }

        $organisation = $this->organisationRepository->find($data->organisationId);
        Assert::isInstanceOf($organisation, Organisation::class);

        $wooDecision = $this->wooDecisionRepository->findByOrganisationAndExternalId($organisation, $data->dossierExternalId);
        Assert::isInstanceOf($wooDecision, WooDecision::class);

        if (! $organisation->getId()->equals($wooDecision->getOrganisation()->getId())) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('No dossier found for this organisation'));
        }

        $attachment = $this->attachmentRepository->findByDossierAndExternalId($wooDecision, $data->attachmentExternalId);
        if (! $attachment instanceof WooDecisionAttachment) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('No attachment found for this dossier'));
        }

        if ($data->content === '') {
            throw new ValidationException(ConstraintViolationList::createFromMessage('No file content provided'));
        }

        $fileName = $attachment->getFileInfo()->getName();
        Assert::notNull($fileName);

        $this->uploadProcessor->process($wooDecision->getId(), UploadGroupId::ATTACHMENTS, $data->content, $fileName);

        return null;
    }
}
