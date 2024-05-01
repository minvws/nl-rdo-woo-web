<?php

declare(strict_types=1);

namespace App\Api\Admin\CovenantAttachment;

use ApiPlatform\Metadata\ApiProperty;
use App\Domain\Publication\Attachment\AttachmentLanguage;
use App\Domain\Publication\Attachment\AttachmentType;
use Symfony\Component\Validator\Constraints as Assert;
use Webmozart\Assert\Assert as WebmozartAssert;

final class CovenantAttachmentUpdateDto
{
    #[Assert\NotBlank(allowNull: true, normalizer: 'trim')]
    public ?string $name = null;

    #[Assert\NotBlank(allowNull: true, normalizer: 'trim')]
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
    public ?string $formalDate = null;

    public ?AttachmentType $type = null;

    public ?string $internalReference = null;

    public ?AttachmentLanguage $language = null;

    /** @var ?array<array-key,string> $grounds */
    #[Assert\All([
        new Assert\Type('string'),
        new Assert\NotBlank(),
    ])]
    public ?array $grounds = null;

    public ?string $uploadUuid = null;

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
