<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use App\Service\Storage\StorageRootPathGenerator;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Document>
 */
final class DocumentFactory extends PersistentProxyObjectFactory
{
    public function __construct(private StorageRootPathGenerator $storageRootPathGenerator)
    {
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        $documentId = self::faker()->unique()->randomNumber(nbDigits: 6, strict: true);
        $documentNr = sprintf('PREF-%s', $documentId);

        $judgement = self::faker()
            ->optional(0.5, Judgement::PUBLIC)
            ->randomElement([
                Judgement::PARTIAL_PUBLIC,
                Judgement::ALREADY_PUBLIC,
                Judgement::NOT_PUBLIC,
            ]);
        $uploaded = in_array($judgement, [Judgement::PUBLIC, Judgement::PARTIAL_PUBLIC], true);

        return [
            'documentDate' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'documentNr' => $documentNr,
            'familyId' => $documentId,
            'documentId' => (string) $documentId,
            'threadId' => 0,
            'pageCount' => self::faker()->numberBetween(1, 20),
            'summary' => self::faker()->paragraph(),
            'grounds' => self::faker()->groundsBetween(),
            'judgement' => $judgement,
            'fileInfo' => FileInfoFactory::new([
                'name' => 'document-' . $documentNr . '.pdf',
                'mimetype' => 'application/pdf',
                'type' => 'pdf',
                'uploaded' => $uploaded,
            ]),
            'links' => array_filter([$this->faker()->optional()->url()]),
            'remark' => $this->faker()->optional()->text(),

            'createdAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'updatedAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this->afterInstantiate(function (Document $document): void {
            $document
                ->getFileInfo()
                ->setPath(sprintf(
                    '%s/%s',
                    $this->storageRootPathGenerator->fromUuid($document->getId()),
                    $document->getFileInfo()->getName(),
                ));
        });
    }

    public static function class(): string
    {
        return Document::class;
    }
}
