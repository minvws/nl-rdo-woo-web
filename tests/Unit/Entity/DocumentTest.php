<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Document;
use App\Entity\Judgement;
use PHPUnit\Framework\TestCase;

class DocumentTest extends TestCase
{
    /**
     * @dataProvider shouldBeUploadedProvider
     */
    public function testShouldBeUploaded(Judgement $judgement, ?bool $suspended, bool $expectedResult): void
    {
        $document = new Document();
        $document->setJudgement($judgement);
        $document->setSuspended($suspended);

        $this->assertEquals(
            $expectedResult,
            $document->shouldBeUploaded()
        );
    }

    public static function shouldBeUploadedProvider(): array
    {
        return [
            'public' => [
                'judgement' => Judgement::PUBLIC,
                'suspended' => false,
                'expectedResult' => true,
            ],
            'public-suspended' => [
                'judgement' => Judgement::PUBLIC,
                'suspended' => true,
                'expectedResult' => false,
            ],
            'partial-public' => [
                'judgement' => Judgement::PARTIAL_PUBLIC,
                'suspended' => false,
                'expectedResult' => true,
            ],
            'partial-public-suspended' => [
                'judgement' => Judgement::PARTIAL_PUBLIC,
                'suspended' => true,
                'expectedResult' => false,
            ],
            'already-public' => [
                'judgement' => Judgement::ALREADY_PUBLIC,
                'suspended' => false,
                'expectedResult' => false,
            ],
            'already-public-suspended' => [
                'judgement' => Judgement::ALREADY_PUBLIC,
                'suspended' => true,
                'expectedResult' => false,
            ],
            'not-public' => [
                'judgement' => Judgement::NOT_PUBLIC,
                'suspended' => false,
                'expectedResult' => false,
            ],
            'not-public-suspended' => [
                'judgement' => Judgement::NOT_PUBLIC,
                'suspended' => true,
                'expectedResult' => false,
            ],
        ];
    }
}
