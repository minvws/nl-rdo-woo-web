<?php

declare(strict_types=1);

namespace Shared\Service\Security\Session;

use Override;
use ParagonIE\Halite\Symmetric\Crypto;
use ParagonIE\Halite\Symmetric\EncryptionKey;
use ParagonIE\HiddenString\HiddenString;
use SensitiveParameter;
use SessionHandlerInterface;
use Symfony\Component\HttpFoundation\Session\Storage\Proxy\SessionHandlerProxy;

/**
 * Wrapper around a session handler that encrypts the session data so it will be encrypted at-rest (either on disk or in redis for instance).
 * This is useful when you are storing sensitive data in the session, like the user object, which holds unencrypted 2FA recovery codes.
 */
class EncryptedSessionProxy extends SessionHandlerProxy
{
    protected EncryptionKey $key;

    public function __construct(
        SessionHandlerInterface $handler,
        #[SensitiveParameter] string $key,
    ) {
        parent::__construct($handler);

        $this->key = new EncryptionKey(new HiddenString($key));
    }

    #[Override]
    public function read(#[SensitiveParameter] $sessionId): string
    {
        $data = parent::read($sessionId);
        if ($data === false || $data === '') {
            return '';
        }

        return Crypto::decrypt($data, $this->key)->getString();
    }

    #[Override]
    public function write(#[SensitiveParameter] $sessionId, #[SensitiveParameter] $data): bool
    {
        $data = Crypto::encrypt(new HiddenString($data), $this->key);

        return parent::write($sessionId, $data);
    }
}
