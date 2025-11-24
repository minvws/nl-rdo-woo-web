<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Twig\Components\Admin;

use Shared\Tests\Unit\UnitTestCase;
use Shared\Twig\Components\Admin\Alert;

final class AlertTest extends UnitTestCase
{
    private Alert $alert;

    protected function setUp(): void
    {
        $this->alert = new Alert();

        parent::setUp();
    }

    public function testGetIconName(): void
    {
        $this->alert->mount('danger');

        self::assertEquals(
            'alert-circle',
            $this->alert->getIconName(),
        );

        $this->alert->mount('info');

        self::assertEquals(
            'info-circle',
            $this->alert->getIconName(),
        );

        $this->alert->mount('warning');

        self::assertEquals(
            'alert-triangle',
            $this->alert->getIconName(),
        );

        $this->alert->mount('');

        self::assertEquals(
            'circle-check',
            $this->alert->getIconName(),
        );
    }

    public function testGetIconColor(): void
    {
        $this->alert->mount('danger');

        self::assertEquals(
            'stroke-bhr-red-700',
            $this->alert->getIconColor(),
        );

        $this->alert->mount('info');

        self::assertEquals(
            'stroke-bhr-blue-700',
            $this->alert->getIconColor(),
        );

        $this->alert->mount('warning');

        self::assertEquals(
            'stroke-bhr-yellow-800',
            $this->alert->getIconColor(),
        );

        $this->alert->mount('');

        self::assertEquals(
            'stroke-bhr-green-700',
            $this->alert->getIconColor(),
        );
    }

    public function testGetAlertType(): void
    {
        $this->alert->mount('danger');

        self::assertEquals(
            'bhr-alert--danger',
            $this->alert->getAlertType(),
        );

        $this->alert->mount('info');

        self::assertEquals(
            'bhr-alert--info',
            $this->alert->getAlertType(),
        );

        $this->alert->mount('warning');

        self::assertEquals(
            'bhr-alert--warning',
            $this->alert->getAlertType(),
        );

        $this->alert->mount('');

        self::assertEquals(
            'bhr-alert--success',
            $this->alert->getAlertType(),
        );
    }
}
