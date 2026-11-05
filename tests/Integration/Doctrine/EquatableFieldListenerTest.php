<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Shared\Domain\Publication\Dossier\Event\DossierTitleChangedEvent;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Subject\LandingPageSlug;
use Shared\Domain\Publication\Subject\LandingPageTitle;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectContentNode;
use Shared\Domain\Publication\Subject\SubjectContentTree;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Factory\Publication\Subject\SubjectFactory;
use Shared\Tests\Integration\SharedWebTestCase;
use Shared\ValueObject\DossierTitle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webmozart\Assert\Assert;

final class EquatableFieldListenerTest extends SharedWebTestCase
{
    private EntityManagerInterface $entityManager;

    /** @var list<DossierTitleChangedEvent> */
    private array $titleChangedEvents = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = self::fromContainer(EntityManagerInterface::class);

        self::fromContainer(EventDispatcherInterface::class)->addListener(
            DossierTitleChangedEvent::class,
            function (DossierTitleChangedEvent $event): void {
                $this->titleChangedEvents[] = $event;
            },
        );
    }

    public function testFlushingAnEqualDossierTitleDoesNotUpdateTheDossier(): void
    {
        $dossier = $this->createWooDecisionLoadedFromDatabase();
        $originalTitle = $dossier->getTitle();

        $dossier->setTitle(DossierTitle::create($originalTitle->toString()));
        $this->entityManager->flush();

        self::assertSame($originalTitle, $dossier->getTitle());
        self::assertSame([], $this->titleChangedEvents);
    }

    public function testFlushingADifferentDossierTitleUpdatesTheDossier(): void
    {
        $dossier = $this->createWooDecisionLoadedFromDatabase();
        $newTitle = DossierTitle::create('A different title');

        $dossier->setTitle($newTitle);
        $this->entityManager->flush();

        self::assertSame($newTitle, $dossier->getTitle());
        self::assertCount(1, $this->titleChangedEvents);
    }

    public function testEqualLandingPageValuesDoNotProduceAChangeSet(): void
    {
        $subject = SubjectFactory::new()->withLandingPage(slug: 'equatable-subject')->create();
        $subject->setLandingPageContentTree(new SubjectContentTree(
            'Title',
            'Intro',
            [new SubjectContentNode('Node', 'Body')],
            'Outro',
        ));
        $this->entityManager->flush();
        $this->entityManager->clear();

        $subject = $this->entityManager->find(Subject::class, $subject->getId());
        Assert::isInstanceOf($subject, Subject::class);

        $slug = $subject->getLandingPageSlug();
        $title = $subject->getLandingPageTitle();
        $contentTree = $subject->getLandingPageContentTree();
        Assert::notNull($slug);
        Assert::notNull($title);
        Assert::notNull($contentTree);

        $subject->setLandingPageSlug(LandingPageSlug::create($slug->toString()));
        $subject->setLandingPageTitle(LandingPageTitle::create($title->toString()));
        $subject->setLandingPageContentTree(SubjectContentTree::fromArray($contentTree->toArray()));

        $unitOfWork = $this->entityManager->getUnitOfWork();
        $unitOfWork->computeChangeSets();

        self::assertSame([], $unitOfWork->getEntityChangeSet($subject));
        self::assertSame($slug, $subject->getLandingPageSlug());
        self::assertSame($title, $subject->getLandingPageTitle());
        self::assertSame($contentTree, $subject->getLandingPageContentTree());
    }

    private function createWooDecisionLoadedFromDatabase(): WooDecision
    {
        $id = WooDecisionFactory::createOne()->getId();
        $this->entityManager->clear();

        $dossier = $this->entityManager->find(WooDecision::class, $id);
        Assert::isInstanceOf($dossier, WooDecision::class);

        return $dossier;
    }
}
