<?php

declare(strict_types=1);

namespace PublicationApi\Domain\Dossier;

use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Shared\ValueObject\Equatable;
use Webmozart\Assert\Assert;

/**
 * @implements Equatable<MetadataSnapshot>
 */
final readonly class MetadataSnapshot implements Equatable
{
    /**
     * @param array<string, mixed> $values
     */
    private function __construct(
        private array $values,
    ) {
    }

    public static function of(AbstractAttachment|AbstractMainDocument $entity): self
    {
        return new self([
            ...$entity->getMetadataSnapshot(),
            'fileName' => $entity->getFileInfo()->getName(),
        ]);
    }

    public static function ofNullable(AbstractAttachment|AbstractMainDocument|null $entity): ?self
    {
        return $entity === null ? null : self::of($entity);
    }

    public function equalTo(Equatable $other): bool
    {
        Assert::isInstanceOf($other, self::class);

        return $this->values === $other->values;
    }
}
