<?php

declare(strict_types=1);

namespace Shared\Domain\Sitemap;

use Doctrine\ORM\EntityManagerInterface;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Repository\AttachmentRepository;
use Shared\Domain\Publication\Dossier\DossierUrlGenerator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Webmozart\Assert\Assert;

#[AsEventListener]
readonly class SitemapAttachmentSubscriber
{
    public function __construct(
        private EntityManagerInterface $doctrine,
        private AttachmentRepository $attachmentRepository,
        private DossierUrlGenerator $dossierUrlGenerator,
    ) {
    }

    public function __invoke(SitemapPopulateEvent $event): void
    {
        $attachmentQuery = $this->attachmentRepository->getAllPublishedQuery();

        foreach ($attachmentQuery->toIterable() as $attachment) {
            Assert::isInstanceOf($attachment, AbstractAttachment::class);

            $dossier = $attachment->getDossier();
            $event->getUrlContainer()->addUrl(
                new UrlConcrete(
                    $this->dossierUrlGenerator->attachmentDetailUrl($dossier, $attachment->getId())->toString(),
                    $attachment->getUpdatedAt(),
                    UrlConcrete::CHANGEFREQ_MONTHLY,
                    0.8,
                ),
                'attachments',
            );
            $this->doctrine->detach($attachment);
        }
    }
}
