<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Domain\Security;

use PHPUnit\Framework\Attributes\DataProvider;
use PublicationApi\Domain\Security\GlobDomainValidator;
use Shared\Tests\Unit\UnitTestCase;

class GlobDomainValidatorTest extends UnitTestCase
{
    /**
     * @param array<array-key,string> $whitelist
     */
    #[DataProvider('domainDataProvider')]
    public function testIsValid(array $whitelist, string $domain, bool $expectedResult): void
    {
        $globDomainValidator = new GlobDomainValidator();
        $result = $globDomainValidator->isValid($whitelist, $domain);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return array<string, array{whitelist: array<array-key, string>, domain: string, expectedResult: bool}>
     */
    public static function domainDataProvider(): array
    {
        return [
            'subdomain' => [
                'whitelist' => ['*.minvws.nl'],
                'domain' => 'valid.minvws.nl',
                'expectedResult' => true,
            ],
            'subsubdomain' => [
                'whitelist' => ['*.minvws.nl'],
                'domain' => 'also.valid.minvws.nl',
                'expectedResult' => true,
            ],
            'subdomain when multiple configured' => [
                'whitelist' => ['*.minvws.nl', '*.irealisate.nl'],
                'domain' => 'valid.minvws.nl',
                'expectedResult' => true,
            ],
            'case-insensitive matching' => [
                'whitelist' => ['*.Minvws.nl'],
                'domain' => 'valid.MinVWS.nl',
                'expectedResult' => true,
            ],
            'unknown domain' => [
                'whitelist' => ['*.minvws.nl'],
                'domain' => 'invalid.example.com',
                'expectedResult' => false,
            ],
            'unknown subdomain' => [
                'whitelist' => ['*.minvws.nl', '*.valid.example.com'],
                'domain' => 'invalid.example.com',
                'expectedResult' => false,
            ],
        ];
    }
}
