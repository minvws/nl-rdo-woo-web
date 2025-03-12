<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\Schema;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
readonly class ElasticPath
{
    public string $value;

    private function __construct(ElasticNestedField|ElasticObjectField|ElasticField ...$parts)
    {
        $path = array_map(
            static fn ($item) => $item->value,
            $parts,
        );

        $this->value = implode('.', $path);
    }

    public static function pagesContent(): self
    {
        return new self(
            ElasticNestedField::PAGES,
            ElasticField::CONTENT,
        );
    }

    public static function pagesPageNr(): self
    {
        return new self(
            ElasticNestedField::PAGES,
            ElasticField::PAGE_NR
        );
    }

    public static function dossiersTitle(): self
    {
        return new self(
            ElasticNestedField::DOSSIERS,
            ElasticField::TITLE,
        );
    }

    public static function dossiersId(): self
    {
        return new self(
            ElasticNestedField::DOSSIERS,
            ElasticField::ID,
        );
    }

    public static function dossiersSummary(): self
    {
        return new self(
            ElasticNestedField::DOSSIERS,
            ElasticField::SUMMARY,
        );
    }

    public static function departmentsId(): self
    {
        return new self(
            ElasticObjectField::DEPARTMENTS,
            ElasticField::ID,
        );
    }

    public static function dossiersDepartmentsId(): self
    {
        return new self(
            ElasticNestedField::DOSSIERS,
            ElasticObjectField::DEPARTMENTS,
            ElasticField::ID,
        );
    }

    public static function subjectId(): self
    {
        return new self(
            ElasticObjectField::SUBJECT,
            ElasticField::ID,
        );
    }

    public static function dossiersSubjectId(): self
    {
        return new self(
            ElasticNestedField::DOSSIERS,
            ElasticObjectField::SUBJECT,
            ElasticField::ID,
        );
    }

    public static function dossiersType(): self
    {
        return new self(
            ElasticNestedField::DOSSIERS,
            ElasticField::TYPE,
        );
    }

    public static function dossiersStatus(): self
    {
        return new self(
            ElasticNestedField::DOSSIERS,
            ElasticField::STATUS,
        );
    }

    public static function dossiersInquiryIds(): self
    {
        return new self(
            ElasticNestedField::DOSSIERS,
            ElasticField::INQUIRY_IDS,
        );
    }
}
