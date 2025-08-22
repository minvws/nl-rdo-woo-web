<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawReason;
use App\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use App\Service\Storage\StorageRootPathGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Document>
 */
final class DocumentFactory extends PersistentProxyObjectFactory
{
    public function __construct(
        private readonly StorageRootPathGenerator $storageRootPathGenerator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        $documentId = (string) self::faker()->unique()->randomNumber(nbDigits: 6, strict: true);

        $judgement = self::faker()
            ->optional(0.5, Judgement::PUBLIC)
            ->randomElement([
                Judgement::PARTIAL_PUBLIC,
                Judgement::ALREADY_PUBLIC,
                Judgement::NOT_PUBLIC,
            ]);

        return [
            'documentDate' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'familyId' => $documentId,
            'documentId' => $documentId,
            'threadId' => 0,
            'pageCount' => self::faker()->numberBetween(1, 20),
            'grounds' => self::faker()->groundsBetween(),
            'judgement' => $judgement,
            'links' => array_filter([$this->faker()->optional()->url()]),
            'remark' => $this->faker()->optional()->text(),

            'createdAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'updatedAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            ->beforeInstantiate(function (array $attributes): array {
                if (! isset($attributes['documentNr']) && is_scalar($attributes['documentId'])) {
                    $attributes['documentNr'] = sprintf('PREF-%s', $attributes['documentId']);
                }

                if (! isset($attributes['fileInfo']) && is_scalar($attributes['documentNr'])) {
                    $attributes['fileInfo'] = FileInfoFactory::new([
                        'name' => 'document-' . $attributes['documentNr'] . '.pdf',
                        'mimetype' => 'application/pdf',
                        'type' => 'pdf',
                        'uploaded' => in_array($attributes['judgement'], [Judgement::PUBLIC, Judgement::PARTIAL_PUBLIC], true),
                    ])->create();
                }

                return $attributes;
            })
            ->afterInstantiate(function (Document $document, array $attributes): void {
                if ($document->getFileInfo()->isUploaded()) {
                    $document
                        ->getFileInfo()
                        ->setPath(sprintf(
                            '%s/%s',
                            $this->storageRootPathGenerator->fromUuid($document->getId()),
                            $document->getFileInfo()->getName(),
                        ));
                }

                if (isset($attributes['overwrite_id'])) {
                    $this->entityManager->detach($document);

                    $reflection = new \ReflectionClass($document);
                    $property = $reflection->getProperty('id');
                    $property->setAccessible(true);
                    $property->setValue($document, $attributes['overwrite_id']);

                    $this->entityManager->persist($document);
                }
            });
    }

    public function removeFileProperties(): self
    {
        return $this->afterInstantiate(function (Document $document): void {
            $document->getFileInfo()->removeFileProperties();
        });
    }

    public function withdrawn(): self
    {
        return $this
            ->removeFileProperties()
            ->afterInstantiate(function (Document $document) {
                /** @var DocumentWithdrawReason $withdrawReason */
                $withdrawReason = self::faker()->randomElement(DocumentWithdrawReason::cases());

                $document->withdraw(
                    reason: $withdrawReason,
                    explanation: self::faker()->paragraph(),
                );
            });
    }

    public function withPublicJudgement(): self
    {
        return $this->with(['judgement' => Judgement::PUBLIC]);
    }

    public function withNotPublicJudgement(): self
    {
        return $this->with(['judgement' => Judgement::NOT_PUBLIC]);
    }

    public function withPartialPublicJudgement(): self
    {
        return $this->with(['judgement' => Judgement::PARTIAL_PUBLIC]);
    }

    public function withAlreadyPublicJudgement(): self
    {
        return $this->with(['judgement' => Judgement::ALREADY_PUBLIC]);
    }

    public static function class(): string
    {
        return Document::class;
    }
}
