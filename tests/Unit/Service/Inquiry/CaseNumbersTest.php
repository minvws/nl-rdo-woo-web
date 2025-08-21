<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Inquiry;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Service\Inquiry\CaseNumbers;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class CaseNumbersTest extends MockeryTestCase
{
    public function testConstructorThrowsExceptionForEmptyValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CaseNumbers(['foo', '']);
    }

    public function testConstructorThrowsExceptionForTooLongValue(): void
    {
        // 256 chars
        $this->expectException(\InvalidArgumentException::class);
        new CaseNumbers([
            'foo',
            'dddasdfadskfjhdasfkjhdasfkjhadskfjhadskjfhakjdsfkajsdhfkjadshfkjahsdflkjadflkdsajflkajdflkasjdlfkjadslkfja'
                . 'lskdjfadslkjfalksdlkfjalskdjflasdkjflkasjfldkjflaksjsdlkfdsalfkdajdsfalkjfdsalkdsjflkfdsafdsalkjdsfa'
                . 'lkjdsfalkdsfjalkdsafdlsfakdsfalkjdsfalkdfdfsdsfffd',
        ]);
    }

    public function testConstructorThrowsExceptionForValueWithInvalidChars(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CaseNumbers(['foo', 'bl*^%t']);
    }

    public function testConstructorSetValues(): void
    {
        $caseNumbers = new CaseNumbers($input = ['foo', 'bar', 'foo-bar']);

        self::assertEquals($input, $caseNumbers->values);
    }

    public function testGetMissingValuesComparedToInput(): void
    {
        $caseNumbersA = new CaseNumbers(['foo', 'bar', 'foo-bar']);
        $caseNumbersB = new CaseNumbers(['foo', 'foo-bar']);

        self::assertEquals(['bar'], $caseNumbersB->getMissingValuesComparedToInput($caseNumbersA)->values);
    }

    public function testGetExtraValuesComparedToInput(): void
    {
        $caseNumbersA = new CaseNumbers(['foo', 'bar', 'foo-bar']);
        $caseNumbersB = new CaseNumbers(['foo', 'foo-bar', 'zzz']);

        self::assertEquals(['zzz'], $caseNumbersB->getExtraValuesComparedToInput($caseNumbersA)->values);
    }

    public function testValuesAreIterableAndCountable(): void
    {
        $caseNumbers = new CaseNumbers($input = ['foo', 'bar', 'foo-bar']);

        self::assertEquals($input, iterator_to_array($caseNumbers));
        self::assertCount(3, $caseNumbers);
    }

    public function testIsNotEmpty(): void
    {
        $caseNumbers = new CaseNumbers(['foo', 'bar', 'foo-bar']);
        self::assertTrue($caseNumbers->isNotEmpty());

        self::assertFalse(CaseNumbers::empty()->isNotEmpty());
    }

    public function testForDocument(): void
    {
        $inquiryA = \Mockery::mock(Inquiry::class);
        $inquiryA->expects('getCasenr')->andReturn($caseNrA = '123-foo');

        $inquiryB = \Mockery::mock(Inquiry::class);
        $inquiryB->expects('getCasenr')->andReturn($caseNrB = '456-bar');

        $document = \Mockery::mock(Document::class);
        $document->expects('getInquiries')->andReturn(new ArrayCollection([$inquiryA, $inquiryB]));

        self::assertEquals(
            [$caseNrA, $caseNrB],
            CaseNumbers::forDocument($document)->values,
        );
    }

    public function testForWoodecision(): void
    {
        $inquiryA = \Mockery::mock(Inquiry::class);
        $inquiryA->expects('getCasenr')->andReturn($caseNrA = '123-foo');

        $inquiryB = \Mockery::mock(Inquiry::class);
        $inquiryB->expects('getCasenr')->andReturn($caseNrB = '456-bar');

        $wooDecision = \Mockery::mock(WooDecision::class);
        $wooDecision->expects('getInquiries')->andReturn(new ArrayCollection([$inquiryA, $inquiryB]));

        self::assertEquals(
            [$caseNrA, $caseNrB],
            CaseNumbers::forWooDecision($wooDecision)->values,
        );
    }

    public function testFromCommaSeparatedStringReturnsEmptySetForNullValue(): void
    {
        self::assertCount(0, CaseNumbers::fromCommaSeparatedString(null));
    }

    public function testFromCommaSeparatedString(): void
    {
        self::assertEquals(
            ['foo', 'bar-123'],
            CaseNumbers::fromCommaSeparatedString('foo,bar-123')->values,
        );
    }
}
