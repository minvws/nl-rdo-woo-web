<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Index\Covenant;

use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Search\Index\AbstractDossierMapper;
use App\Domain\Search\Index\Covenant\CovenantMapper;
use App\Domain\Search\Index\ElasticDocumentType;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class CovenantMapperTest extends MockeryTestCase
{
    private CovenantMapper $mapper;
    private AbstractDossierMapper&MockInterface $abstractDossierMapper;

    public function setUp(): void
    {
        $this->abstractDossierMapper = \Mockery::mock(AbstractDossierMapper::class);

        $this->mapper = new CovenantMapper($this->abstractDossierMapper);

        parent::setUp();
    }

    public function testMap(): void
    {
        $dossier = \Mockery::mock(Covenant::class);

        $this->abstractDossierMapper->expects('mapCommonFields')->with($dossier)->andReturn([
            'foo' => 'bar',
        ]);

        $doc = $this->mapper->map($dossier);

        self::assertEquals(
            [
                'type' => ElasticDocumentType::COVENANT,
                'foo' => 'bar',
            ],
            $doc->getFieldValues(),
        );
    }
}
