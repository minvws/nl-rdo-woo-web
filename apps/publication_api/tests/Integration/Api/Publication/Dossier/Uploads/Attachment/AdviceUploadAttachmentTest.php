<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Publication\Dossier\Uploads\Attachment;

use PublicationApi\Tests\Integration\Api\Publication\Dossier\Uploads\ApiPublicationV1UploadTestCase;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Advice\AdviceAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Advice\AdviceFactory;
use Shared\ValueObject\ExternalId;

use function sprintf;

final class AdviceUploadAttachmentTest extends ApiPublicationV1UploadTestCase
{
    public function testUpload(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $advice = AdviceFactory::createOne([
            'organisation' => $organisation,
            'externalId' => ExternalId::create($this->getFaker()->uuid()),
            'departments' => [$department],
        ]);
        $adviceAttachment = AdviceAttachmentFactory::createOne([
            'dossier' => $advice,
            'externalId' => ExternalId::create($this->getFaker()->uuid()),
        ]);

        $this->assertUpload(
            url: sprintf(
                '/api/publication/v1/organisation/%s/dossiers/advice/E:%s/uploads/attachment/E:%s',
                $organisation->getId(),
                $advice->getExternalId(),
                $adviceAttachment->getExternalId(),
            ),
            dossierId: $advice->getId()->toRfc4122(),
            entityId: $adviceAttachment->getId()->toRfc4122(),
            entityFileName: $adviceAttachment->getFileInfo()->getName(),
            uploadGroupId: UploadGroupId::ATTACHMENTS,
            entityParameterKey: 'attachmentId',
        );
    }

    public function testUploadWithoutFile(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $advice = AdviceFactory::createOne([
            'organisation' => $organisation,
            'externalId' => ExternalId::create($this->getFaker()->uuid()),
            'departments' => [$department],
        ]);
        $adviceAttachment = AdviceAttachmentFactory::createOne([
            'dossier' => $advice,
            'externalId' => ExternalId::create($this->getFaker()->uuid()),
        ]);

        $this->assertUploadWithoutFile(sprintf(
            '/api/publication/v1/organisation/%s/dossiers/advice/E:%s/uploads/attachment/E:%s',
            $organisation->getId(),
            $advice->getExternalId(),
            $adviceAttachment->getExternalId(),
        ));
    }
}
