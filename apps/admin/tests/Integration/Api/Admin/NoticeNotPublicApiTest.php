<?php

declare(strict_types=1);

namespace Admin\Tests\Integration\Api\Admin;

use Admin\Api\Admin\NoticeNotPublic\NoticeNotPublicDto;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\NoticeNotPublic;
use Shared\Service\Security\User;
use Shared\Tests\Factory\Publication\Dossier\NoticeNotPublic\NoticeNotPublicFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantFactory;
use Shared\Tests\Factory\UserFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function sprintf;

final class NoticeNotPublicApiTest extends AdminApiTestCase
{
    public function testGetReturnsNullWhenNoNoticeExists(): void
    {
        $dossierAdminUser = $this->createDossierAdminUser();
        $dossier = CovenantFactory::createOne(['organisation' => $dossierAdminUser->getOrganisation()]);

        self::createAdminApiClient($dossierAdminUser)
            ->request(
                Request::METHOD_GET,
                sprintf('/balie/api/dossiers/%s/notice-not-public', $dossier->getId()),
            );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testCreateNoticeNotPublic(): void
    {
        $dossierAdminUser = $this->createDossierAdminUser();
        $dossier = CovenantFactory::createOne(['organisation' => $dossierAdminUser->getOrganisation()]);

        $data = [
            'formalDate' => $this->getFaker()->plainDate()->format('Y-m-d'),
            'grounds' => [$this->getFaker()->word()],
            'documentName' => $this->getFaker()->word(),
            'explanation' => $this->getFaker()->sentence(),
        ];

        self::createAdminApiClient($dossierAdminUser)
            ->request(
                Request::METHOD_POST,
                sprintf('/balie/api/dossiers/%s/notice-not-public', $dossier->getId()),
                ['json' => $data],
            );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertMatchesResourceItemJsonSchema(NoticeNotPublicDto::class);
        self::assertJsonContains($data);
    }

    public function testCreateReturnConflictWhenAlreadyExists(): void
    {
        $dossierAdminUser = $this->createDossierAdminUser();
        $dossier = CovenantFactory::createOne(['organisation' => $dossierAdminUser->getOrganisation()]);
        NoticeNotPublicFactory::createOne(['dossier' => $dossier]);

        $data = [
            'formalDate' => $this->getFaker()->plainDate()->format('Y-m-d'),
            'grounds' => [$this->getFaker()->word()],
        ];

        self::createAdminApiClient($dossierAdminUser)
            ->request(
                Request::METHOD_POST,
                sprintf('/balie/api/dossiers/%s/notice-not-public', $dossier->getId()),
                ['json' => $data],
            );

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    public function testUpdateNoticeNotPublic(): void
    {
        $dossierAdminUser = $this->createDossierAdminUser();
        $dossier = CovenantFactory::createOne(['organisation' => $dossierAdminUser->getOrganisation()]);
        NoticeNotPublicFactory::createOne(['dossier' => $dossier]);

        $data = [
            'formalDate' => $this->getFaker()->plainDate()->format('Y-m-d'),
            'grounds' => [$this->getFaker()->word()],
            'documentName' => $this->getFaker()->word(),
            'explanation' => $this->getFaker()->sentence(),
        ];

        self::createAdminApiClient($dossierAdminUser)
            ->request(
                Request::METHOD_PUT,
                sprintf('/balie/api/dossiers/%s/notice-not-public', $dossier->getId()),
                ['json' => $data],
            );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertMatchesResourceItemJsonSchema(NoticeNotPublicDto::class);
        self::assertJsonContains($data);

        self::createAdminApiClient($dossierAdminUser)
            ->request(
                Request::METHOD_GET,
                sprintf('/balie/api/dossiers/%s/notice-not-public', $dossier->getId()),
            );

        self::assertResponseIsSuccessful();
        self::assertJsonContains($data);
    }

    public function testDeleteNoticeNotPublic(): void
    {
        $dossierAdminUser = $this->createDossierAdminUser();
        $dossier = CovenantFactory::createOne(['organisation' => $dossierAdminUser->getOrganisation()]);
        $noticeNotPublic = NoticeNotPublicFactory::createOne(['dossier' => $dossier]);

        self::createAdminApiClient($dossierAdminUser)
            ->request(
                Request::METHOD_DELETE,
                sprintf('/balie/api/dossiers/%s/notice-not-public', $dossier->getId()),
            );

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
        self::assertDatabaseMissing(NoticeNotPublic::class, [
            'id' => $noticeNotPublic->getId(),
        ]);
    }

    public function testDeleteReturnsNotFoundWhenNotExists(): void
    {
        $dossierAdminUser = $this->createDossierAdminUser();
        $dossier = CovenantFactory::createOne(['organisation' => $dossierAdminUser->getOrganisation()]);

        self::createAdminApiClient($dossierAdminUser)
            ->request(
                Request::METHOD_DELETE,
                sprintf('/balie/api/dossiers/%s/notice-not-public', $dossier->getId()),
            );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testCreateValidationFailure(): void
    {
        $dossierAdminUser = $this->createDossierAdminUser();
        $dossier = CovenantFactory::createOne(['organisation' => $dossierAdminUser->getOrganisation()]);

        $invalidData = [
            'documentName' => $this->getFaker()->word(),
        ];

        self::createAdminApiClient($dossierAdminUser)
            ->request(
                Request::METHOD_POST,
                sprintf('/balie/api/dossiers/%s/notice-not-public', $dossier->getId()),
                ['json' => $invalidData],
            );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testCannotAccessAnotherOrganisationsDossier(): void
    {
        $dossierAdminUser = $this->createDossierAdminUser();
        $dossier = CovenantFactory::createOne();

        $data = [
            'formalDate' => $this->getFaker()->plainDate()->format('Y-m-d'),
            'grounds' => [$this->getFaker()->word()],
        ];

        self::createAdminApiClient($dossierAdminUser)
            ->request(
                Request::METHOD_POST,
                sprintf('/balie/api/dossiers/%s/notice-not-public', $dossier->getId()),
                ['json' => $data],
            );

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    private function createDossierAdminUser(): User
    {
        return UserFactory::new()
            ->asDossierAdmin()
            ->isEnabled()
            ->create();
    }
}
