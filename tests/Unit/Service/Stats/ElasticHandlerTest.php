<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Stats;

use DateTimeImmutable;
use DateTimeInterface;
use Mockery;
use Shared\Service\Elastic\ElasticClientInterface;
use Shared\Service\Stats\Handler\ElasticHandler;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

use function array_key_exists;

final class ElasticHandlerTest extends UnitTestCase
{
    public function testStore(): void
    {
        $dateTime = new DateTimeImmutable();
        $hostname = $this->getFaker()->word();
        $section = $this->getFaker()->word();
        $duration = $this->getFaker()->randomDigit();

        $expectedBody = [
            'created_at' => $dateTime->format(DateTimeInterface::ATOM),
            'hostname' => $hostname,
            'section' => $section,
            'duration' => $duration,
        ];

        $elasticClient = Mockery::mock(ElasticClientInterface::class);
        $elasticClient->expects('create')
            ->with(Mockery::on(static function (array $params) use ($expectedBody): bool {
                if (! array_key_exists('index', $params) || $params['index'] !== 'worker_stats') {
                    return false;
                }

                if (! array_key_exists('id', $params) || ! $params['id'] instanceof Uuid) {
                    return false;
                }

                if (! array_key_exists('body', $params) || $params['body'] !== $expectedBody) {
                    return false;
                }

                return true;
            }));

        $doctrineHandler = new ElasticHandler($elasticClient);
        $doctrineHandler->store($dateTime, $hostname, $section, $duration);
    }
}
