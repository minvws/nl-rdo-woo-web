<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Dossier\Uploads\Attachment;

use PublicationApi\Tests\Integration\Api\Dossier\Uploads\ApiPublicationV1UploadTestCase;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\OtherPublication\OtherPublicationAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\OtherPublication\OtherPublicationFactory;
use Shared\ValueObject\ExternalId;

use function sprintf;

final class OtherPublicationUploadAttachmentTest extends ApiPublicationV1UploadTestCase
{
    public function testUpload(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $otherPublication = OtherPublicationFactory::createOne([
            'organisation' => $organisation,
            'externalId' => ExternalId::create($this->getFaker()->uuid()),
            'departments' => [$department],
        ]);
        $otherPublicationAttachment = OtherPublicationAttachmentFactory::createOne([
            'dossier' => $otherPublication,
            'externalId' => ExternalId::create($this->getFaker()->uuid()),
        ]);

        $this->assertUpload(
            url: sprintf(
                '/api/publication/v1/organisation/%s/dossiers/other-publication/external/%s/uploads/attachment/external/%s',
                $organisation->getId(),
                $otherPublication->getExternalId(),
                $otherPublicationAttachment->getExternalId(),
            ),
            dossierId: $otherPublication->getId()->toRfc4122(),
            entityId: $otherPublicationAttachment->getId()->toRfc4122(),
            entityFileName: $otherPublicationAttachment->getFileInfo()->getName(),
            uploadGroupId: UploadGroupId::ATTACHMENTS,
            entityParameterKey: 'attachmentId',
        );
    }

    public function testUploadWithoutFile(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $otherPublication = OtherPublicationFactory::createOne([
            'organisation' => $organisation,
            'externalId' => ExternalId::create($this->getFaker()->uuid()),
            'departments' => [$department],
        ]);
        $otherPublicationAttachment = OtherPublicationAttachmentFactory::createOne([
            'dossier' => $otherPublication,
            'externalId' => ExternalId::create($this->getFaker()->uuid()),
        ]);

        $this->assertUploadWithoutFile(sprintf(
            '/api/publication/v1/organisation/%s/dossiers/other-publication/external/%s/uploads/attachment/external/%s',
            $organisation->getId(),
            $otherPublication->getExternalId(),
            $otherPublicationAttachment->getExternalId(),
        ));
    }
}
