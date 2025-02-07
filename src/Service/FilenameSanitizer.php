<?php

declare(strict_types=1);

namespace App\Service;

class FilenameSanitizer extends \IndieHD\FilenameSanitizer\FilenameSanitizer
{
    public function stripAdditionalCharacters(): self
    {
        $this->setFilename(str_replace(["'", chr(127), '#'], '', $this->getFilename()));

        return $this;
    }

    public function stripPhp(): static
    {
        $this->setFilename(htmlspecialchars($this->getFilename()));

        return $this;
    }

    public function stripRiskyCharacters(): static
    {
        $filename = $this->getFilename();

        $filename = str_replace('`', '', $filename);
        $filename = strval(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $filename));
        $filename = strval(preg_replace('/[^a-zA-Z0-9._\-]/', '_', $filename));

        $this->setFilename($filename);

        return $this;
    }
}
