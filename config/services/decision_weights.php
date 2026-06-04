<?php

declare(strict_types=1);

use Shared\Domain\Publication\Dossier\Type\WooDecision\Decision\DecisionType;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->parameters()->set('decision_ranking_weights', [
        DecisionType::PUBLIC->value => 1.0,
        DecisionType::PARTIAL_PUBLIC->value => 1.0,
        DecisionType::ALREADY_PUBLIC->value => 0.8,
        DecisionType::NOT_PUBLIC->value => 0.6,
        DecisionType::NOTHING_FOUND->value => 0.6,
    ]);
};
