<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Search\Query\Facet\Input;

use App\Service\Search\Model\FacetKey;
use App\Service\Search\Query\Facet\Input\DateFacetInput;
use App\Service\Search\Query\Facet\Input\DateFacetInputInterface;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\ParameterBag;

#[Group('facet')]
#[Group('facetInput')]
final class DateFacetInputTest extends UnitTestCase
{
    protected FacetKey $key = FacetKey::DATE;
    protected ParameterBag&MockInterface $bag;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bag = \Mockery::mock(ParameterBag::class);
        $this->bag->shouldReceive('all')->with($this->key->getParamName())->once()->andReturn([])->byDefault();
    }

    public function testItCanBeInitialized(): void
    {
        $input = DateFacetInput::fromParameterBag($this->key, $this->bag);

        self::assertInstanceOf(DateFacetInput::class, $input);
        self::assertInstanceOf(DateFacetInputInterface::class, $input);
    }

    public function testIsActiveReturnsTrueWhenItHasValues(): void
    {
        $this->bag->shouldReceive('all')->with($this->key->getParamName())->once()->andReturn(['without_date' => '1']);

        $input = DateFacetInput::fromParameterBag($this->key, $this->bag);

        self::assertTrue($input->isActive());
    }

    public function testIsActiveReturnsFalseWhenItHasNoValues(): void
    {
        $input = DateFacetInput::fromParameterBag($this->key, $this->bag);

        self::assertFalse($input->isActive());
    }

    public function testIsNotActiveReturnsFalseWhenItHasValues(): void
    {
        $this->bag->shouldReceive('all')->with($this->key->getParamName())->once()->andReturn(['without_date' => '1']);

        $input = DateFacetInput::fromParameterBag($this->key, $this->bag);

        self::assertFalse($input->isNotActive());
    }

    public function testWithoutDateCanBeUsedInCombinationWithFromAndToDate(): void
    {
        $this->bag->shouldReceive('all')->with($this->key->getParamName())->once()->andReturn([
            'without_date' => '1',
            'from' => '2021-01-01',
            'to' => '2024-01-01',
        ]);

        $input = DateFacetInput::fromParameterBag($this->key, $this->bag);

        self::assertFalse($input->isNotActive());
        self::assertTrue($input->isWithoutDate());
        self::assertTrue($input->hasFromDate());
        self::assertTrue($input->hasToDate());
        self::assertEquals('2021-01-01', $input->getPeriodFilterFrom());
        self::assertEquals('2024-01-01', $input->getPeriodFilterTo());
    }

    public function testIsNotActiveReturnsTrueWhenItHasNoValues(): void
    {
        $input = DateFacetInput::fromParameterBag($this->key, $this->bag);

        self::assertTrue($input->isNotActive());
    }

    public function testGetWithoutDateWithEmptyBagReturnsFalse(): void
    {
        $input = DateFacetInput::fromParameterBag($this->key, $this->bag);

        self::assertFalse($input->isWithoutDate());
    }

    #[DataProvider('getWithoutDateData')]
    public function testGetWithoutDate(string $input, bool $expected): void
    {
        $this->bag->shouldReceive('all')
            ->with($this->key->getParamName())
            ->once()
            ->andReturn(['without_date' => $input]);

        $input = DateFacetInput::fromParameterBag($this->key, $this->bag);

        self::assertSame($expected, $input->isWithoutDate());
    }

    /**
     * @return array<string,array{input:string,expected:bool}>
     */
    public static function getWithoutDateData(): array
    {
        return [
            'empty string' => [
                'input' => '',
                'expected' => false,
            ],
            'number zero' => [
                'input' => '0',
                'expected' => true,
            ],
            'number one' => [
                'input' => '1',
                'expected' => true,
            ],
            'true' => [
                'input' => 'true',
                'expected' => true,
            ],
            'false' => [
                'input' => 'false',
                'expected' => true,
            ],
            'foobar' => [
                'input' => 'foobar',
                'expected' => true,
            ],
            '<whitespace>' => [
                'input' => ' ',
                'expected' => false,
            ],
        ];
    }

    public function testGetPeriodFilterFromWithEmptyBagReturnsNull(): void
    {
        $input = DateFacetInput::fromParameterBag($this->key, $this->bag);

        self::assertNull($input->getPeriodFilterFrom());
    }

    #[DataProvider('datesData')]
    public function testGetPeriodFilterFrom(string $input, ?string $expected): void
    {
        $this->bag->shouldReceive('all')
            ->with($this->key->getParamName())
            ->once()
            ->andReturn(['from' => $input]);

        $input = DateFacetInput::fromParameterBag($this->key, $this->bag);

        self::assertSame($expected, $input->getPeriodFilterFrom());
        self::assertNull($input->getPeriodFilterTo());
    }

    #[DataProvider('datesData')]
    public function testGetPeriodFilterTo(string $input, ?string $expected): void
    {
        $this->bag->shouldReceive('all')
            ->with($this->key->getParamName())
            ->once()
            ->andReturn(['to' => $input]);

        $input = DateFacetInput::fromParameterBag($this->key, $this->bag);

        self::assertSame($expected, $input->getPeriodFilterTo());
        self::assertNull($input->getPeriodFilterFrom());
    }

    /**
     * @return array<string,array{input:string,expected:?string}>
     */
    public static function datesData(): array
    {
        return [
            'valid date' => [
                'input' => '2021-01-01',
                'expected' => '2021-01-01',
            ],
            'invalid formatted date' => [
                'input' => '01-01-2021',
                'expected' => null,
            ],
            'empty string' => [
                'input' => '',
                'expected' => null,
            ],
            '<whitespace>' => [
                'input' => ' ',
                'expected' => null,
            ],
            'random string #1' => [
                'input' => 'foobar',
                'expected' => null,
            ],
            'random string #2' => [
                'input' => 'est', // = Eastern Standard Time
                'expected' => null,
            ],
        ];
    }

    /**
     * @param array<string,string> $bagInput
     */
    #[DataProvider('hasAnyPeriodFilterDatesData')]
    public function testHasAnyPeriodFilterDates(array $bagInput, bool $expected): void
    {
        $this->bag->shouldReceive('all')
            ->with($this->key->getParamName())
            ->once()
            ->andReturn($bagInput);

        $input = DateFacetInput::fromParameterBag($this->key, $this->bag);

        self::assertSame($expected, $input->hasAnyPeriodFilterDates());
    }

    /**
     * @return array<string,array{bagInput:array<string,string>,expected:bool}>
     */
    public static function hasAnyPeriodFilterDatesData(): array
    {
        return [
            'empty bag' => [
                'bagInput' => [],
                'expected' => false,
            ],
            'from date only' => [
                'bagInput' => [
                    'from' => '2021-01-01',
                ],
                'expected' => true,
            ],
            'to date only' => [
                'bagInput' => [
                    'to' => '2021-01-01',
                ],
                'expected' => true,
            ],
            'from and to dates' => [
                'bagInput' => [
                    'from' => '2021-01-01',
                    'to' => '2021-01-01',
                ],
                'expected' => true,
            ],
            'empty string as date input' => [
                'bagInput' => [
                    'from' => '',
                    'to' => '',
                ],
                'expected' => false,
            ],
            '<whitespace> as date input' => [
                'bagInput' => [
                    'from' => ' ',
                    'to' => ' ',
                ],
                'expected' => false,
            ],
            'random string #1 as date input' => [
                'bagInput' => [
                    'from' => 'foobar',
                    'to' => 'foobar',
                ],
                'expected' => false,
            ],
            'random string #2 as date input' => [
                'bagInput' => [
                    'from' => 'est', // = Eastern Standard Time
                    'to' => 'est',
                ],
                'expected' => false,
            ],
        ];
    }

    public function testGetRequestParameters(): void
    {
        $this->bag->shouldReceive('all')->with($this->key->getParamName())->once()->andReturn(['without_date' => '1']);

        $input = DateFacetInput::fromParameterBag($this->key, $this->bag);

        self::assertEquals(
            ['without_date' => 1],
            $input->getRequestParameters(),
        );

        $this->bag->shouldReceive('all')->with($this->key->getParamName())->once()->andReturn([
            'from' => '2022-03-12',
            'to' => '2024-12-01',
        ]);

        $input = DateFacetInput::fromParameterBag($this->key, $this->bag);

        self::assertEquals(
            ['from' => '2022-03-12', 'to' => '2024-12-01'],
            $input->getRequestParameters(),
        );
    }
}
