<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Form;

use PHPUnit\Framework\TestCase;
use Shared\Form\PlainDateType;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Forms;

final class PlainDateTypeTest extends TestCase
{
    public function testGetParent(): void
    {
        self::assertSame(DateType::class, new PlainDateType()->getParent());
    }

    public function testSubmitValidDate(): void
    {
        $form = Forms::createFormFactory()->create(PlainDateType::class);
        $form->submit('2024-06-15');

        self::assertTrue($form->isSynchronized());
        self::assertEquals(PlainDate::create('2024-06-15'), $form->getData());
    }

    public function testSubmitEmptyString(): void
    {
        $form = Forms::createFormFactory()->create(PlainDateType::class);
        $form->submit('');

        self::assertTrue($form->isSynchronized());
        self::assertNull($form->getData());
    }

    public function testModelDataTransformsToViewData(): void
    {
        $form = Forms::createFormFactory()->create(PlainDateType::class);
        $form->setData(PlainDate::create('2024-06-15'));

        self::assertSame('2024-06-15', $form->getViewData());
    }
}
