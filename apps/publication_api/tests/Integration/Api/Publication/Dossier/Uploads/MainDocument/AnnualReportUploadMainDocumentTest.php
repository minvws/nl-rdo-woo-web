<?php

declare(strict_types=1);

namespace Integration\Api\Publication\Dossier\Uploads\MainDocument;

use PublicationApi\Tests\Integration\Api\Publication\Dossier\Uploads\ApiPublicationV1UploadTestCase;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocumentFactory;

use function sprintf;

final class AnnualReportUploadMainDocumentTest extends ApiPublicationV1UploadTestCase
{
    public function testUpload(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $annualReport = AnnualReportFactory::createOne([
            'organisation' => $organisation,
            'externalId' => $this->getFaker()->externalId(),
            'departments' => [$department],
        ]);
        $annualReportMainDocument = AnnualReportMainDocumentFactory::createOne([
            'dossier' => $annualReport,
        ]);

        $this->assertUpload(
            url: sprintf(
                '/api/publication/v1/organisation/%s/dossiers/annual-report/E:%s/uploads/main-document',
                $organisation->getId(),
                $annualReport->getExternalId(),
            ),
            dossierId: $annualReport->getId()->toRfc4122(),
            entityId: $annualReportMainDocument->getId()->toRfc4122(),
            entityFileName: $annualReportMainDocument->getFileInfo()->getName(),
            uploadGroupId: UploadGroupId::MAIN_DOCUMENTS,
            entityParameterKey: 'mainDocumentId',
        );
    }

    public function testUploadWithoutFile(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $annualReport = AnnualReportFactory::createOne([
            'organisation' => $organisation,
            'externalId' => $this->getFaker()->externalId(),
            'departments' => [$department],
        ]);

        $this->assertUploadWithoutFile(sprintf(
            '/api/publication/v1/organisation/%s/dossiers/annual-report/E:%s/uploads/main-document',
            $organisation->getId(),
            $annualReport->getExternalId(),
        ));
    }
}
