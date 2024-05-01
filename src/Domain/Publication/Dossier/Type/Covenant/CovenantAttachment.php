<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Covenant;

use App\Domain\Publication\Attachment\AbstractAttachment;
use App\Domain\Publication\Attachment\AttachmentLanguage;
use App\Domain\Publication\Attachment\AttachmentType;
use App\Service\Uploader\UploadGroupId;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CovenantAttachmentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class CovenantAttachment extends AbstractAttachment
{
    #[ORM\ManyToOne(targetEntity: Covenant::class, inversedBy: 'attachments')]
    #[ORM\JoinColumn(name: 'dossier_id', referencedColumnName: 'id', nullable: false, onDelete: 'cascade')]
    private Covenant $dossier;

    public function __construct(
        Covenant $dossier,
        \DateTimeImmutable $formalDate,
        AttachmentType $type,
        AttachmentLanguage $language,
    ) {
        parent::__construct();

        $this->dossier = $dossier;
        $this->formalDate = $formalDate;
        $this->type = $type;
        $this->language = $language;
    }

    public function getDossier(): Covenant
    {
        return $this->dossier;
    }

    public function getUploadGroupId(): UploadGroupId
    {
        return UploadGroupId::COVENANT_ATTACHMENTS;
    }
}
