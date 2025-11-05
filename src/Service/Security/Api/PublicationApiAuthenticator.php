<?php

declare(strict_types=1);

namespace App\Service\Security\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class PublicationApiAuthenticator extends AbstractAuthenticator
{
    private const string SERVER_SSL_USERNAME_KEY = 'SSL_CLIENT_S_DN_CN';

    public function __construct(
        private readonly GlobDomainValidator $globDomainValidator,
        private readonly string $sslUserNameWhitelist,
    ) {
    }

    public function supports(Request $request): bool
    {
        return $request->server->has(self::SERVER_SSL_USERNAME_KEY);
    }

    public function authenticate(Request $request): Passport
    {
        $sslUserName = $request->server->get(self::SERVER_SSL_USERNAME_KEY);

        if (! \is_string($sslUserName)) {
            throw new AuthenticationException('Client Certificate Common Name is missing or invalid');
        }

        $whitelist = \explode(',', $this->sslUserNameWhitelist);
        if (! $this->globDomainValidator->isValid($whitelist, $sslUserName)) {
            throw new AuthenticationException('Client Certificate Common Name is invalid');
        }

        $userBadge = new UserBadge($sslUserName, static function () use ($sslUserName): ApiUser {
            return new ApiUser($sslUserName);
        });

        return new SelfValidatingPassport($userBadge);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): null
    {
        unset($request, $token, $firewallName);

        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        unset($request, $exception);

        return new JsonResponse(['message' => 'Authentication Failed'], Response::HTTP_UNAUTHORIZED);
    }
}
