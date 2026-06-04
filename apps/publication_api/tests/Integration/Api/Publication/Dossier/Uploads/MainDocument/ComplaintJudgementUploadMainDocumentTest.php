<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Publication\Dossier\Uploads\MainDocument;

use PublicationApi\Tests\Integration\Api\Publication\Dossier\Uploads\ApiPublicationV1UploadTestCase;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementMainDocumentFactory;

use function sprintf;

final class ComplaintJudgementUploadMainDocumentTest extends ApiPublicationV1UploadTestCase
{
    public function testUpload(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $complaintJudgement = ComplaintJudgementFactory::createOne([
            'organisation' => $organisation,
            'externalId' => $this->getFaker()->externalId(),
            'departments' => [$department],
        ]);
        $complaintJudgementMainDocument = ComplaintJudgementMainDocumentFactory::createOne([
            'dossier' => $complaintJudgement,
        ]);

        $this->assertUpload(
            url: sprintf(
                '/api/publication/v1/organisation/%s/dossiers/complaint-judgement/E:%s/uploads/main-document',
                $organisation->getId(),
                $complaintJudgement->getExternalId(),
            ),
            dossierId: $complaintJudgement->getId()->toRfc4122(),
            entityId: $complaintJudgementMainDocument->getId()->toRfc4122(),
            entityFileName: $complaintJudgementMainDocument->getFileInfo()->getName(),
            uploadGroupId: UploadGroupId::MAIN_DOCUMENTS,
            entityParameterKey: 'mainDocumentId',
        );
    }

    public function testUploadWithoutFile(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $complaintJudgement = ComplaintJudgementFactory::createOne([
            'organisation' => $organisation,
            'externalId' => $this->getFaker()->externalId(),
            'departments' => [$department],
        ]);

        $this->assertUploadWithoutFile(sprintf(
            '/api/publication/v1/organisation/%s/dossiers/complaint-judgement/E:%s/uploads/main-document',
            $organisation->getId(),
            $complaintJudgement->getExternalId(),
        ));
    }
}
