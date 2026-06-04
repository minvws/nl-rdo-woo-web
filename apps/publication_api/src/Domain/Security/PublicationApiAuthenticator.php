<?php

declare(strict_types=1);

namespace PublicationApi\Domain\Security;

use Shared\Service\Security\ApiUser;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

use function explode;
use function is_string;
use function preg_match;
use function trim;

class PublicationApiAuthenticator extends AbstractAuthenticator
{
    private const string SERVER_SSL_USERNAME_KEY = 'SSL_CLIENT_S_DN_CN';
    private const string SERVER_SSL_CLIENT_VERIFY_KEY = 'SSL_CLIENT_VERIFY';
    private const string SERVER_SSL_CLIENT_S_DN_KEY = 'SSL_CLIENT_S_DN';

    public function __construct(
        private readonly GlobDomainValidator $globDomainValidator,
        #[Autowire(param: 'publication_api_ssl_username_whitelist')]
        private readonly string $sslUserNameWhitelist,
        #[Autowire(param: 'publication_api_ssl_organization_identifier')]
        private readonly string $sslOrganizationIdentifier,
    ) {
    }

    public function supports(Request $request): bool
    {
        return $request->server->has(self::SERVER_SSL_USERNAME_KEY);
    }

    public function authenticate(Request $request): Passport
    {
        $this->checkVerified($request);

        $this->checkOrganizationIdentifier($request);

        $sslUserName = $this->checkSslUsername($request);

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
        unset($request);

        return new JsonResponse(
            [
                'title' => 'Authentication Failed',
                'message' => $exception->getMessage(),
                'type' => 'errors/authentication-failed',
                'status' => Response::HTTP_UNAUTHORIZED,
            ],
            Response::HTTP_UNAUTHORIZED,
            [
                'Content-Type' => 'application/problem+json',
            ],
        );
    }

    private function extractOrganizationIdentifier(string $dn): ?string
    {
        return preg_match('/organizationIdentifier\s*=\s*([^,]+)/i', $dn, $matches)
            ? trim($matches[1])
            : null;
    }

    private function checkVerified(Request $request): void
    {
        $sslClientVerify = $request->server->get(self::SERVER_SSL_CLIENT_VERIFY_KEY);

        if ($sslClientVerify !== 'SUCCESS') {
            throw new AuthenticationException('Client Certificate is not verified or invalid');
        }
    }

    private function checkOrganizationIdentifier(Request $request): void
    {
        $sslClientDn = $request->server->get(self::SERVER_SSL_CLIENT_S_DN_KEY);

        $organizationIdentifier = is_string($sslClientDn) ? $this->extractOrganizationIdentifier($sslClientDn) : null;
        if ($organizationIdentifier === null || $organizationIdentifier !== $this->sslOrganizationIdentifier) {
            throw new AuthenticationException('Client Certificate Organization Identifier is missing or invalid');
        }
    }

    private function checkSslUsername(Request $request): string
    {
        $sslUserName = $request->server->get(self::SERVER_SSL_USERNAME_KEY);

        if (! is_string($sslUserName)) {
            throw new AuthenticationException('Client Certificate Common Name is missing or invalid');
        }

        $whitelist = explode(',', $this->sslUserNameWhitelist);
        if (! $this->globDomainValidator->isValid($whitelist, $sslUserName)) {
            throw new AuthenticationException(
                'Client Certificate Common Name is not whitelisted. Please read the documentation or contact your system administrator.',
            );
        }

        return $sslUserName;
    }
}
