<?php

declare(strict_types=1);

namespace App\Domain\Sitemap;

use App\Domain\Department\DepartmentRepository;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class SitemapDepartmentSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private DepartmentRepository $departmentRepository,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SitemapPopulateEvent::class => ['populate', 0],
        ];
    }

    public function populate(SitemapPopulateEvent $event): void
    {
        $event->getUrlContainer()->addUrl(
            new UrlConcrete(
                $event->getUrlGenerator()->generate(
                    'app_departments_index',
                    [],
                    UrlGeneratorInterface::ABSOLUTE_URL,
                ),
                null,
                UrlConcrete::CHANGEFREQ_MONTHLY,
                0.8
            ),
            'departments',
        );

        foreach ($this->departmentRepository->getAllPublicDepartments() as $department) {
            $event->getUrlContainer()->addUrl(
                new UrlConcrete(
                    $event->getUrlGenerator()->generate(
                        'app_department_detail',
                        [
                            'slug' => $department->getSlug(),
                        ],
                        UrlGeneratorInterface::ABSOLUTE_URL,
                    ),
                    null,
                    UrlConcrete::CHANGEFREQ_MONTHLY,
                    0.8
                ),
                'departments',
            );
        }
    }
}
