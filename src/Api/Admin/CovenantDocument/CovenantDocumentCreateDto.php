<?php

declare(strict_types=1);

namespace App\Api\Admin\CovenantDocument;

use ApiPlatform\Metadata\ApiProperty;
use App\Domain\Publication\Attachment\AttachmentLanguage;
use Symfony\Component\Validator\Constraints as Assert;

class CovenantDocumentCreateDto
{
    #[Assert\NotBlank]
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
    public string $formalDate;

    public string $internalReference = '';

    #[Assert\NotNull]
    public AttachmentLanguage $language;

    /**
     * @var string[] $grounds
     */
    #[Assert\All([
        new Assert\Type('string'),
        new Assert\NotBlank(),
    ])]
    public array $grounds = [];

    #[Assert\NotBlank]
    public string $uploadUuid;

    #[Assert\NotBlank]
    public string $name;

    public function getFormalDateInstance(): \DateTimeImmutable
    {
        return new \DateTimeImmutable($this->formalDate);
    }
}
