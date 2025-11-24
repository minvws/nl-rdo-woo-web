<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service;

use Doctrine\ORM\EntityManagerInterface;
use Mockery\MockInterface;
use Shared\Domain\Publication\History\History;
use Shared\Service\HistoryService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Uid\Uuid;

class HistoryServiceTest extends UnitTestCase
{
    private EntityManagerInterface&MockInterface $entityManager;
    private Translator&MockInterface $translator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = \Mockery::mock(EntityManagerInterface::class);
        $this->translator = \Mockery::mock(Translator::class);
    }

    public function testAddDossierEntry(): void
    {
        /** @var HistoryService&MockInterface $service */
        $service = \Mockery::mock(HistoryService::class, [
            $this->entityManager,
            $this->translator,
        ])
        ->shouldAllowMockingProtectedMethods()
        ->makePartial();

        $uuid = Uuid::v6();
        $key = 'my-key';
        $context = ['a' => 'b'];
        $mode = HistoryService::MODE_PUBLIC;

        $service
            ->shouldReceive('addEntry')
            ->with(HistoryService::TYPE_DOSSIER, $uuid, $key, $context, $mode, true)
            ->once();

        $service->addDossierEntry($uuid, $key, $context, $mode);
    }

    public function testHistoryTranslation(): void
    {
        $arrayLoader = new ArrayLoader();
        $translator = new Translator('en');
        $translator->addLoader('array', $arrayLoader);
        $translator->addResource('array', [
            'history.foo.bar.public' => 'PUB Hello {bar}',
            'history.foo.bar.private' => 'PRV Hello {bar}',
            'test' => 'vertaalde test',
            'c' => 'see',
        ], 'en');

        $service = new HistoryService(
            \Mockery::mock(EntityManagerInterface::class),
            $translator
        );

        // Single key translation
        $entity = new History();
        $entity->setContextKey('foo.bar');
        $entity->setContext(['bar' => 'test']);
        self::assertEquals('PUB Hello test', $service->translate($entity, HistoryService::MODE_PUBLIC));

        // / Private translation
        $entity = new History();
        $entity->setContextKey('foo.bar');
        $entity->setContext(['bar' => 'test']);
        self::assertEquals('PRV Hello test', $service->translate($entity, HistoryService::MODE_PRIVATE));

        // Translated key
        $entity = new History();
        $entity->setContextKey('foo.bar');
        $entity->setContext(['bar' => '%test%']);
        self::assertEquals('PRV Hello vertaalde test', $service->translate($entity, HistoryService::MODE_PRIVATE));

        // Multiple keys
        $entity = new History();
        $entity->setContextKey('foo.bar');
        $entity->setContext(['bar' => ['a', 'b', 'c', 'dee']]);
        self::assertEquals('PRV Hello a,b,c,dee', $service->translate($entity, HistoryService::MODE_PRIVATE));

        // Multiple keys with translation
        $entity = new History();
        $entity->setContextKey('foo.bar');
        $entity->setContext(['bar' => ['a', 'b', '%c%', 'dee']]);
        self::assertEquals('PRV Hello a,b,see,dee', $service->translate($entity, HistoryService::MODE_PRIVATE));
    }
}
