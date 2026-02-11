<?php

declare(strict_types=1);

namespace Shared\Domain\Upload\AntiVirus;

use RuntimeException;
use Shared\Service\PlatformCheck\PlatformCheckerInterface;
use Shared\Service\PlatformCheck\PlatformCheckResult;

readonly class ClamAvPlatformChecker implements PlatformCheckerInterface
{
    public function __construct(
        private ClamAvClientFactory $clientFactory,
    ) {
    }

    /**
     * @return PlatformCheckResult[]
     */
    public function getResults(): array
    {
        return [$this->checkClamAv()];
    }

    private function checkClamAv(): PlatformCheckResult
    {
        $description = 'Checking if ClamAV is working';
        $maliciousData = 'X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*';

        try {
            $result = $this->clientFactory->getClient()->scanStream($maliciousData);
        } catch (RuntimeException) {
            return PlatformCheckResult::error($description, 'Cannot connect to ClamAV');
        }

        if ($result->isFound()) {
            return PlatformCheckResult::success($description);
        }

        return PlatformCheckResult::error($description, 'Malicious data not reported by ClamAV');
    }
}
