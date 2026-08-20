<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Dossier;

use PHPUnit\Framework\Attributes\DataProvider;
use PublicationApi\FeatureFlag\DraftDecisionFeatureVoter;
use PublicationApi\Tests\Integration\Api\ApiPublicationV1TestCase;
use Shared\Tests\Factory\OrganisationFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function str_replace;

final class DraftDecisionFeatureFlagTest extends ApiPublicationV1TestCase
{
    /**
     * @param array<string, mixed> $options
     */
    #[DataProvider('draftDecisionEndpoints')]
    public function testDraftDecisionResourcesAreForbiddenWhenFeatureIsDisabled(
        string $method,
        string $url,
        array $options = [],
    ): void {
        $client = $this->createPublicationApiClient();
        $voter = new DraftDecisionFeatureVoter(false);
        self::getContainer()->set(DraftDecisionFeatureVoter::class, $voter);
        self::getContainer()->set('.debug.security.voter.' . DraftDecisionFeatureVoter::class, $voter);

        $organisation = OrganisationFactory::createOne();
        $url = str_replace('/organisation/org/', '/organisation/' . $organisation->getId() . '/', $url);

        $client->request($method, $url, $options);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        self::assertJsonContains(['detail' => 'feature is not enabled']);
    }

    /**
     * @return iterable<string, array{string, string, array<string, mixed>}>
     */
    public static function draftDecisionEndpoints(): iterable
    {
        $uploadOptions = [
            'headers' => ['Content-Type' => 'application/octet-stream'],
            'body' => '',
        ];

        yield 'main resource' => [
            Request::METHOD_GET,
            '/api/publication/v1/organisation/org/dossiers/draft-decision',
            [],
        ];
        yield 'main document upload resource' => [
            Request::METHOD_PUT,
            '/api/publication/v1/organisation/org/dossiers/draft-decision/external/dossier/uploads/main-document',
            $uploadOptions,
        ];
        yield 'attachment upload resource' => [
            Request::METHOD_PUT,
            '/api/publication/v1/organisation/org/dossiers/draft-decision/external/dossier/uploads/attachment/external/attachment',
            $uploadOptions,
        ];
    }
}
