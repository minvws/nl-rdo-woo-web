<?php

declare(strict_types=1);

namespace App\Domain\Search\Theme;

use App\Service\Search\Query\Condition\QueryConditionBuilderInterface;

readonly class Covid19Theme implements ThemeInterface
{
    public const URL_NAME = 'covid-19';

    public function __construct(
        private Covid19QueryConditionBuilder $queryConditions,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public function getUrlName(): string
    {
        return self::URL_NAME;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getMenuNameTranslationKey(): string
    {
        return 'public.theme.covid-19.menu_name';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getPageTitleTranslationKey(): string
    {
        return 'public.theme.covid-19.page_title';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getPageTextTranslationKey(): string
    {
        return 'public.theme.covid-19.page_text';
    }

    public function getBaseQueryConditionBuilder(): QueryConditionBuilderInterface
    {
        return $this->queryConditions;
    }
}
