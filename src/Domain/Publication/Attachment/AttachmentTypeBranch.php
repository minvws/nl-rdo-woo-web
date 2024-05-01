<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @phpstan-type AttachmentTypeBranchArray array{
 *   type: string,
 *   label: string,
 *   subbranch: mixed,
 *   attachmentTypes: array<int, array<string, string>>
 * }
 */
final readonly class AttachmentTypeBranch
{
    /**
     * @param array<int,AttachmentType> $attachmentTypes
     */
    public function __construct(
        public string $name,
        public ?AttachmentTypeBranch $branch = null,
        public array $attachmentTypes = [],
    ) {
        if ($branch === null && count($attachmentTypes) === 0) {
            throw AttachmentTypeBranchException::mandatoryArguments();
        }
    }

    /**
     * @phpstan-assert-if-true !null $this->branch
     */
    public function hasBranch(): bool
    {
        return ! is_null($this->branch);
    }

    /**
     * @phpstan-assert-if-true non-empty-array<int,AttachmentType> $this->attachmentTypes
     */
    public function hasAttachmentTypes(): bool
    {
        return count($this->attachmentTypes) > 0;
    }

    /**
     * @phpstan-return AttachmentTypeBranchArray
     *
     * @return array<string,mixed>
     */
    public function toArray(TranslatorInterface $translator): array
    {
        return [
            'type' => 'AttachmentTypeBranch',
            'label' => $this->name,
            'subbranch' => $this->branch?->toArray($translator),
            'attachmentTypes' => $this->hasAttachmentTypes()
                ? array_map(
                    static fn (AttachmentType $attachmentType): array => $attachmentType->toArray($translator),
                    $this->attachmentTypes
                )
                : [],
        ];
    }
}
