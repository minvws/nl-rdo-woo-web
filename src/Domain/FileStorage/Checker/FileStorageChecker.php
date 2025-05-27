<?php

declare(strict_types=1);

namespace App\Domain\FileStorage\Checker;

use App\Domain\FileStorage\Checker\PathSetFactory\PathSetsFactoryInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

readonly class FileStorageChecker
{
    /**
     * @param iterable<PathSetsFactoryInterface> $pathSetsFactories
     */
    public function __construct(
        private FileStorageLister $fileStorageLister,
        #[AutowireIterator('domain.filestorage.pathsetsfactory')]
        private iterable $pathSetsFactories,
    ) {
    }

    public function check(): FileStorageCheckResult
    {
        $result = new FileStorageCheckResult(
            new OrphanedPaths(),
            $this->getPathSets(),
        );

        foreach (FileStorageType::cases() as $fileStorageType) {
            $this->matchAllPathsForFileStorage($fileStorageType, $result);
        }

        return $result;
    }

    /**
     * @return PathSet[]
     */
    private function getPathSets(): array
    {
        $pathSets = [];
        foreach ($this->pathSetsFactories as $factory) {
            foreach ($factory->getPathSets() as $pathSet) {
                $pathSets[] = $pathSet;
            }
        }

        return $pathSets;
    }

    private function matchAllPathsForFileStorage(
        FileStorageType $fileStorageType,
        FileStorageCheckResult $result,
    ): void {
        foreach ($this->fileStorageLister->paths($fileStorageType) as $path => $size) {
            $this->matchFilePath($fileStorageType, $result, $path, $size);
        }
    }

    private function matchFilePath(
        FileStorageType $fileStorageType,
        FileStorageCheckResult $result,
        string $path,
        int $size,
    ): void {
        foreach ($result->pathSets as $pathSet) {
            if ($pathSet->matches($fileStorageType, $path, $size)) {
                return;
            }
        }

        $result->orphanedPaths->add($fileStorageType, $path, $size);
    }
}
