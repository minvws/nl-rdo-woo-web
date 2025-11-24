<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\InvestigationReport;

use Doctrine\ORM\Mapping as ORM;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Webmozart\Assert\Assert;

#[ORM\Entity(repositoryClass: InvestigationReportAttachmentRepository::class)]
class InvestigationReportAttachment extends AbstractAttachment
{
    public function __construct(
        AbstractDossier $dossier,
        \DateTimeImmutable $formalDate,
        AttachmentType $type,
        AttachmentLanguage $language,
    ) {
        parent::__construct();

        Assert::isInstanceOf($dossier, InvestigationReport::class);

        $this->dossier = $dossier;
        $this->formalDate = $formalDate;
        $this->type = $type;
        $this->language = $language;
        $this->fileInfo->setPaginatable(true);
    }

    /**
     * @return list<AttachmentType>
     */
    public static function getAllowedTypes(): array
    {
        $filterTypes = InvestigationReportMainDocument::getAllowedTypes();
        $filterTypes[] = AttachmentType::OTHER;

        return AttachmentType::getCasesWithout(...$filterTypes);
    }
}
