<?php

declare(strict_types=1);

namespace App\Domain\WooIndex;

use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

readonly class WooIndexFileManager
{
    public function __construct(
        private Filesystem $filesystem,
        private LoggerInterface $logger,
        private WooIndexFinderFactory $wooIndexFinderFactory,
        private string $wooIndexDir,
    ) {
    }

    public function publish(string $source): string|false
    {
        $target = sprintf('%s/%s', $this->wooIndexDir, basename($source));

        try {
            $this->filesystem->mirror($source, $target, options: ['override' => true, 'delete' => true]);

            $this->filesystem->remove(sprintf('%s/..', $source));
        } catch (IOException $e) {
            $this->logger->error('Could not move generated sitemap', [
                'exception' => $e->getMessage(),
                'path' => $e->getPath(),
            ]);

            return false;
        }

        return $target;
    }

    public function getLastPublished(): ?string
    {
        $finder = $this->wooIndexFinderFactory->create($this->wooIndexDir);

        foreach ($finder as $dir) {
            return $dir->getBasename();
        }

        return null;
    }

    public function cleanupPublished(int $treshold = 1): void
    {
        if ($treshold < 1) {
            throw DiWooInvalidArgumentException::invalidTreshold($treshold);
        }

        $finder = $this->wooIndexFinderFactory->create($this->wooIndexDir);

        try {
            $i = 0;
            foreach ($finder as $dir) {
                if ($treshold > $i++) {
                    continue;
                }

                $this->filesystem->remove($dir->getPathname());
            }
        } catch (IOException $e) {
            $this->logger->error('Could not remove generated sitemap', [
                'exception' => $e->getMessage(),
                'path' => $e->getPath(),
            ]);
        }
    }
}
