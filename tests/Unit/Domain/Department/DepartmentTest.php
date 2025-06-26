<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Department;

use App\Domain\Publication\FileInfo;
use App\Entity\Department;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class DepartmentTest extends MockeryTestCase
{
    public function testSetAndGetLandingPageTitle(): void
    {
        $department = new Department();
        $department->setLandingPageTitle($title = 'foo');

        self::assertEquals($title, $department->getLandingPageTitle());
    }

    public function testSetAndGetLandingPageDescription(): void
    {
        $department = new Department();
        $department->setLandingPageDescription($description = 'foo');

        self::assertEquals($description, $department->getLandingPageDescription());
    }

    public function testSetAndGetFeedbackContent(): void
    {
        $department = new Department();
        $department->setFeedbackContent($content = 'foo');

        self::assertEquals($content, $department->getFeedbackContent());
    }

    public function testSetAndGetFileInfo(): void
    {
        $department = new Department();

        $fileInfo = new FileInfo();
        $department->setFileInfo($fileInfo);

        self::assertSame($fileInfo, $department->getFileInfo());
    }
}
