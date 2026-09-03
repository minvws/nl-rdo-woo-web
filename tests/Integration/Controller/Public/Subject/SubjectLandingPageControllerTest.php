<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Controller\Public\Subject;

use Doctrine\ORM\EntityManagerInterface;
use Shared\Domain\Publication\Subject\LandingPageSlug;
use Shared\Domain\Publication\Subject\LandingPageTitle;
use Shared\Domain\Publication\Subject\SubjectContentNode;
use Shared\Domain\Publication\Subject\SubjectLandingPageStatus;
use Shared\Tests\Factory\Publication\Subject\SubjectFactory;
use Shared\Tests\Integration\SharedWebTestCase;
use Symfony\Component\HttpFoundation\Response;

use function sprintf;
use function strpos;

final class SubjectLandingPageControllerTest extends SharedWebTestCase
{
    public function testPublishedLandingPageRendersNestedContentInOrderAndSafelyRendersMarkdown(): void
    {
        $client = static::createClient();

        $subject = SubjectFactory::createOne([
            'name' => 'Vaccinaties en medicatie',
        ]);
        $subject->setLandingPage(
            LandingPageSlug::create('vaccinaties-en-medicatie'),
            LandingPageTitle::create('Onderwerp landing page'),
            "Eerste regel\nTweede regel",
            SubjectLandingPageStatus::PUBLISHED,
            [
                new SubjectContentNode(
                    'Eerste niveau',
                    '**Veilige body** <script>alert(1)</script> [Onveilige link](javascript:alert(2))',
                    [
                        new SubjectContentNode(
                            'Tweede niveau',
                            'Tweede body',
                            [
                                new SubjectContentNode(
                                    'Derde niveau',
                                    'Derde body',
                                    [
                                        new SubjectContentNode(
                                            'Vierde niveau',
                                            'Vierde body',
                                            [
                                                new SubjectContentNode(
                                                    'Vijfde niveau',
                                                    'Vijfde body',
                                                    [
                                                        new SubjectContentNode('Afgesneden niveau', 'Niet zichtbaar'),
                                                    ],
                                                ),
                                            ],
                                        ),
                                    ],
                                ),
                            ],
                        ),
                    ],
                ),
                new SubjectContentNode('Tweede root', 'Tweede root body'),
            ],
        );
        $subject->setHasVisibleLandingPageContentTree(true);
        self::fromContainer(EntityManagerInterface::class)->flush();

        $client->request('GET', '/onderwerp/vaccinaties-en-medicatie');

        self::assertResponseIsSuccessful();
        $content = (string) $client->getResponse()->getContent();

        self::assertStringContainsString('Onderwerp landing page', $content);
        self::assertStringContainsString('Eerste regel', $content);
        self::assertStringContainsString('Tweede regel', $content);
        self::assertStringContainsString('class="bg-woo-gray-100 p-6 my-10"', $content);
        self::assertStringContainsString('class="woo-readable-width woo-rich-text"', $content);
        self::assertStringContainsString('<h2 class="woo-h3">Eerste niveau</h2>', $content);
        self::assertStringContainsString('<h3 class="font-bold">Tweede niveau</h3>', $content);
        self::assertStringContainsString('<h4 class="font-bold">Derde niveau</h4>', $content);
        self::assertStringContainsString('<h5 class="font-bold">Vierde niveau</h5>', $content);
        self::assertStringContainsString('<h6 class="font-bold">Vijfde niveau</h6>', $content);
        self::assertStringContainsString('<span class="font-bold">Veilige body</span>', $content);
        self::assertStringNotContainsString('<script>', $content);
        self::assertStringNotContainsString('javascript:', $content);
        self::assertStringNotContainsString('Afgesneden niveau', $content);
        self::assertLessThan(
            strpos($content, 'Tweede root'),
            strpos($content, 'Eerste niveau'),
        );
    }

