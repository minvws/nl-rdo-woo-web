<?php

declare(strict_types=1);

namespace Shared\Api\Admin\Attachment;

use ApiPlatform\Metadata\ApiProperty;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Symfony\Component\Validator\Constraints as Assert;
use Webmozart\Assert\Assert as WebmozartAssert;

class AttachmentCreateDto
{
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

    #[Assert\NotBlank(normalizer: 'trim')]
    public AttachmentType $type;

    public string $internalReference = '';

    #[Assert\NotBlank()]
    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'enum' => [AttachmentLanguage::DUTCH->value, AttachmentLanguage::ENGLISH->value],
        ],
    )]
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
