<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Domain\Security;

use MinVWS\AuditLogger\AuditLoggerInterface;
use MinVWS\AuditLogger\Events\Logging\UserLoginLogEvent;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PublicationApi\Domain\OpenApi\ProblemDetailsFactory;
use PublicationApi\Domain\Security\AuditLog\LoginFailedAuditLogEvent;
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
        $auditLogger = Mockery::mock(AuditLoggerInterface::class);
        $auditLogger->expects('log')
            ->with(Mockery::on(static function (LoginFailedAuditLogEvent $event): bool {
                return $event->failed === true && $event->failedReason === 'publication_api_invalid_certificate';
            }));

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessageIs('Client Certificate is not verified or invalid');

        $this->buildAuthenticator($auditLogger)
            ->authenticate(new Request(server: $serverParams));
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
        $auditLogger = Mockery::mock(AuditLoggerInterface::class);
        $auditLogger->expects('log')
            ->with(Mockery::on(static function (LoginFailedAuditLogEvent $event): bool {
                return $event->failed === true && $event->failedReason === 'publication_api_invalid_organization_identifier';
            }));

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessageIs('Client Certificate Organization Identifier is missing or invalid');

        $this->buildAuthenticator($auditLogger)
            ->authenticate(new Request(server: $serverParams));
    }

    public function testAuthenticateThrowsWhenSslUserNameIsMissing(): void
    {
        $auditLogger = Mockery::mock(AuditLoggerInterface::class);
        $auditLogger->expects('log')
            ->with(Mockery::on(static function (LoginFailedAuditLogEvent $event): bool {
                return $event->failed === true && $event->failedReason === 'publication_api_invalid_common_name';
            }));

        $request = new Request(server: [
            self::SSL_VERIFY => 'SUCCESS',
            self::SSL_DN => self::VALID_DN,
            // SSL_CLIENT_S_DN_CN intentionally absent
        ]);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessageIs('Client Certificate Common Name is missing or invalid');

        $this->buildAuthenticator($auditLogger)
            ->authenticate($request);
    }

    public function testAuthenticateThrowsWhenCommonNameIsNotWhitelisted(): void
    {
        $sslUserName = 'attacker.example.com';

        $auditLogger = Mockery::mock(AuditLoggerInterface::class);
        $auditLogger->expects('log')
            ->with(Mockery::on(static function (LoginFailedAuditLogEvent $event): bool {
                return $event->failed === true && $event->failedReason === 'publication_api_common_name_not_whitelisted';
            }));

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
        $this->expectExceptionMessageIs(
            'Client Certificate Common Name is not whitelisted. Please read the documentation or contact your system administrator.',
        );

        $this->buildAuthenticator($auditLogger, $globDomainValidator)
            ->authenticate($request);
    }

    public function testAuthenticateReturnsPassportWithCorrectUserOnValidRequest(): void
    {
        $auditLogger = Mockery::mock(AuditLoggerInterface::class);
        $auditLogger->expects('log')
            ->with(Mockery::on(static function (UserLoginLogEvent $event): bool {
                return $event->data === ['common_name' => self::VALID_USERNAME];
            }));

        $globDomainValidator = Mockery::mock(GlobDomainValidator::class);
        $globDomainValidator->expects('isValid')
            ->with([self::VALID_WHITELIST], self::VALID_USERNAME)
            ->andReturn(true);

        $passport = $this->buildAuthenticator($auditLogger, $globDomainValidator)
            ->authenticate(new Request(server: [
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
        $auditLogger = Mockery::mock(AuditLoggerInterface::class);
        $auditLogger->expects('log')
            ->with(Mockery::on(static function (UserLoginLogEvent $event): bool {
                return $event->data === ['common_name' => self::VALID_USERNAME];
            }));

        $globDomainValidator = Mockery::mock(GlobDomainValidator::class);
        $globDomainValidator->expects('isValid')->andReturn(true);

        $passport = $this->buildAuthenticator($auditLogger, $globDomainValidator)
            ->authenticate(new Request(server: [
                self::SSL_CN => self::VALID_USERNAME,
                self::SSL_VERIFY => 'SUCCESS',
                self::SSL_DN => $dn,
            ]));

        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);
    }

    private function buildAuthenticator(
        ?AuditLoggerInterface $auditLogger = null,
        ?GlobDomainValidator $globDomainValidator = null,
    ): PublicationApiAuthenticator {
        return new PublicationApiAuthenticator(
            $auditLogger ?? Mockery::mock(AuditLoggerInterface::class),
            $globDomainValidator ?? Mockery::mock(GlobDomainValidator::class),
            new ProblemDetailsFactory(),
            self::VALID_WHITELIST,
            self::VALID_OIN,
        );
    }
}
