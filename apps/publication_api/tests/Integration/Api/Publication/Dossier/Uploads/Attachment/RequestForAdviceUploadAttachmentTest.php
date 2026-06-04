<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Publication\Dossier\Uploads\Attachment;

use PublicationApi\Tests\Integration\Api\Publication\Dossier\Uploads\ApiPublicationV1UploadTestCase;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceFactory;
use Shared\ValueObject\ExternalId;

use function sprintf;

final class RequestForAdviceUploadAttachmentTest extends ApiPublicationV1UploadTestCase
{
    public function testUpload(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $requestForAdvice = RequestForAdviceFactory::createOne([
            'organisation' => $organisation,
            'externalId' => ExternalId::create($this->getFaker()->uuid()),
            'departments' => [$department],
        ]);
        $requestForAdviceAttachment = RequestForAdviceAttachmentFactory::createOne([
            'dossier' => $requestForAdvice,
            'externalId' => ExternalId::create($this->getFaker()->uuid()),
        ]);

        $this->assertUpload(
            url: sprintf(
                '/api/publication/v1/organisation/%s/dossiers/request-for-advice/E:%s/uploads/attachment/E:%s',
                $organisation->getId(),
                $requestForAdvice->getExternalId(),
                $requestForAdviceAttachment->getExternalId(),
            ),
            dossierId: $requestForAdvice->getId()->toRfc4122(),
            entityId: $requestForAdviceAttachment->getId()->toRfc4122(),
            entityFileName: $requestForAdviceAttachment->getFileInfo()->getName(),
            uploadGroupId: UploadGroupId::ATTACHMENTS,
            entityParameterKey: 'attachmentId',
        );
    }

    public function testUploadWithoutFile(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $requestForAdvice = RequestForAdviceFactory::createOne([
            'organisation' => $organisation,
            'externalId' => ExternalId::create($this->getFaker()->uuid()),
            'departments' => [$department],
        ]);
        $requestForAdviceAttachment = RequestForAdviceAttachmentFactory::createOne([
            'dossier' => $requestForAdvice,
            'externalId' => ExternalId::create($this->getFaker()->uuid()),
        ]);

        $this->assertUploadWithoutFile(sprintf(
            '/api/publication/v1/organisation/%s/dossiers/request-for-advice/E:%s/uploads/attachment/E:%s',
            $organisation->getId(),
            $requestForAdvice->getExternalId(),
            $requestForAdviceAttachment->getExternalId(),
        ));
    }
}
