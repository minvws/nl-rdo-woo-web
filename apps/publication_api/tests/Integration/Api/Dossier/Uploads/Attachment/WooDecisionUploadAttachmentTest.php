<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Dossier\Uploads\Attachment;

use PublicationApi\Tests\Integration\Api\Dossier\Uploads\ApiPublicationV1UploadTestCase;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\ValueObject\ExternalId;

use function sprintf;

final class WooDecisionUploadAttachmentTest extends ApiPublicationV1UploadTestCase
{
    public function testUpload(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::createOne([
            'organisation' => $organisation,
            'externalId' => ExternalId::create($this->getFaker()->uuid()),
            'previewDate' => $this->getFaker()->plainDate(),
            'departments' => [$department],
        ]);
        $wooDecisionAttachment = WooDecisionAttachmentFactory::createOne([
            'dossier' => $wooDecision,
            'externalId' => ExternalId::create($this->getFaker()->uuid()),
        ]);

        $this->assertUpload(
            url: sprintf(
                '/api/publication/v1/organisation/%s/dossiers/woo-decision/external/%s/uploads/attachment/external/%s',
                $organisation->getId(),
                $wooDecision->getExternalId(),
                $wooDecisionAttachment->getExternalId(),
            ),
            dossierId: $wooDecision->getId()->toRfc4122(),
            entityId: $wooDecisionAttachment->getId()->toRfc4122(),
            entityFileName: $wooDecisionAttachment->getFileInfo()->getName(),
            uploadGroupId: UploadGroupId::ATTACHMENTS,
            entityParameterKey: 'attachmentId',
        );
    }

    public function testUploadWithoutFile(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::createOne([
            'organisation' => $organisation,
            'externalId' => ExternalId::create($this->getFaker()->uuid()),
            'previewDate' => $this->getFaker()->plainDate(),
            'departments' => [$department],
        ]);
        $wooDecisionAttachment = WooDecisionAttachmentFactory::createOne([
            'dossier' => $wooDecision,
            'externalId' => ExternalId::create($this->getFaker()->uuid()),
        ]);

        $this->assertUploadWithoutFile(sprintf(
            '/api/publication/v1/organisation/%s/dossiers/woo-decision/external/%s/uploads/attachment/external/%s',
            $organisation->getId(),
            $wooDecision->getExternalId(),
            $wooDecisionAttachment->getExternalId(),
        ));
    }
}
