<?php

declare(strict_types=1);

namespace Shared\Controller\ValueResolver;

use Doctrine\ORM\EntityManagerInterface;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Exception\ViewingNotAllowedException;
use Shared\Service\Security\DossierVoter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[AsTargetedValueResolver('dossierWithAccessCheck')]
readonly class DossierWithAccessCheckValueResolver implements ValueResolverInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    /**
     * @return iterable<AbstractDossier>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $dossier = $this->resolveDossier($request, $argument);
        if ($dossier === null) {
            throw ViewingNotAllowedException::forDossier();
        }

        if (! $this->authorizationChecker->isGranted(DossierVoter::VIEW, $dossier)) {
            throw ViewingNotAllowedException::forDossier();
        }

        return [$dossier];
    }

    private function resolveDossier(Request $request, ArgumentMetadata $argument): ?AbstractDossier
    {
        $argumentType = $argument->getType();
        if ($argumentType === null || ! is_a($argumentType, AbstractDossier::class, true)) {
            return null;
        }

        $prefix = $request->attributes->getString('prefix');
        $dossierId = $request->attributes->getString('dossierId');
        if ($prefix === '' || $dossierId === '') {
            return null;
        }

        /** @var ?AbstractDossier */
        return $this->entityManager->getRepository($argumentType)->findOneBy([
            'documentPrefix' => $prefix,
            'dossierNr' => $dossierId,
        ]);
    }
}
