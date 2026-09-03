<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Symfony\Translations;

use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Tests\Integration\SharedWebTestCase;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webmozart\Assert\Assert;

use function implode;
use function sprintf;

final class HistoryTranslationsTest extends SharedWebTestCase
{
    private const array MAIN_DOCUMENT_KEY_SUFFIXES = [
        'main_document_added.private',
        'main_document_updated.private',
        'main_document_replaced.private',
        'main_document_replaced.public',
        'main_document_deleted.private',
    ];

    private const array ATTACHMENT_KEYS = [
        'history.attachment_created.private',
        'history.attachment_created.public',
        'history.attachment_updated.private',
        'history.attachment_replaced.private',
        'history.attachment_replaced.public',
        'history.attachment_deleted.private',
        'history.attachment_withdrawn.private',
        'history.attachment_withdrawn.public',
    ];

    public function testEveryDossierTypeHasMainDocumentHistoryTranslations(): void
    {
        $keys = [];
        foreach (DossierType::cases() as $dossierType) {
            foreach (self::MAIN_DOCUMENT_KEY_SUFFIXES as $suffix) {
                $keys[] = sprintf('history.%s.%s', $dossierType->value, $suffix);
            }
        }

        $this->assertTranslationKeysExist($keys);
    }

    public function testAttachmentHistoryTranslationsExist(): void
    {
        $this->assertTranslationKeysExist(self::ATTACHMENT_KEYS);
    }

    /**
     * @param list<string> $keys
     */
    private function assertTranslationKeysExist(array $keys): void
    {
        $translator = self::getContainer()->get(TranslatorInterface::class);
        Assert::isInstanceOf($translator, TranslatorBagInterface::class);

        $catalogue = $translator->getCatalogue('nl');

        $missing = [];
        foreach ($keys as $key) {
            if (! $catalogue->has($key)) {
                $missing[] = $key;
            }
        }

        self::assertSame(
            [],
            $missing,
            'Missing (or null) history translations: ' . implode(', ', $missing),
        );
    }
}
