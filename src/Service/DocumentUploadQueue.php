<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Dossier;
use Predis\ClientInterface;

/**
 * This queue is used to display the list of uploaded documents that are waiting on being processed by a worker.
 */
class DocumentUploadQueue
{
    public function __construct(
        private readonly ClientInterface $redis,
    ) {
    }

    public function add(Dossier $dossier, string $filename): void
    {
        $this->redis->lpush(
            $this->getListName($dossier),
            [$filename],
        );
    }

    public function remove(Dossier $dossier, string $filename): void
    {
        $this->redis->lrem(
            $this->getListName($dossier),
            0,
            $filename,
        );
    }

    public function clear(Dossier $dossier): void
    {
        $this->redis->del(
            $this->getListName($dossier)
        );
    }

    /**
     * @return string[]
     */
    public function getFilenames(Dossier $dossier): array
    {
        return $this->redis->lrange(
            $this->getListName($dossier),
            0,
            -1,
        );
    }

    private function getListName(Dossier $dossier): string
    {
        if (! $dossier->getId()) {
            throw new \RuntimeException('Dossier has no ID');
        }

        return 'uploads:dossier:' . $dossier->getId()->toRfc4122();
    }
}
