<?php

declare(strict_types=1);

namespace Shared\Api\Admin\Attachment;

use ApiPlatform\Metadata\ApiProperty;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Symfony\Component\Validator\Constraints as Assert;
use Webmozart\Assert\Assert as WebmozartAssert;

class AttachmentUpdateDto
{
    #[Assert\NotBlank(allowNull: true, normalizer: 'trim')]
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

    #[Assert\NotBlank(allowNull: true, normalizer: 'trim')]
    public ?string $uploadUuid = null;

    public ?AttachmentType $type = null;

    public ?string $internalReference = null;

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'enum' => [AttachmentLanguage::DUTCH->value, AttachmentLanguage::ENGLISH->value],
        ],
    )]
    public ?AttachmentLanguage $language = null;

    /** @var ?array<array-key,string> $grounds */
    #[Assert\All([
        new Assert\Type('string'),
        new Assert\NotBlank(),
    ])]
    public ?array $grounds = null;

    /**
     * @phpstan-assert-if-true !null $this->formalDate
     * @phpstan-assert-if-true !null $this->getFormalDateInstance()
     */
    public function hasFormalDateInstance(): bool
    {
        return ! is_null($this->formalDate);
    }

    public function getFormalDateInstance(): ?\DateTimeImmutable
    {
        if (! $this->hasFormalDateInstance()) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $this->formalDate);

        WebmozartAssert::notFalse($date);

        return $date;
    }
}
