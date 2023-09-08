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
}
