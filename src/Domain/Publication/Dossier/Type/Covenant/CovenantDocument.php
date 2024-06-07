<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Covenant;

use App\Domain\Publication\Attachment\AttachmentLanguage;
use App\Domain\Publication\Attachment\AttachmentType;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Service\Uploader\UploadGroupId;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CovenantDocumentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class CovenantDocument extends AbstractMainDocument
{
    #[ORM\OneToOne(inversedBy: 'document', targetEntity: Covenant::class)]
    #[ORM\JoinColumn(name: 'dossier_id', referencedColumnName: 'id', nullable: false, onDelete: 'cascade')]
    private Covenant $dossier;

    public function __construct(
        Covenant $dossier,
        \DateTimeImmutable $formalDate,
        AttachmentLanguage $language,
    ) {
        parent::__construct();

        $this->dossier = $dossier;
        $this->formalDate = $formalDate;
        $this->type = AttachmentType::COVENANT;
        $this->language = $language;
    }

    public function getDossier(): Covenant
    {
        return $this->dossier;
    }

    public function getUploadGroupId(): UploadGroupId
    {
        return UploadGroupId::COVENANT_DOCUMENTS;
    }

    /**
     * @return list<AttachmentType>
     */
    public static function getAllowedTypes(): array
    {
        return [AttachmentType::COVENANT];
    }
}
