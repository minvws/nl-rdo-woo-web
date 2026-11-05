<?php

declare(strict_types=1);

namespace Shared\Domain\Ingest\Content;

interface FileReferenceInterface
{
    /**
     * @return resource
     */
    public function getStream();
}
