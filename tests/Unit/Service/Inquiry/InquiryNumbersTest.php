<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Inquiry;

use Doctrine\Common\Collections\ArrayCollection;
use InvalidArgumentException;
use Mockery;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Service\Inquiry\InquiryNumbers;
use Shared\Tests\Unit\UnitTestCase;

use function iterator_to_array;

class InquiryNumbersTest extends UnitTestCase
{
    public function testConstructorThrowsExceptionForEmptyValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new InquiryNumbers(['foo', '']);
    }

    public function testConstructorThrowsExceptionForTooLongValue(): void
    {
        // 256 chars
        $this->expectException(InvalidArgumentException::class);
        new InquiryNumbers([
            'foo',
            'dddasdfadskfjhdasfkjhdasfkjhadskfjhadskjfhakjdsfkajsdhfkjadshfkjahsdflkjadflkdsajflkajdflkasjdlfkjadslkfja'
                . 'lskdjfadslkjfalksdlkfjalskdjflasdkjflkasjfldkjflaksjsdlkfdsalfkdajdsfalkjfdsalkdsjflkfdsafdsalkjdsfa'
                . 'lkjdsfalkdsfjalkdsafdlsfakdsfalkjdsfalkdfdfsdsfffd',
        ]);
    }

    public function testConstructorThrowsExceptionForValueWithInvalidChars(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new InquiryNumbers(['foo', 'bl*^%t']);
    }

    public function testConstructorSetValues(): void
    {
        $inquiryNumbers = new InquiryNumbers($input = ['foo', 'bar', 'foo-bar', 'foo.bar']);

        self::assertEquals($input, $inquiryNumbers->values);
    }

    public function testGetMissingValuesComparedToInput(): void
    {
        $inquiryNumbersA = new InquiryNumbers(['foo', 'bar', 'foo-bar']);
        $inquiryNumbersB = new InquiryNumbers(['foo', 'foo-bar']);

        self::assertEquals(['bar'], $inquiryNumbersB->getMissingValuesComparedToInput($inquiryNumbersA)->values);
    }

    public function testGetExtraValuesComparedToInput(): void
    {
        $inquiryNumbersA = new InquiryNumbers(['foo', 'bar', 'foo-bar']);
        $inquiryNumbersB = new InquiryNumbers(['foo', 'foo-bar', 'zzz']);

        self::assertEquals(['zzz'], $inquiryNumbersB->getExtraValuesComparedToInput($inquiryNumbersA)->values);
    }

    public function testValuesAreIterableAndCountable(): void
    {
        $inquiryNumbers = new InquiryNumbers($input = ['foo', 'bar', 'foo-bar']);

        self::assertEquals($input, iterator_to_array($inquiryNumbers));
        self::assertCount(3, $inquiryNumbers);
    }

    public function testIsNotEmpty(): void
    {
        $inquiryNumbers = new InquiryNumbers(['foo', 'bar', 'foo-bar']);
        self::assertTrue($inquiryNumbers->isNotEmpty());

        self::assertFalse(InquiryNumbers::empty()->isNotEmpty());
    }

    public function testForDocument(): void
    {
        $inquiryA = Mockery::mock(Inquiry::class);
        $inquiryA->expects('getInquiryNumber')->andReturn($inquiryNumberA = '123-foo');

        $inquiryB = Mockery::mock(Inquiry::class);
        $inquiryB->expects('getInquiryNumber')->andReturn($inquiryNumberB = '456-bar');

        $document = Mockery::mock(Document::class);
        $document->expects('getInquiries')->andReturn(new ArrayCollection([$inquiryA, $inquiryB]));

        self::assertEquals(
            [$inquiryNumberA, $inquiryNumberB],
            InquiryNumbers::forDocument($document)->values,
        );
    }

    public function testForWoodecision(): void
    {
        $inquiryA = Mockery::mock(Inquiry::class);
        $inquiryA->expects('getInquiryNumber')->andReturn($inquiryNumberA = '123-foo');

        $inquiryB = Mockery::mock(Inquiry::class);
        $inquiryB->expects('getInquiryNumber')->andReturn($inquiryNumberB = '456-bar');

        $wooDecision = Mockery::mock(WooDecision::class);
        $wooDecision->expects('getInquiries')->andReturn(new ArrayCollection([$inquiryA, $inquiryB]));

        self::assertEquals(
            [$inquiryNumberA, $inquiryNumberB],
            InquiryNumbers::forWooDecision($wooDecision)->values,
        );
    }

    public function testFromCommaSeparatedStringReturnsEmptySetForNullValue(): void
    {
        self::assertCount(0, InquiryNumbers::fromCommaSeparatedString(null));
    }

    public function testFromCommaSeparatedString(): void
    {
        self::assertEquals(
            ['foo', 'bar-123'],
            InquiryNumbers::fromCommaSeparatedString('foo,bar-123')->values,
        );
    }
}
