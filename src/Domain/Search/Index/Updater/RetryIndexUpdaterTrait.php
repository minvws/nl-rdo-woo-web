<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\Updater;

use Elastic\Elasticsearch\Exception\ClientResponseException;

trait RetryIndexUpdaterTrait
{
    private const MAX_RETRIES = 10;

    // Will retry a callable for a specified number of times. If the callable throws a ClientResponseException with a 409 code, it will
    // retry the callable. If the callable throws a ClientResponseException with a different code, it will throw the exception.
    // If the callable throws any other exception, it will throw the exception.
    private function retry(callable $fn): void
    {
        for ($retryCount = 0; $retryCount <= self::MAX_RETRIES; $retryCount++) {
            try {
                $fn();

                return;
            } catch (ClientResponseException $e) {
                if ($retryCount === self::MAX_RETRIES) {
                    $this->logger->error('[Elasticsearch] Too many retries', [
                        'message' => $e->getMessage(),
                        'code' => $e->getCode(),
                    ]);
                    throw $e;
                }
                if ($e->getCode() != 409) {
                    $this->logger->error('[Elasticsearch] An error occurred: {message}', [
                        'message' => $e->getMessage(),
                        'code' => $e->getCode(),
                    ]);
                    throw $e;
                }

                $waitMs = (int) ceil(min(100000 * (1.4 ** $retryCount), 5000000));
                $this->logger->notice('[Elasticsearch] Update dossier version mismatch. Retrying...', [
                    'waitMs' => $waitMs,
                ]);
                usleep($waitMs);
            }
        }
    }
}
