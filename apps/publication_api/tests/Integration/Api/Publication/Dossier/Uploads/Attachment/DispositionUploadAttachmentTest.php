<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Publication\Dossier\Uploads\Attachment;

use PublicationApi\Tests\Integration\Api\Publication\Dossier\Uploads\ApiPublicationV1UploadTestCase;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Disposition\DispositionAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Disposition\DispositionFactory;
use Shared\ValueObject\ExternalId;

use function sprintf;

final class DispositionUploadAttachmentTest extends ApiPublicationV1UploadTestCase
{
    public function testUpload(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $disposition = DispositionFactory::createOne([
            'organisation' => $organisation,
            'externalId' => ExternalId::create($this->getFaker()->uuid()),
            'departments' => [$department],
        ]);
        $dispositionAttachment = DispositionAttachmentFactory::createOne([
            'dossier' => $disposition,
            'externalId' => ExternalId::create($this->getFaker()->uuid()),
        ]);

        $this->assertUpload(
            url: sprintf(
                '/api/publication/v1/organisation/%s/dossiers/disposition/E:%s/uploads/attachment/E:%s',
                $organisation->getId(),
                $disposition->getExternalId(),
                $dispositionAttachment->getExternalId(),
            ),
            dossierId: $disposition->getId()->toRfc4122(),
            entityId: $dispositionAttachment->getId()->toRfc4122(),
            entityFileName: $dispositionAttachment->getFileInfo()->getName(),
            uploadGroupId: UploadGroupId::ATTACHMENTS,
            entityParameterKey: 'attachmentId',
        );
    }

    public function testUploadWithoutFile(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $disposition = DispositionFactory::createOne([
            'organisation' => $organisation,
            'externalId' => ExternalId::create($this->getFaker()->uuid()),
            'departments' => [$department],
        ]);
        $dispositionAttachment = DispositionAttachmentFactory::createOne([
            'dossier' => $disposition,
            'externalId' => ExternalId::create($this->getFaker()->uuid()),
        ]);

        $this->assertUploadWithoutFile(sprintf(
            '/api/publication/v1/organisation/%s/dossiers/disposition/E:%s/uploads/attachment/E:%s',
            $organisation->getId(),
            $disposition->getExternalId(),
            $dispositionAttachment->getExternalId(),
        ));
    }
}
