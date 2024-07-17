<?php

declare(strict_types=1);

namespace App\Domain\Ingest\TikaOnly;

use App\Domain\Search\Index\SubType\SubTypeIndexer;
use App\Entity\EntityWithFileInfo;
use App\Service\Storage\EntityStorageService;
use App\Service\Worker\Pdf\Tools\TikaService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Webmozart\Assert\Assert;

#[AsMessageHandler]
final readonly class IngestTikaOnlyHandler
{
    public function __construct(
        private EntityManagerInterface $doctrine,
        private EntityStorageService $entityStorage,
        private LoggerInterface $logger,
        private TikaService $tika,
        private SubTypeIndexer $subTypeIndexer,
    ) {
    }

    public function __invoke(IngestTikaOnlyCommand $message): void
    {
        $entity = $this->doctrine->getRepository($message->getEntityClass())->find($message->getEntityId());
        if (is_null($entity)) {
            $this->logger->warning('No entity found in IngestTikaOnlyHandler', [
                'id' => $message->getEntityId(),
                'class' => $message->getEntityClass(),
            ]);

            return;
        }

        /** @var EntityWithFileInfo $entity */
        Assert::isInstanceOf($entity, EntityWithFileInfo::class);
        Assert::true($entity->getFileInfo()->isUploaded(), 'Entity is not uploaded');

        $localFile = $this->entityStorage->downloadEntity($entity);
        if ($localFile === false) {
            $this->logger->warning('Entity file could not be downloaded', [
                'id' => $message->getEntityId(),
                'class' => $message->getEntityClass(),
            ]);

            return;
        }

        $tikaData = $this->tika->extract($localFile, $entity->getFileInfo()->getMimetype() ?? '');
        $content = $tikaData['X-TIKA:content'] ?? '';

        try {
            $this->subTypeIndexer->updatePage($entity, 0, $content);
        } catch (\Exception $e) {
            $this->logger->error('Failed to index tika content as page', [
                'id' => $entity->getId(),
                'class' => $entity::class,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
