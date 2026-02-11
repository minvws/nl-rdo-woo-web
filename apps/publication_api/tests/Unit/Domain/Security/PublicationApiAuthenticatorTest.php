<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Domain\Security;

use Mockery;
use PublicationApi\Domain\Security\ApiUser;
use PublicationApi\Domain\Security\GlobDomainValidator;
use PublicationApi\Domain\Security\PublicationApiAuthenticator;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class PublicationApiAuthenticatorTest extends UnitTestCase
{
    public function testSupportsWithSslUserName(): void
    {
        $request = new Request(server: [
            'SSL_CLIENT_S_DN_CN' => 'string',
        ]);
        $whitelist = '*.minvws.nl';

        $globDomainValidator = Mockery::mock(GlobDomainValidator::class);

        $publicationApiAuthenticator = new PublicationApiAuthenticator($globDomainValidator, $whitelist);
        $this->assertTrue($publicationApiAuthenticator->supports($request));
    }

    public function testSupportsWithoutSslUserName(): void
    {
        $request = new Request();
        $whitelist = '*.minvws.nl';

        $globDomainValidator = Mockery::mock(GlobDomainValidator::class);

        $publicationApiAuthenticator = new PublicationApiAuthenticator($globDomainValidator, $whitelist);
        $this->assertFalse($publicationApiAuthenticator->supports($request));
    }

    public function testAuthenticateWithValidSslUserName(): void
    {
        $sslUserName = 'valid.minvws.nl';
        $request = new Request(server: [
            'SSL_CLIENT_S_DN_CN' => $sslUserName,
        ]);
        $whitelist = '*.minvws.nl';

        $globDomainValidator = Mockery::mock(GlobDomainValidator::class);
        $globDomainValidator->expects('isValid')
            ->with([$whitelist], $sslUserName)
            ->andReturn(true);

        $publicationApiAuthenticator = new PublicationApiAuthenticator($globDomainValidator, $whitelist);
        $passport = $publicationApiAuthenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);
        $this->assertInstanceOf(ApiUser::class, $passport->getUser());
        $this->assertEquals($sslUserName, $passport->getUser()->getUserIdentifier());
    }

    public function testAuthenticateWithInvalidSslUserName(): void
    {
        $sslUserName = 'valid.minvws.nl';
        $request = new Request(server: [
            'SSL_CLIENT_S_DN_CN' => $sslUserName,
        ]);
        $whitelist = '*.minvws.nl';

        $globDomainValidator = Mockery::mock(GlobDomainValidator::class);
        $globDomainValidator->expects('isValid')
            ->with([$whitelist], $sslUserName)
            ->andReturn(false);

        $publicationApiAuthenticator = new PublicationApiAuthenticator($globDomainValidator, $whitelist);

        $this->expectException(AuthenticationException::class);
        $publicationApiAuthenticator->authenticate($request);
    }
}
