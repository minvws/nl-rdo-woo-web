<?php

declare(strict_types=1);

namespace App\Api\Admin\Publication\Search;

use App\Domain\Publication\Attachment\AbstractAttachment;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Step\StepName;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecisionAttachment;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Service\DossierWizard\WizardStatusFactory;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class SearchResultDtoFactory
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private WizardStatusFactory $wizardStatusFactory,
    ) {
    }

    public function make(object $entity): SearchResultDto
    {
        return match (true) {
            $entity instanceof AbstractDossier => $this->fromAbstractDossierEntity($entity),
            $entity instanceof Document => $this->fromDocumentEntity($entity),
            $entity instanceof AbstractMainDocument => $this->fromAbstractMainDocumentEntity($entity),
            $entity instanceof AbstractAttachment => $this->fromAbstractAttachmentEntity($entity),
            default => throw new \InvalidArgumentException(sprintf('Unsupported entity type given: "%s"', $entity::class)),
        };
    }

    /**
     * @param array<array-key,object> $entities
     *
     * @return array<array-key,SearchResultDto>
     */
    public function makeCollection(array $entities): array
    {
        return array_map(
            fn (object $entity): SearchResultDto => $this->make($entity),
            $entities,
        );
    }

    private function fromAbstractDossierEntity(AbstractDossier $entity): SearchResultDto
    {
        return new SearchResultDto(
            id: $entity->getDossierNr(),
            type: SearchResultType::DOSSIER,
            title: $entity->getTitle() ?? '',
            link: $this->urlGenerator->generate(
                'app_admin_dossier',
                ['prefix' => $entity->getDocumentPrefix(), 'dossierId' => $entity->getDossierNr()],
            ),
        );
    }

    private function fromDocumentEntity(Document $entity): SearchResultDto
    {
        /** @var WooDecision $firstDossier */
        $firstDossier = $entity->getDossiers()->first();

        return new SearchResultDto(
            id: $entity->getDocumentNr(),
            type: SearchResultType::DOCUMENT,
            title: $entity->getFileInfo()->getName() ?? '',
            link: $this->urlGenerator->generate(
                'app_admin_dossier_woodecision_document',
                [
                    'prefix' => $firstDossier->getDocumentPrefix(),
                    'dossierId' => $firstDossier->getDossierNr(),
                    'documentId' => $entity->getDocumentNr(),
                ],
            )
        );
    }

    private function fromAbstractMainDocumentEntity(AbstractMainDocument $entity): SearchResultDto
    {
        $routeName = $this->wizardStatusFactory
            ->getWizardStatus($entity->getDossier(), StepName::DETAILS, withAccessCheck: false)
            ->getContentPath();

        return new SearchResultDto(
            id: $entity->getId()->__toString(),
            type: SearchResultType::MAIN_DOCUMENT,
            title: $entity->getFileInfo()->getName() ?? '',
            link: $this->urlGenerator->generate(
                $routeName,
                [
                    'prefix' => $entity->getDossier()->getDocumentPrefix(),
                    'dossierId' => $entity->getDossier()->getDossierNr(),
                ],
            ),
        );
    }

    private function fromAbstractAttachmentEntity(AbstractAttachment $entity): SearchResultDto
    {
        $status = $this->wizardStatusFactory
            ->getWizardStatus($entity->getDossier(), StepName::DETAILS, withAccessCheck: false);

        $routeName = $entity instanceof WooDecisionAttachment
            ? $status->getDecisionPath()
            : $status->getContentPath();

        return new SearchResultDto(
            id: $entity->getId()->__toString(),
            type: SearchResultType::ATTACHMENT,
            title: $entity->getFileInfo()->getName() ?? '',
            link: $this->urlGenerator->generate(
                $routeName,
                [
                    'prefix' => $entity->getDossier()->getDocumentPrefix(),
                    'dossierId' => $entity->getDossier()->getDossierNr(),
                ],
            ),
        );
    }
}
