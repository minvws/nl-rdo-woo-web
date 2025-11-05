<?php

declare(strict_types=1);

namespace App\Tests\Unit\Api\Publication\V1;

use App\Api\Publication\V1\RequestLogProcessor;
use App\Service\Security\Api\ApiUser;
use App\Service\Security\ApplicationMode\ApplicationMode;
use App\Tests\Unit\UnitTestCase;
use Monolog\Level;
use Monolog\LogRecord;
use Symfony\Bundle\SecurityBundle\Security;

class RequestLogProcessorTest extends UnitTestCase
{
    public function testCommonNameInLogRecord(): void
    {
        $commonName = 'valid.minvws.nl';
        $user = new ApiUser($commonName);

        $security = \Mockery::mock(Security::class);
        $security->expects('getUser')
            ->once()
            ->andReturn($user);

        $record = new LogRecord(
            new \DateTimeImmutable(),
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
        $security = \Mockery::mock(Security::class);

        $record = new LogRecord(
            new \DateTimeImmutable(),
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
        $security = \Mockery::mock(Security::class);
        $security->expects('getUser')
            ->once()
            ->andReturnNull();

        $record = new LogRecord(
            new \DateTimeImmutable(),
            'channel',
            Level::Info,
            'message',
        );

        $requestLogProcessor = new RequestLogProcessor($security, ApplicationMode::API);
        $record = $requestLogProcessor($record);

        self::assertArrayNotHasKey('commonName', $record->extra);
    }
}
