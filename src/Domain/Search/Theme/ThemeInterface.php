<?php

declare(strict_types=1);

namespace App\Domain\Search\Theme;

use App\Service\Search\Query\Condition\QueryConditions;

interface ThemeInterface
{
    public function getUrlName(): string;

    public function getMenuNameTranslationKey(): string;

    public function getPageTitleTranslationKey(): string;

    public function getPageTextTranslationKey(): string;

    public function getBaseQueryConditions(): QueryConditions;
}
