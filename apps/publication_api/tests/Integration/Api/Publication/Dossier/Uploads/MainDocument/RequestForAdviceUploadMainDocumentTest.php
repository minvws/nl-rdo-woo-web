<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Publication\Dossier\Uploads\MainDocument;

use PublicationApi\Tests\Integration\Api\Publication\Dossier\Uploads\ApiPublicationV1UploadTestCase;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceMainDocumentFactory;

use function sprintf;

final class RequestForAdviceUploadMainDocumentTest extends ApiPublicationV1UploadTestCase
{
    public function testUpload(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $requestForAdvice = RequestForAdviceFactory::createOne([
            'organisation' => $organisation,
            'externalId' => $this->getFaker()->externalId(),
            'departments' => [$department],
        ]);
        $requestForAdviceMainDocument = RequestForAdviceMainDocumentFactory::createOne([
            'dossier' => $requestForAdvice,
        ]);

        $this->assertUpload(
            url: sprintf(
                '/api/publication/v1/organisation/%s/dossiers/request-for-advice/E:%s/uploads/main-document',
                $organisation->getId(),
                $requestForAdvice->getExternalId(),
            ),
            dossierId: $requestForAdvice->getId()->toRfc4122(),
            entityId: $requestForAdviceMainDocument->getId()->toRfc4122(),
            entityFileName: $requestForAdviceMainDocument->getFileInfo()->getName(),
            uploadGroupId: UploadGroupId::MAIN_DOCUMENTS,
            entityParameterKey: 'mainDocumentId',
        );
    }

    public function testUploadWithoutFile(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $requestForAdvice = RequestForAdviceFactory::createOne([
            'organisation' => $organisation,
            'externalId' => $this->getFaker()->externalId(),
            'departments' => [$department],
        ]);

        $this->assertUploadWithoutFile(sprintf(
            '/api/publication/v1/organisation/%s/dossiers/request-for-advice/E:%s/uploads/main-document',
            $organisation->getId(),
            $requestForAdvice->getExternalId(),
        ));
    }
}
