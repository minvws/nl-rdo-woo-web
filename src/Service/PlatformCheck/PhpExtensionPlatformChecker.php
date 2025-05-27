<?php

declare(strict_types=1);

namespace App\Service\PlatformCheck;

readonly class PhpExtensionPlatformChecker implements PlatformCheckerInterface
{
    private const array REQUIRED_EXTENSIONS = ['amqp', 'json', 'pdo_pgsql', 'intl', 'zip'];

    /**
     * @param string[] $requiredExtensions
     *
     * @return PlatformCheckResult[]
     */
    public function getResults(array $requiredExtensions = self::REQUIRED_EXTENSIONS): array
    {
        $results = [];
        foreach ($requiredExtensions as $extension) {
            $results[] = $this->checkExtension($extension);
        }

        return $results;
    }

    protected function checkExtension(string $extension): PlatformCheckResult
    {
        $name = strtoupper($extension);
        $description = 'Checking if PHP extension ' . $name . ' is loaded';

        if (extension_loaded($extension)) {
            return PlatformCheckResult::success($description);
        }

        return PlatformCheckResult::error($description, 'not loaded');
    }
}
