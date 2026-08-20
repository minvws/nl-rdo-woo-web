<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Dossier\Uploads\MainDocument;

use PublicationApi\Tests\Integration\Api\Dossier\Uploads\ApiPublicationV1UploadTestCase;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\DraftDecision\DraftDecisionFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\DraftDecision\DraftDecisionMainDocumentFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function sprintf;
use function str_repeat;

final class DraftDecisionUploadMainDocumentTest extends ApiPublicationV1UploadTestCase
{
    public function testUpload(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $draftDecision = DraftDecisionFactory::new()->concept()->create([
            'organisation' => $organisation,
            'externalId' => $this->getFaker()->externalId(),
            'departments' => [$department],
        ]);
        $draftDecisionMainDocument = DraftDecisionMainDocumentFactory::createOne([
            'dossier' => $draftDecision,
        ]);

        $this->assertUpload(
            url: sprintf(
                '/api/publication/v1/organisation/%s/dossiers/draft-decision/external/%s/uploads/main-document',
                $organisation->getId(),
                $draftDecision->getExternalId(),
            ),
            dossierId: $draftDecision->getId()->toRfc4122(),
            entityId: $draftDecisionMainDocument->getId()->toRfc4122(),
            entityFileName: $draftDecisionMainDocument->getFileInfo()->getName(),
            uploadGroupId: UploadGroupId::MAIN_DOCUMENTS,
            entityParameterKey: 'mainDocumentId',
        );
    }

    public function testUploadWithoutFile(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $draftDecision = DraftDecisionFactory::createOne([
            'organisation' => $organisation,
            'externalId' => $this->getFaker()->externalId(),
            'departments' => [$department],
        ]);

        $this->assertUploadWithoutFile(sprintf(
            '/api/publication/v1/organisation/%s/dossiers/draft-decision/external/%s/uploads/main-document',
            $organisation->getId(),
            $draftDecision->getExternalId(),
        ));
    }

    public function testUploadWithTooLongDossierExternalId(): void
    {
        $organisation = OrganisationFactory::createOne();

        $client = self::createPublicationApiClient();
        $client->request(Request::METHOD_PUT, sprintf(
            '/api/publication/v1/organisation/%s/dossiers/draft-decision/external/%s/uploads/main-document',
            $organisation->getId(),
            str_repeat('x', 129),
        ));

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
