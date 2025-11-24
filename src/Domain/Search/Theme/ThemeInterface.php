<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Theme;

use Shared\Service\Search\Query\Condition\QueryConditionBuilderInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('woo_platform.search.theme')]
interface ThemeInterface
{
    public function getUrlName(): string;

    public function getMenuNameTranslationKey(): string;

    public function getPageTitleTranslationKey(): string;

    public function getPageTextTranslationKey(): string;

    public function getBaseQueryConditionBuilder(): QueryConditionBuilderInterface;
}
