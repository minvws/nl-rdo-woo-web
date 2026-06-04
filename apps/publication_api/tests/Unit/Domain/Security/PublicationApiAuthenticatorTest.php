<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Domain\Security;

use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PublicationApi\Domain\Security\GlobDomainValidator;
use PublicationApi\Domain\Security\PublicationApiAuthenticator;
use Shared\Service\Security\ApiUser;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class PublicationApiAuthenticatorTest extends UnitTestCase
{
    private const string SSL_CN = 'SSL_CLIENT_S_DN_CN';
    private const string SSL_VERIFY = 'SSL_CLIENT_VERIFY';
    private const string SSL_DN = 'SSL_CLIENT_S_DN';

    private const string VALID_USERNAME = 'valid.minvws.nl';
    private const string VALID_WHITELIST = '*.minvws.nl';
    private const string VALID_OIN = 'NTRNL-99999994';
    private const string VALID_DN = 'C=NL, O=TRIAL VWS, CN=TRIAL VWS, organizationIdentifier=NTRNL-99999994';

    public function testSupportsWithSslUserName(): void
    {
        $request = new Request(server: [self::SSL_CN => 'string']);

        $this->assertTrue($this->buildAuthenticator()->supports($request));
    }

    public function testSupportsWithoutSslUserName(): void
    {
        $this->assertFalse($this->buildAuthenticator()->supports(new Request()));
    }

    /**
     * @return array<string, array{serverParams: array<string, string>}>
     */
    public static function invalidSslClientVerifyProvider(): array
    {
        $base = [self::SSL_CN => self::VALID_USERNAME, self::SSL_DN => self::VALID_DN];

        return [
            'verify missing' => ['serverParams' => $base],
            'verify is FAILED' => ['serverParams' => $base + [self::SSL_VERIFY => 'FAILED']],
            'verify is NONE' => ['serverParams' => $base + [self::SSL_VERIFY => 'NONE']],
            'verify is false' => ['serverParams' => $base + [self::SSL_VERIFY => 'false']],
            'verify is empty' => ['serverParams' => $base + [self::SSL_VERIFY => '']],
        ];
    }

    /**
     * @param array<string, string> $serverParams
     */
    #[DataProvider('invalidSslClientVerifyProvider')]
    public function testAuthenticateThrowsWhenSslClientVerifyIsNotSuccess(array $serverParams): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('not verified');

        $this->buildAuthenticator()->authenticate(new Request(server: $serverParams));
    }

    /**
     * @return array<string, array{serverParams: array<string, string>}>
     */
    public static function invalidOrganizationIdentifierProvider(): array
    {
        $base = [self::SSL_CN => self::VALID_USERNAME, self::SSL_VERIFY => 'SUCCESS'];

        return [
            'DN missing' => ['serverParams' => $base],
            'organizationIdentifier absent from DN' => [
                'serverParams' => $base + [self::SSL_DN => 'C=NL, O=TRIAL VWS, CN=TRIAL VWS'],
            ],
            'organizationIdentifier wrong OIN' => [
                'serverParams' => $base + [self::SSL_DN => 'C=NL, O=TRIAL BZK, CN=TRIAL BZK, organizationIdentifier=NTRNL-99999992'],
            ],
        ];
    }

    /**
     * @param array<string, string> $serverParams
     */
    #[DataProvider('invalidOrganizationIdentifierProvider')]
    public function testAuthenticateThrowsWhenOrganizationIdentifierIsInvalid(array $serverParams): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Organization Identifier');

        $this->buildAuthenticator()->authenticate(new Request(server: $serverParams));
    }

    public function testAuthenticateThrowsWhenSslUserNameIsMissing(): void
    {
        $request = new Request(server: [
            self::SSL_VERIFY => 'SUCCESS',
            self::SSL_DN => self::VALID_DN,
            // SSL_CLIENT_S_DN_CN intentionally absent
        ]);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Common Name is missing or invalid');

        $this->buildAuthenticator()->authenticate($request);
    }

    public function testAuthenticateThrowsWhenCommonNameIsNotWhitelisted(): void
    {
        $sslUserName = 'attacker.example.com';

        $globDomainValidator = Mockery::mock(GlobDomainValidator::class);
        $globDomainValidator->expects('isValid')
            ->with([self::VALID_WHITELIST], $sslUserName)
            ->andReturn(false);

        $request = new Request(server: [
            self::SSL_CN => $sslUserName,
            self::SSL_VERIFY => 'SUCCESS',
            self::SSL_DN => self::VALID_DN,
        ]);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('not whitelisted');

        $this->buildAuthenticator($globDomainValidator)->authenticate($request);
    }

    public function testAuthenticateReturnsPassportWithCorrectUserOnValidRequest(): void
    {
        $globDomainValidator = Mockery::mock(GlobDomainValidator::class);
        $globDomainValidator->expects('isValid')
            ->with([self::VALID_WHITELIST], self::VALID_USERNAME)
            ->andReturn(true);

        $passport = $this->buildAuthenticator($globDomainValidator)->authenticate(new Request(server: [
            self::SSL_CN => self::VALID_USERNAME,
            self::SSL_VERIFY => 'SUCCESS',
            self::SSL_DN => self::VALID_DN,
        ]));

        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);
        $this->assertInstanceOf(ApiUser::class, $passport->getUser());
        $this->assertEquals(self::VALID_USERNAME, $passport->getUser()->getUserIdentifier());
    }

    /**
     * @return array<string, array{dn: string}>
     */
    public static function dnWithValidOinProvider(): array
    {
        return [
            'OIN at end of DN' => ['dn' => 'C=NL, O=TRIAL VWS, CN=TRIAL VWS, organizationIdentifier=NTRNL-99999994'],
            'OIN in middle of DN' => ['dn' => 'C=NL, organizationIdentifier=NTRNL-99999994, CN=TRIAL VWS'],
            'OIN with extra fields' => [
                'dn' => 'C=NL, OU=Digikoppeling, CN=TRIAL VWS, organizationIdentifier=NTRNL-99999994, emailAddress=test@example.com',
            ],
        ];
    }

    #[DataProvider('dnWithValidOinProvider')]
    public function testAuthenticateExtractsOinFromVariousDnFormats(string $dn): void
    {
        $globDomainValidator = Mockery::mock(GlobDomainValidator::class);
        $globDomainValidator->expects('isValid')->andReturn(true);

        $passport = $this->buildAuthenticator($globDomainValidator)->authenticate(new Request(server: [
            self::SSL_CN => self::VALID_USERNAME,
            self::SSL_VERIFY => 'SUCCESS',
            self::SSL_DN => $dn,
        ]));

        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);
    }

    private function buildAuthenticator(?GlobDomainValidator $globDomainValidator = null): PublicationApiAuthenticator
    {
        return new PublicationApiAuthenticator(
            $globDomainValidator ?? Mockery::mock(GlobDomainValidator::class),
            self::VALID_WHITELIST,
            self::VALID_OIN,
        );
    }
}
