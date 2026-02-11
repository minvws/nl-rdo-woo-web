<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\ViewModel;

use Shared\Domain\Publication\Dossier\Type\DossierTypeConfigInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_reduce;
use function array_values;
use function sprintf;
use function uksort;

final readonly class DossierTypeViewFactory
{
    public function __construct(
        private TranslatorInterface $translator,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function make(DossierTypeConfigInterface $dossierTypeConfig): DossierType
    {
        return new DossierType(
            type: $dossierTypeConfig->getDossierType()->value,
            createUrl: $this->urlGenerator->generate($dossierTypeConfig->getCreateRouteName()),
        );
    }

    /**
     * @param array<array-key,DossierTypeConfigInterface> $dossierTypeConfigs
     *
     * @return list<DossierType>
     */
    public function makeCollection(array $dossierTypeConfigs): array
    {
        $result = array_reduce(
            $dossierTypeConfigs,
            function (array $carry, DossierTypeConfigInterface $dossierTypeConfig): array {
                $key = $this->translator->trans(
                    sprintf('dossier.type.%s', $dossierTypeConfig->getDossierType()->value),
                );

                $carry[$key] = $this->make($dossierTypeConfig);

                return $carry;
            },
            [],
        );

        uksort($result, fn (string $a, string $b): int => $a <=> $b);

        return array_values($result);
    }
}
