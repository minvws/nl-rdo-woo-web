<?php

declare(strict_types=1);

namespace Shared\Service\Encryption;

interface EncryptionServiceInterface
{
    public function encrypt(string $data): string;

    public function decrypt(string $data): string;
}
