<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Theme;

use Shared\Service\Search\Query\Condition\QueryConditionBuilderInterface;

interface ThemeInterface
{
    public function getUrlName(): string;

    public function getMenuNameTranslationKey(): string;

    public function getPageTitleTranslationKey(): string;

    public function getPageTextTranslationKey(): string;

    public function getBaseQueryConditionBuilder(): QueryConditionBuilderInterface;
}
