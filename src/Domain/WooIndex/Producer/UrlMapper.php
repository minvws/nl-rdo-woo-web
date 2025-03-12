<?php

declare(strict_types=1);

namespace App\Domain\WooIndex\Producer;

use App\Domain\Publication\Attachment\Entity\AbstractAttachment;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use Carbon\Carbon;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webmozart\Assert\Assert;

final readonly class UrlMapper
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private DiWooDocumentFactory $diWooDocumentFactory,
    ) {
    }

    public function fromEntity(Document|AbstractMainDocument|AbstractAttachment $entity): Url
    {
        $dossier = $this->getDossier($entity);

        return new Url(
            loc: $this->urlGenerator->generate(
                name: 'app_dossier_file_download',
                parameters: [
                    'prefix' => $dossier->getDocumentPrefix(),
                    'dossierId' => $dossier->getDossierNr(),
                    'type' => $dossier->getType()->value,
                    'id' => $entity->getId(),
                ],
                referenceType: UrlGeneratorInterface::ABSOLUTE_URL,
            ),
            lastmod: Carbon::instance($entity->getUpdatedAt()),
            diWooDocument: $this->diWooDocumentFactory->fromDocument($dossier, $entity),
        );
    }

    private function getDossier(Document|AbstractMainDocument|AbstractAttachment $entity): AbstractDossier
    {
        $dossier = match (true) {
            $entity instanceof Document => $entity->getDossiers()->first(),
            $entity instanceof AbstractMainDocument,
            $entity instanceof AbstractAttachment => $entity->getDossier(),
        };

        Assert::notFalse($dossier);

        return $dossier;
    }
}
