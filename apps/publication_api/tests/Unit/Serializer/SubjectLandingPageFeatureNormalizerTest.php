<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Serializer;

use Mockery;
use PublicationApi\Api\Subject\SubjectLandingPageOutputDto;
use PublicationApi\Api\Subject\SubjectResponse;
use PublicationApi\FeatureFlag\SubjectLandingPageGuard;
use PublicationApi\Serializer\SubjectLandingPageFeatureNormalizer;
use Shared\Domain\Publication\Subject\SubjectLandingPageStatus;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Uid\Uuid;

final class SubjectLandingPageFeatureNormalizerTest extends UnitTestCase
{
    public function testOmitsLandingPageWhenFeatureIsDisabled(): void
    {
        $normalizer = new SubjectLandingPageFeatureNormalizer(new SubjectLandingPageGuard(false));
        $delegate = Mockery::mock(NormalizerInterface::class);
        $delegate->expects('normalize')->andReturn([
            'id' => 'subject-id',
            'name' => 'Subject',
            'landingPage' => ['status' => 'published'],
        ]);
        $normalizer->setNormalizer($delegate);

        $result = $normalizer->normalize($this->responseWithLandingPage());

        self::assertSame([
            'id' => 'subject-id',
            'name' => 'Subject',
        ], $result);
    }

    private function responseWithLandingPage(): SubjectResponse
    {
        return new SubjectResponse(
            Uuid::v6(),
            'Subject',
            new SubjectLandingPageOutputDto(
                SubjectLandingPageStatus::PUBLISHED,
                'landing-page',
                'Title',
                'Description',
                [],
                null,
            ),
        );
    }
}
