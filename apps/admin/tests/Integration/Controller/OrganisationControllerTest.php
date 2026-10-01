<?php

declare(strict_types=1);

namespace Admin\Tests\Integration\Controller;

use Admin\Tests\Integration\AdminWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantFactory;
use Shared\Tests\Factory\UserFactory;
use Shared\ValueObject\OrganisationPrefix;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

use function sprintf;

final class OrganisationControllerTest extends AdminWebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->client->loginUser(UserFactory::new()->asSuperAdmin()->isEnabled()->create(), 'balie');
    }

    public function testModifyShowsAnEditablePrefixWhenTheOrganisationHasNoDossiers(): void
    {
        $organisation = OrganisationFactory::createOne(['prefix' => OrganisationPrefix::create('OLD-01')]);

        $crawler = $this->client->request('GET', $this->editUrl($organisation));

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('[data-e2e-name="organisation-prefix-input"]'));
    }

    public function testModifyShowsAReadOnlyPrefixWhenTheOrganisationHasDossiers(): void
    {
        $organisation = OrganisationFactory::createOne(['prefix' => OrganisationPrefix::create('OLD-01')]);
        CovenantFactory::createOne(['organisation' => $organisation]);

        $crawler = $this->client->request('GET', $this->editUrl($organisation));

        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('[data-e2e-name="organisation-prefix-input"]'));
        self::assertSelectorTextSame('.bhr-readonly-text', 'OLD-01');
    }

    public function testModifyChangesThePrefixWhenTheOrganisationHasNoDossiers(): void
    {
        $organisation = OrganisationFactory::createOne(['prefix' => OrganisationPrefix::create('OLD-01')]);

        $this->submitEditForm($organisation, 'new-02');

        self::assertResponseRedirects('/balie/organisatie');
        self::assertSame('NEW-02', $this->reload($organisation)->getPrefix()->toString());
    }

    public function testModifyRejectsAPrefixChangeWhenTheOrganisationHasDossiers(): void
    {
        $organisation = OrganisationFactory::createOne(['prefix' => OrganisationPrefix::create('OLD-01')]);
        CovenantFactory::createOne(['organisation' => $organisation]);

        $this->submitEditForm($organisation, 'NEW-02');

        self::assertResponseIsUnprocessable();
        self::assertSame('OLD-01', $this->reload($organisation)->getPrefix()->toString());
    }

    private function submitEditForm(Organisation $organisation, string $prefix): void
    {
        $department = $organisation->getDepartments()->first();
        self::assertInstanceOf(Department::class, $department);

        $form = $this->client
            ->request('GET', $this->editUrl($organisation))
            ->filter('[data-e2e-name="organisation-form-submit"]')
            ->form();

        $fields = $form->getPhpValues()['organisation_form'];
        self::assertIsArray($fields);
        $fields['prefix'] = $prefix;
        $fields['departments'] = [$department->getId()->toRfc4122()];

        $this->client->request($form->getMethod(), $form->getUri(), ['organisation_form' => $fields]);
    }

    private function reload(Organisation $organisation): Organisation
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();

        $reloaded = $entityManager->find(Organisation::class, $organisation->getId());
        self::assertNotNull($reloaded);

        return $reloaded;
    }

    private function editUrl(Organisation $organisation): string
    {
        return sprintf('/balie/organisatie/%s', $organisation->getId());
    }
}
