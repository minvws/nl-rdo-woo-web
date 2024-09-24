<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\ViewModel;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\DossierReference;
use Webmozart\Assert\Assert;

readonly class RecentDossier
{
    public function __construct(
        public DossierReference $reference,
        public \DateTimeImmutable $publicationDate,
    ) {
    }

    public static function create(AbstractDossier $dossier): self
    {
        Assert::notNull($dossier->getPublicationDate());

        return new self(
            DossierReference::fromEntity($dossier),
            $dossier->getPublicationDate(),
        );
    }
}
