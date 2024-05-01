<?php

declare(strict_types=1);

namespace App\Api\Admin\CovenantAttachment;

use ApiPlatform\Metadata\ApiProperty;
use App\Domain\Publication\Attachment\AttachmentLanguage;
use App\Domain\Publication\Attachment\AttachmentType;
use Symfony\Component\Validator\Constraints as Assert;
use Webmozart\Assert\Assert as WebmozartAssert;

final class CovenantAttachmentCreateDto
{
    #[Assert\NotBlank(normalizer: 'trim')]
    #[ApiProperty(writable: false, identifier: true, genId: false)]
    public string $name;

    #[Assert\NotBlank(normalizer: 'trim')]
    #[Assert\Date()]
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

    #[Assert\NotBlank(normalizer: 'trim')]
    public string $uploadUuid;

    #[Assert\NotBlank()]
    public AttachmentType $type;

    public ?string $internalReference = null;

    #[Assert\NotBlank()]
    public AttachmentLanguage $language;

    /** @var array<array-key,string> $grounds */
    #[Assert\All([
        new Assert\Type('string'),
        new Assert\NotBlank(),
    ])]
    public array $grounds = [];

    public function getFormalDateInstance(): \DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $this->formalDate);

        WebmozartAssert::notFalse($date);

        return $date;
    }
}
