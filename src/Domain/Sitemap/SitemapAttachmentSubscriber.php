<?php

declare(strict_types=1);

namespace Shared\Domain\Sitemap;

use Doctrine\ORM\EntityManagerInterface;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Repository\AttachmentRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use function sprintf;

#[AsEventListener]
readonly class SitemapAttachmentSubscriber
{
    public function __construct(
        private EntityManagerInterface $doctrine,
        private AttachmentRepository $attachmentRepository,
    ) {
    }

    public function __invoke(SitemapPopulateEvent $event): void
    {
        $attachmentQuery = $this->attachmentRepository->getAllPublishedQuery();

        /** @var AbstractAttachment $attachment */
        foreach ($attachmentQuery->toIterable() as $attachment) {
            $dossier = $attachment->getDossier();
            $event->getUrlContainer()->addUrl(
                new UrlConcrete(
                    $event->getUrlGenerator()->generate(
                        sprintf('app_%s_attachment_detail', $dossier->getType()->getValueForRouteName()),
                        [
                            'documentPrefix' => $dossier->getDocumentPrefix(),
                            'dossierNumber' => $dossier->getDossierNumber(),
                            'attachmentId' => $attachment->getId(),
                        ],
                        UrlGeneratorInterface::ABSOLUTE_URL,
                    ),
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
