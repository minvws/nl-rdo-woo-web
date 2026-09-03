<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Translation;

use Mockery;
use ReflectionEnum;
use ReflectionEnumBackedCase;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Attachment\Enum\AttachmentWithdrawReason;
use Shared\Domain\Publication\Dossier\Admin\Action\DossierAdminAction;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Decision\DecisionType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawReason;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use Shared\Domain\Publication\SourceType;
use Shared\Domain\Search\Index\ElasticDocumentType;
use Shared\Service\Search\Query\Sort\SortField;
use Shared\Service\Search\Query\Sort\SortOrder;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use UnitEnum;

use function array_column;
use function array_unique;
use function count;
use function dirname;
use function sprintf;

final class TranslatableEnumTranslationCatalogTest extends UnitTestCase
{
    public function testAllTranslatableEnumCasesHaveDutchAndEnglishTranslations(): void
    {
        $enumClasses = self::translatableEnumClasses();
        $expectedCallCount = self::countEnumCases($enumClasses);

        $translationKeys = self::collectTranslationKeys($enumClasses, $expectedCallCount);

        foreach (['nl', 'en'] as $locale) {
            self::assertCatalogsContainKeys($translationKeys, $locale);
        }
    }

    /**
     * @param list<class-string<UnitEnum>> $enumClasses
     */
    private static function countEnumCases(array $enumClasses): int
    {
        $total = 0;
        foreach ($enumClasses as $enumClass) {
            $total += count(new ReflectionEnum($enumClass)->getCases());
        }

        return $total;
    }

    /**
     * @param list<class-string<UnitEnum>> $enumClasses
     *
     * @return list<array{id: string, domain: string}>
     */
    private static function collectTranslationKeys(array $enumClasses, int $expectedCallCount): array
    {
        $translationKeys = [];

        $translator = Mockery::mock(TranslatorInterface::class);
        $translator->expects('trans')->times($expectedCallCount)->andReturnUsing(
            static function (
                string $id,
                array $parameters = [],
                ?string $domain = null,
                ?string $locale = null,
            ) use (&$translationKeys): string {
                $translationKeys[] = ['id' => $id, 'domain' => $domain ?? 'messages'];

                return $id;
            },
        );

        foreach ($enumClasses as $enumClass) {
            foreach (new ReflectionEnum($enumClass)->getCases() as $case) {
                self::assertInstanceOf(ReflectionEnumBackedCase::class, $case);
                $enum = $case->getValue();
                self::assertInstanceOf(TranslatableInterface::class, $enum);
                $enum->trans($translator);
            }
        }

        return $translationKeys;
    }

    /**
     * @param list<array{id: string, domain: string}> $translationKeys
     */
    private static function assertCatalogsContainKeys(array $translationKeys, string $locale): void
    {
        $domains = array_unique(array_column($translationKeys, 'domain'));

        $catalogs = [];
        foreach ($domains as $domain) {
            $catalogs[$domain] = self::loadCatalog($domain, $locale);
        }

        foreach ($translationKeys as ['id' => $id, 'domain' => $domain]) {
            self::assertArrayHasKey(
                $id,
                $catalogs[$domain],
                sprintf('Missing "%s" translation for "%s" (domain "%s").', $locale, $id, $domain),
            );
        }
    }

    /**
     * @return list<class-string<UnitEnum>>
     */
    private static function translatableEnumClasses(): array
    {
        return [
            AttachmentLanguage::class,
            AttachmentType::class,
            AttachmentWithdrawReason::class,
            DossierAdminAction::class,
            DossierStatus::class,
            DossierType::class,
            DecisionType::class,
            DocumentWithdrawReason::class,
            ElasticDocumentType::class,
            Judgement::class,
            PublicationReason::class,
            SortField::class,
            SortOrder::class,
            SourceType::class,
        ];
    }

    /**
     * @return array<array-key, mixed>
     */
    private static function loadCatalog(string $domain, string $locale): array
    {
        $catalog = Yaml::parseFile(
            sprintf('%s/translations/%s+intl-icu.%s.yaml', dirname(__DIR__, 3), $domain, $locale),
        );

        self::assertIsArray($catalog);

        return $catalog;
    }
}
