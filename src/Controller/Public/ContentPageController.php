<?php

declare(strict_types=1);

namespace Shared\Controller\Public;

use Shared\Domain\Content\Page\ContentPage;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;

class ContentPageController extends AbstractController
{
    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    public function page(
        #[MapEntity(mapping: ['slug' => 'slug'])] ContentPage $contentPage,
    ): Response {
        return $this->render('public/content-page/content-page.html.twig', [
            'page' => $contentPage,
        ]);
    }
}
