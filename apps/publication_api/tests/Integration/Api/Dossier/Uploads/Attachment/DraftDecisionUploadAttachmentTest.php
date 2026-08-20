<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Dossier\Uploads\Attachment;

use PublicationApi\Tests\Integration\Api\Dossier\Uploads\ApiPublicationV1UploadTestCase;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\DraftDecision\DraftDecisionAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\DraftDecision\DraftDecisionFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function sprintf;
use function str_repeat;

final class DraftDecisionUploadAttachmentTest extends ApiPublicationV1UploadTestCase
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
        $draftDecisionAttachment = DraftDecisionAttachmentFactory::createOne([
            'dossier' => $draftDecision,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $this->assertUpload(
            url: sprintf(
                '/api/publication/v1/organisation/%s/dossiers/draft-decision/external/%s/uploads/attachment/external/%s',
                $organisation->getId(),
                $draftDecision->getExternalId(),
                $draftDecisionAttachment->getExternalId(),
            ),
            dossierId: $draftDecision->getId()->toRfc4122(),
            entityId: $draftDecisionAttachment->getId()->toRfc4122(),
            entityFileName: $draftDecisionAttachment->getFileInfo()->getName(),
            uploadGroupId: UploadGroupId::ATTACHMENTS,
            entityParameterKey: 'attachmentId',
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
        $draftDecisionAttachment = DraftDecisionAttachmentFactory::createOne([
            'dossier' => $draftDecision,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $this->assertUploadWithoutFile(sprintf(
            '/api/publication/v1/organisation/%s/dossiers/draft-decision/external/%s/uploads/attachment/external/%s',
            $organisation->getId(),
            $draftDecision->getExternalId(),
            $draftDecisionAttachment->getExternalId(),
        ));
    }

    public function testUploadWithTooLongDossierExternalId(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $draftDecision = DraftDecisionFactory::createOne([
            'organisation' => $organisation,
            'externalId' => $this->getFaker()->externalId(),
            'departments' => [$department],
        ]);
        $draftDecisionAttachment = DraftDecisionAttachmentFactory::createOne([
            'dossier' => $draftDecision,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $client = self::createPublicationApiClient();
        $client->request(Request::METHOD_PUT, sprintf(
            '/api/publication/v1/organisation/%s/dossiers/draft-decision/external/%s/uploads/attachment/external/%s',
            $organisation->getId(),
            str_repeat('x', 129),
            $draftDecisionAttachment->getExternalId(),
        ));

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testUploadWithTooLongAttachmentExternalId(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $draftDecision = DraftDecisionFactory::createOne([
            'organisation' => $organisation,
            'externalId' => $this->getFaker()->externalId(),
            'departments' => [$department],
        ]);

        $client = self::createPublicationApiClient();
        $client->request(Request::METHOD_PUT, sprintf(
            '/api/publication/v1/organisation/%s/dossiers/draft-decision/external/%s/uploads/attachment/external/%s',
            $organisation->getId(),
            $draftDecision->getExternalId(),
            str_repeat('x', 129),
        ));

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
