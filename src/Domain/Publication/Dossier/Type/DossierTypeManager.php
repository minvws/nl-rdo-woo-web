<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

use function array_key_exists;

class DossierTypeManager
{
    /**
     * @var DossierTypeConfigInterface[]
     */
    private array $configs;

    /**
     * @param DossierTypeConfigInterface[] $configs
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        #[AutowireIterator('woo_platform.publication.dossier_type_config')]
        iterable $configs,
    ) {
        $this->configs = [];
        foreach ($configs as $config) {
            $this->configs[$config->getDossierType()->value] = $config;
        }
    }

    public function getConfig(DossierType $type): DossierTypeConfigInterface
    {
        if (! array_key_exists($type->value, $this->configs)) {
            throw DossierTypeException::forDossierTypeNotAvailable($type);
        }

        return $this->configs[$type->value];
    }

    public function getConfigWithAccessCheck(DossierType $type): DossierTypeConfigInterface
    {
        $config = $this->getConfig($type);
        if (! $this->isAccessible($config)) {
            throw DossierTypeException::forAccessDeniedToType($type);
        }

        return $config;
    }

    /**
     * @return DossierTypeConfigInterface[]
     */
    public function getAvailableConfigs(): array
    {
        $availableConfigs = [];
        foreach ($this->configs as $config) {
            if ($this->isAccessible($config)) {
                $availableConfigs[] = $config;
            }
        }

        return $availableConfigs;
    }

    public function createDossier(DossierType $type): AbstractDossier
    {
        return new ($this->getConfig($type)->getEntityClass());
    }

    public function getStatusWorkflow(AbstractDossier $dossier): WorkflowInterface
    {
        return $this->getConfig($dossier->getType())->getStatusWorkflow();
    }

    private function isAccessible(DossierTypeConfigInterface $config): bool
    {
        $expression = $config->getSecurityExpression();

        return $expression === null || $this->authorizationChecker->isGranted($expression);
    }

    /**
     * @return DossierTypeConfigInterface[]
     */
    public function getAllConfigs(): array
    {
        return $this->configs;
    }
}
