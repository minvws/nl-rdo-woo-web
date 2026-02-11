<?php

declare(strict_types=1);

namespace Shared\Tests\Factory;

use DateTimeImmutable;
use Override;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Inquiry>
 */
final class InquiryFactory extends PersistentObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
    }

    public static function class(): string
    {
        return Inquiry::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @return array<string,mixed>
     */
    protected function defaults(): array|callable
    {
        $org = LazyValue::memoize(fn () => OrganisationFactory::createOne());

        return [
            'casenr' => self::faker()->text(255),
            'createdAt' => DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'updatedAt' => DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'organisation' => $org,
            'documents' => DocumentFactory::new()->range(0, 3),
            'dossiers' => WooDecisionFactory::new([
                'organisation' => $org,
            ])->range(0, 2),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[Override]
    protected function initialize(): static
    {
        return $this
            ->afterPersist(function (Inquiry $inquiry) {
                $inquiry->setInventory(InquiryInventoryFactory::new()->create([
                    'inquiry' => $inquiry,
                ]));
            });
    }
}
