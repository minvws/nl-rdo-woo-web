<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Domain\OpenApi;

use DateTimeImmutable;
use Mockery;
use Monolog\Level;
use Monolog\LogRecord;
use PublicationApi\Domain\OpenApi\RequestLogProcessor;
use Shared\Service\Security\ApiUser;
use Shared\Service\Security\ApplicationMode\ApplicationMode;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Bundle\SecurityBundle\Security;

class RequestLogProcessorTest extends UnitTestCase
{
    public function testCommonNameInLogRecord(): void
    {
        $commonName = 'valid.minvws.nl';
        $user = new ApiUser($commonName);

        $security = Mockery::mock(Security::class);
        $security->expects('getUser')
            ->andReturn($user);

        $record = new LogRecord(
            new DateTimeImmutable(),
            'channel',
            Level::Info,
            'message',
        );

        $requestLogProcessor = new RequestLogProcessor($security, ApplicationMode::API);
        $record = $requestLogProcessor($record);

        self::assertArrayHasKey('commonName', $record->extra);
        self::assertEquals($commonName, $record->extra['commonName']);
    }

    public function testCommonNameNotInLogRecordIfNotApi(): void
    {
        $security = Mockery::mock(Security::class);

        $record = new LogRecord(
            new DateTimeImmutable(),
            'channel',
            Level::Info,
            'message',
        );

        $requestLogProcessor = new RequestLogProcessor($security, ApplicationMode::PUBLIC);
        $record = $requestLogProcessor($record);

        self::assertArrayNotHasKey('commonName', $record->extra);
    }

    public function testCommonNameNotInLogRecordIfNoApiUser(): void
    {
        $security = Mockery::mock(Security::class);
        $security->expects('getUser')
            ->andReturnNull();

        $record = new LogRecord(
            new DateTimeImmutable(),
            'channel',
            Level::Info,
            'message',
        );

        $requestLogProcessor = new RequestLogProcessor($security, ApplicationMode::API);
        $record = $requestLogProcessor($record);

        self::assertArrayNotHasKey('commonName', $record->extra);
    }
}
