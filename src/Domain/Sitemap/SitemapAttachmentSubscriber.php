<?php

declare(strict_types=1);

namespace App\Domain\Sitemap;

use App\Domain\Publication\Attachment\Entity\AbstractAttachment;
use App\Domain\Publication\Attachment\Repository\AttachmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class SitemapAttachmentSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $doctrine,
        private AttachmentRepository $attachmentRepository,
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
        $attachmentQuery = $this->attachmentRepository->getAllPublishedQuery();

        /** @var AbstractAttachment $attachment */
        foreach ($attachmentQuery->toIterable() as $attachment) {
            $dossier = $attachment->getDossier();
            $event->getUrlContainer()->addUrl(
                new UrlConcrete(
                    $event->getUrlGenerator()->generate(
                        sprintf('app_%s_attachment_detail', $dossier->getType()->getValueForRouteName()),
                        [
                            'prefix' => $dossier->getDocumentPrefix(),
                            'dossierId' => $dossier->getDossierNr(),
                            'attachmentId' => $attachment->getId(),
                        ],
                        UrlGeneratorInterface::ABSOLUTE_URL,
                    ),
                    $attachment->getUpdatedAt(),
                    UrlConcrete::CHANGEFREQ_MONTHLY,
                    0.8
                ),
                'attachments',
            );
            $this->doctrine->detach($attachment);
        }
    }
}
