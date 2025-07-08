<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Domain\Content\Page\ContentPage;
use App\Domain\Content\Page\ContentPageType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class ContentPageFixtures extends Fixture implements FixtureGroupInterface
{
    /**
     * @return list<string>
     */
    public static function getGroups(): array
    {
        return ['example'];
    }

    public function load(ObjectManager $manager): void
    {
        foreach (ContentPageType::cases() as $type) {
            $entity = new ContentPage(
                slug: $type->getSlug(),
                title: $type->getDefaultTitle(),
                content: sprintf('Dit is de content van **%s**', $type->getSlug()),
            );
            $manager->persist($entity);
        }

        $manager->flush();
    }
}
