<?php

declare(strict_types=1);

namespace App\Service\PlatformCheck;

use App\Service\Worker\Pdf\Tools\Pdftk\PdftkService;

readonly class ExecutablePlatformChecker implements PlatformCheckerInterface
{
    private const REQUIRED_EXECUTABLES = [
        '/usr/bin/tesseract',
        PdftkService::PDFTK_PATH,
        '/usr/bin/pdfseparate',
        '/usr/bin/pdftoppm',
        '/usr/bin/7za',
        '/usr/bin/xlsx2csv',
    ];

    /**
     * @param string[] $executables
     *
     * @return PlatformCheckResult[]
     */
    public function getResults(array $executables = self::REQUIRED_EXECUTABLES): array
    {
        $results = [];
        foreach ($executables as $path) {
            $results[] = $this->checkExecutable($path);
        }

        return $results;
    }

    protected function checkExecutable(string $path): PlatformCheckResult
    {
        $description = 'Checking if ' . $path . ' is executable';

        if (is_executable($path)) {
            return PlatformCheckResult::success($description);
        }

        return PlatformCheckResult::error($description, 'not executable');
    }
}
