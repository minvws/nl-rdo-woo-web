<?php

declare(strict_types=1);

namespace App\Api\Admin\CovenantDocument;

use ApiPlatform\Metadata\ApiProperty;
use App\Domain\Publication\Attachment\AttachmentLanguage;
use Symfony\Component\Validator\Constraints as Assert;

class CovenantDocumentUpdateDto
{
    #[Assert\Date]
    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'format' => 'date',
        ],
        jsonSchemaContext: [
            'type' => 'string',
            'format' => 'date',
        ]
    )]
    public ?string $formalDate = null;

    public ?string $internalReference = null;

    public ?AttachmentLanguage $language = null;

    /**
     * @var string[]|null $grounds
     */
    #[Assert\All([
        new Assert\Type('string'),
        new Assert\NotBlank(),
    ])]
    public ?array $grounds = null;

    public ?string $uploadUuid = null;

    public ?string $name = null;

    public function getFormalDateInstance(): ?\DateTimeImmutable
    {
        return $this->formalDate ? new \DateTimeImmutable($this->formalDate) : null;
    }
}
