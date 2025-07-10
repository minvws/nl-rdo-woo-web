<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Sitemap;

use App\Domain\Department\Department;
use App\Domain\Department\DepartmentRepository;
use App\Domain\Sitemap\SitemapDepartmentSubscriber;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Service\UrlContainerInterface;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SitemapDepartmentSubscriberTest extends UnitTestCase
{
    private DepartmentRepository&MockInterface $departmentRepository;
    private SitemapDepartmentSubscriber $subscriber;

    public function setUp(): void
    {
        $this->departmentRepository = \Mockery::mock(DepartmentRepository::class);

        $this->subscriber = new SitemapDepartmentSubscriber(
            $this->departmentRepository,
        );
    }

    public function testPopulate(): void
    {
        $department = \Mockery::mock(Department::class);
        $department->shouldReceive('getSlug')->andReturn($slug = 'foobar');

        $urlContainer = \Mockery::mock(UrlContainerInterface::class);

        $this->departmentRepository
            ->expects('getAllPublicDepartments')
            ->once()
            ->andReturn([$department]);

        $urlGenerator = \Mockery::mock(UrlGeneratorInterface::class);
        $urlGenerator->expects('generate')->with(
            'app_departments_index',
            [],
            0,
        )->andReturn($departmentOverviewUrl = '/departments');

        $urlContainer->expects('addUrl')->with(
            \Mockery::on(
                static function (UrlConcrete $urlConcrete) use ($departmentOverviewUrl): bool {
                    self::assertEquals($departmentOverviewUrl, $urlConcrete->getLoc());

                    return true;
                }
            ),
            'departments',
        );

        $urlGenerator->expects('generate')->with(
            'app_department_detail',
            [
                'slug' => $slug,
            ],
            0,
        )->andReturn($departmentDetailUrl = '/departments/foobar');

        $urlContainer->expects('addUrl')->with(
            \Mockery::on(
                static function (UrlConcrete $urlConcrete) use ($departmentDetailUrl): bool {
                    self::assertEquals($departmentDetailUrl, $urlConcrete->getLoc());

                    return true;
                }
            ),
            'departments',
        );

        $event = new SitemapPopulateEvent(
            $urlContainer,
            $urlGenerator,
        );

        $this->subscriber->populate($event);
    }
}
