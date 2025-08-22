<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig\Components\Admin;

use App\Twig\Components\Admin\Alert;
use Mockery\Adapter\Phpunit\MockeryTestCase;

final class AlertTest extends MockeryTestCase
{
    private Alert $alert;

    public function setUp(): void
    {
        $this->alert = new Alert();

        parent::setUp();
    }

    public function testGetIconName(): void
    {
        $this->alert->mount('danger');

        self::assertEquals(
            'exclamation-filled-colored',
            $this->alert->getIconName(),
        );

        $this->alert->mount('info');

        self::assertEquals(
            'info-rounded-filled',
            $this->alert->getIconName(),
        );

        $this->alert->mount('');

        self::assertEquals(
            'check-rounded-filled',
            $this->alert->getIconName(),
        );
    }

    public function testGetIconColor(): void
    {
        $this->alert->mount('danger');

        self::assertEquals(
            'fill-current',
            $this->alert->getIconColor(),
        );

        $this->alert->mount('info');

        self::assertEquals(
            'fill-bhr-blue-800',
            $this->alert->getIconColor(),
        );

        $this->alert->mount('');

        self::assertEquals(
            'fill-bhr-philippine-green',
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

        $this->alert->mount('');

        self::assertEquals(
            'bhr-alert--success',
            $this->alert->getAlertType(),
        );
    }
}
