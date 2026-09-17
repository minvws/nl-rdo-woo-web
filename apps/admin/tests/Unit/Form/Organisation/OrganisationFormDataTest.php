<?php

declare(strict_types=1);

namespace Admin\Tests\Unit\Form\Organisation;

use Admin\Form\Organisation\OrganisationFormData;
use Shared\Domain\Organisation\Organisation;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\OrganisationPrefix;

final class OrganisationFormDataTest extends UnitTestCase
{
    public function testFromEntityCopiesEditableOrganisationData(): void
    {
        $organisation = new Organisation();
        $organisation->setName('Organisation name');
        $organisation->setPrefix(OrganisationPrefix::create('ABC-12'));

        $data = OrganisationFormData::fromEntity($organisation);

        self::assertSame('Organisation name', $data->name);
        self::assertNotNull($data->prefix);
        self::assertSame('ABC-12', $data->prefix->toString());
        self::assertSame($organisation->getId(), $data->organisationId);
        self::assertSame([], $data->departments);
    }
}