    public function testUnpublishedLandingPageIsNotPubliclyAccessible(): void
    {
        $client = static::createClient();

        $subject = SubjectFactory::createOne([
            'name' => 'Concept onderwerp',
        ]);
        $subject->setLandingPage(
            LandingPageSlug::create('concept-onderwerp'),
            LandingPageTitle::create('Concept landing page'),
            'Concept description',
            SubjectLandingPageStatus::CONCEPT,
            [],
        );
        self::fromContainer(EntityManagerInterface::class)->flush();

        $client->request('GET', '/onderwerp/concept-onderwerp');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testPublishedLandingPageWithEmptyContentTreeRenders(): void
    {
        $client = static::createClient();

        $subject = SubjectFactory::createOne([
            'name' => 'Leeg onderwerp',
        ]);
        $subject->setLandingPage(
            LandingPageSlug::create('leeg-onderwerp'),
            LandingPageTitle::create('Lege landing page'),
            'Description zonder secties',
            SubjectLandingPageStatus::PUBLISHED,
            [],
        );
        self::fromContainer(EntityManagerInterface::class)->flush();

        $client->request('GET', '/onderwerp/leeg-onderwerp');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Lege landing page', (string) $client->getResponse()->getContent());
        self::assertStringNotContainsString('woo-accordion-list', (string) $client->getResponse()->getContent());
    }

    public function testContentTreeIsHiddenWhenItIsNotMarkedAsVisible(): void
    {
        $client = static::createClient();

        $subject = SubjectFactory::createOne([
            'name' => 'Verborgen verhaallijn',
        ]);
        $subject->setLandingPage(
            LandingPageSlug::create('verborgen-verhaallijn'),
            LandingPageTitle::create('Verborgen verhaallijn'),
            'Description met verborgen secties',
            SubjectLandingPageStatus::PUBLISHED,
            [new SubjectContentNode('Verborgen sectie', 'Verborgen body')],
        );
        self::fromContainer(EntityManagerInterface::class)->flush();

        $client->request('GET', '/onderwerp/verborgen-verhaallijn');

        self::assertResponseIsSuccessful();
        $content = (string) $client->getResponse()->getContent();

        self::assertStringContainsString('Description met verborgen secties', $content);
        self::assertStringNotContainsString('woo-accordion-list', $content);
        self::assertStringNotContainsString('Verborgen sectie', $content);
    }

    public function testConceptLandingPageIsAvailableThroughPreviewUrlOnly(): void
    {
        $client = static::createClient();

        $subject = SubjectFactory::createOne([
            'name' => 'Preview onderwerp',
        ]);
        $subject->setLandingPage(
            LandingPageSlug::create('preview-onderwerp'),
            LandingPageTitle::create('Preview landing page'),
            'Preview description',
            SubjectLandingPageStatus::CONCEPT,
            [new SubjectContentNode('Preview section', 'Preview body')],
        );
        self::fromContainer(EntityManagerInterface::class)->flush();

        $client->request(
            'GET',
            sprintf(
                '/onderwerp/%s/preview/%s',
                $subject->getId(),
                $subject->getLandingPagePreviewToken(),
            ),
        );

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('private', (string) $client->getResponse()->headers->get('Cache-Control'));
        self::assertStringContainsString('no-store', (string) $client->getResponse()->headers->get('Cache-Control'));
        $this->assertResponseHeaderSame('X-Robots-Tag', 'noindex, nofollow');
        self::assertStringContainsString('Preview landing page', (string) $client->getResponse()->getContent());
    }

    public function testPreviewWithInvalidTokenIsNotAccessible(): void
    {
        $client = static::createClient();

        $subject = SubjectFactory::createOne([
            'name' => 'Preview onderwerp',
        ]);
        $subject->setLandingPage(
            LandingPageSlug::create('preview-onderwerp'),
            LandingPageTitle::create('Preview landing page'),
            'Preview description',
            SubjectLandingPageStatus::CONCEPT,
            [],
        );
        self::fromContainer(EntityManagerInterface::class)->flush();

        $client->request(
            'GET',
            sprintf(
                '/onderwerp/%s/preview/%s',
                $subject->getId(),
                $this->getFaker()->uuid(),
            ),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
