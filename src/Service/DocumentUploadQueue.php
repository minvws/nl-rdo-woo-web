<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
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

    public function add(WooDecision $dossier, string $filename): void
    {
        $this->redis->lpush(
            $this->getListName($dossier),
            [$filename],
        );
    }

    public function remove(WooDecision $dossier, string $filename): void
    {
        $this->redis->lrem(
            $this->getListName($dossier),
            0,
            $filename,
        );
    }

    public function clear(WooDecision $dossier): void
    {
        $this->redis->del(
            $this->getListName($dossier)
        );
    }

    /**
     * @return string[]
     */
    public function getFilenames(WooDecision $dossier): array
    {
        return $this->redis->lrange(
            $this->getListName($dossier),
            0,
            -1,
        );
    }

    private function getListName(WooDecision $dossier): string
    {
        return 'uploads:dossier:' . $dossier->getId()->toRfc4122();
    }
}
