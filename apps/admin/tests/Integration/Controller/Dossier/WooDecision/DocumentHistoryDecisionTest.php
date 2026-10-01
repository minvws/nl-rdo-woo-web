<?php

declare(strict_types=1);

namespace Admin\Tests\Integration\Controller\Dossier\WooDecision;

use Admin\Tests\Integration\AdminWebTestCase;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Service\HistoryService;
use Shared\Tests\Factory\DocumentFactory;
use Shared\Tests\Factory\History\HistoryFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Factory\UserFactory;

use function sprintf;

final class DocumentHistoryDecisionTest extends AdminWebTestCase
{
    public function testDocumentPageRendersWhenAHistoryEntryHasNoDecision(): void
    {
        $client = static::createClient();

        $user = UserFactory::new()
            ->asSuperAdmin()
            ->isEnabled()
            ->create();

        $dossier = WooDecisionFactory::createOne([
            'organisation' => $user->getOrganisation(),
        ]);

        $document = DocumentFactory::createOne([
            'dossiers' => [$dossier],
            'judgement' => Judgement::PUBLIC,
        ]);

        HistoryFactory::createOne([
            'type' => HistoryService::TYPE_DOCUMENT,
            'identifier' => $document->getId(),
            'contextKey' => 'document_withdraw',
            'context' => ['explanation' => '%global.document.withdraw.reason.data_in_document%'],
        ]);

        $crawler = $client->loginUser($user, 'balie')
            ->request('GET', sprintf(
                '/balie/dossier/woodecision/document/summary/%s/%s/%s',
                $dossier->getDocumentPrefix(),
                $dossier->getDossierNumber(),
                $document->getDocumentNumber(),
            ));

        self::assertResponseIsSuccessful();
        self::assertGreaterThan(0, $crawler->filter('[data-e2e-name="document-history-decision"]')->count());
    }
}
