<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\History;
use App\Service\HistoryService;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;

class HistoryServiceTest extends MockeryTestCase
{
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
