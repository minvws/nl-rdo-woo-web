<?php

declare(strict_types=1);

namespace Shared\Tests\Factory;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use ReflectionClass;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawReason;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Service\Storage\StorageRootPathGenerator;
use Shared\ValueObject\ExternalId;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

use function array_filter;
use function in_array;
use function is_scalar;
use function sprintf;

/**
 * @extends PersistentObjectFactory<Document>
 */
final class DocumentFactory extends PersistentObjectFactory
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
            'documentDate' => DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'familyId' => $documentId,
            'documentId' => $documentId,
            'externalId' => self::faker()->boolean() ? ExternalId::create(self::faker()->uuid()) : null,
            'threadId' => 0,
            'grounds' => self::faker()->groundsBetween(),
            'judgement' => $judgement,
            'links' => array_filter([$this->faker()->optional()->url()]),
            'remark' => $this->faker()->optional()->text(),

            'createdAt' => DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'updatedAt' => DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[Override]
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

                    $reflection = new ReflectionClass($document);
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
