<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Handler;

use App\Domain\Upload\UploadedFile;

/**
 * @codeCoverageIgnore This trait will be removed in woo-3346, only exists to resolve duplicated code issues.
 */
trait AssembleChunksTrait
{
    protected function assembleChunks(string $chunkUuid, int $chunkCount, string $originalFilename): ?UploadedFile
    {
        $path = sprintf('%s/assembled-%s', sys_get_temp_dir(), $chunkUuid);
        $stitchedFile = new \SplFileObject($path, 'w');

        for ($i = 0; $i < $chunkCount; $i++) {
            // Check if the chunk exists
            $remoteChunkPath = '/uploads/chunks/' . $chunkUuid . '/' . $i;

            $localChunkFile = $this->entityStorageService->download($remoteChunkPath);
            if (! $localChunkFile) {
                $this->logger->error('Chunk is not readable', [
                    'uuid' => $chunkUuid,
                    'chunk' => $i,
                ]);

                return null;
            }

            // Add chunk to the stitched file
            $chunk = new \SplFileObject($localChunkFile);
            $chunk->rewind();
            while (! $chunk->eof()) {
                $data = $chunk->fread(1024 * 64);
                if ($data === false) {
                    continue;
                }
                $stitchedFile->fwrite($data);
            }

            // Unlink chunk, as we don't need it anymore
            $this->entityStorageService->removeDownload($localChunkFile);
            $chunk = null;
        }

        return UploadedFile::fromSplFile($stitchedFile->getFileInfo(), $originalFilename);
    }
}
