<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Publication\Dossier\Uploads\MainDocument;

use PublicationApi\Tests\Integration\Api\Publication\Dossier\Uploads\ApiPublicationV1UploadTestCase;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\OtherPublication\OtherPublicationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\OtherPublication\OtherPublicationMainDocumentFactory;

use function sprintf;

final class OtherPublicationUploadMainDocumentTest extends ApiPublicationV1UploadTestCase
{
    public function testUpload(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $otherPublication = OtherPublicationFactory::createOne([
            'organisation' => $organisation,
            'externalId' => $this->getFaker()->externalId(),
            'departments' => [$department],
        ]);
        $otherPublicationMainDocument = OtherPublicationMainDocumentFactory::createOne([
            'dossier' => $otherPublication,
        ]);

        $this->assertUpload(
            url: sprintf(
                '/api/publication/v1/organisation/%s/dossiers/other-publication/E:%s/uploads/main-document',
                $organisation->getId(),
                $otherPublication->getExternalId(),
            ),
            dossierId: $otherPublication->getId()->toRfc4122(),
            entityId: $otherPublicationMainDocument->getId()->toRfc4122(),
            entityFileName: $otherPublicationMainDocument->getFileInfo()->getName(),
            uploadGroupId: UploadGroupId::MAIN_DOCUMENTS,
            entityParameterKey: 'mainDocumentId',
        );
    }

    public function testUploadWithoutFile(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $otherPublication = OtherPublicationFactory::createOne([
            'organisation' => $organisation,
            'externalId' => $this->getFaker()->externalId(),
            'departments' => [$department],
        ]);

        $this->assertUploadWithoutFile(sprintf(
            '/api/publication/v1/organisation/%s/dossiers/other-publication/E:%s/uploads/main-document',
            $organisation->getId(),
            $otherPublication->getExternalId(),
        ));
    }
}
