<?php

declare(strict_types=1);

namespace Admin\Api\Admin\NoticeNotPublic;

use Admin\Api\Admin\ApiDossierAccessChecker;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\NoticeNotPublicRepository;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

final readonly class NoticeNotPublicProvider implements ProviderInterface
{
    public function __construct(
        private ApiDossierAccessChecker $dossierAccessChecker,
        private NoticeNotPublicRepository $noticeNotPublicRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?NoticeNotPublicDto
    {
        $uriDossierId = $uriVariables['dossierId'] ?? '';
        Assert::string($uriDossierId);
        $dossierId = Uuid::fromString($uriDossierId);

        $this->dossierAccessChecker->ensureUserIsAllowedToUpdateDossier($dossierId);

        $notice = $this->noticeNotPublicRepository->findOneByDossierId($dossierId);

        return $notice ? NoticeNotPublicDto::fromEntity($notice) : null;
    }
}
