<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Publication\Dossier;

use DateTime;
use PublicationApi\Tests\Integration\Api\Publication\ApiPublicationV1TestCase;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Symfony\Component\Uid\Uuid;

use function is_string;
use function sprintf;

abstract class ApiPublicationV1DossierTestCase extends ApiPublicationV1TestCase
{
    abstract protected function getDossierApiUriSegment(): string;

    protected function buildUrl(Uuid|Organisation $organisation, string|AbstractDossier|null $dossier = null): string
    {
        $organisationId = $organisation instanceof Uuid ? $organisation : $organisation->getId();

        if ($dossier === null) {
            return sprintf('/api/publication/v1/organisation/%s/dossiers/%s', $organisationId, $this->getDossierApiUriSegment());
        }

        $dossierId = is_string($dossier) ? $dossier : $dossier->getExternalId();

        return sprintf('/api/publication/v1/organisation/%s/dossiers/%s/E:%s', $organisationId, $this->getDossierApiUriSegment(), $dossierId);
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function createAttachments(int $attachmentCount): array
    {
        $attachments = [];
        for ($i = 0; $i < $attachmentCount; $i++) {
            $attachments[] = [
                'fileName' => $this->getFaker()->word(),
                'formalDate' => $this->getFaker()->date(DateTime::RFC3339),
                'language' => $this->getFaker()->randomElement(AttachmentLanguage::cases()),
                'type' => $this->getFaker()->randomElement(AttachmentType::cases()),
                'externalId' => $this->getFaker()->uuid(),
            ];
        }

        return $attachments;
    }
}
