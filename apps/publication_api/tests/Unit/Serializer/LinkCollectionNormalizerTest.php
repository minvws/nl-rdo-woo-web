<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Serializer;

use ArrayObject;
use InvalidArgumentException;
use Mockery;
use PublicationApi\Domain\OpenApi\Links\Link;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use PublicationApi\Serializer\LinkCollectionNormalizer;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\Url;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

use function json_encode;

final class LinkCollectionNormalizerTest extends UnitTestCase
{
    public function testNormalizesAnEmptyCollectionToAnEmptyJsonObject(): void
    {
        $normalizer = new LinkCollectionNormalizer();
        $normalizer->setNormalizer(Mockery::mock(NormalizerInterface::class));

        $result = $normalizer->normalize(new LinkCollection());

        self::assertEquals(new ArrayObject(), $result);
        self::assertSame('{}', json_encode($result));
    }

    public function testNormalizesEveryLinkWithTheInjectedNormalizer(): void
    {
        $selfUrl = $this->getFaker()->url();
        $inquiryUrl = $this->getFaker()->url();
        $inquiryNumber = $this->getFaker()->word();

        $selfLink = new Link(Url::create($selfUrl));
        $inquiryLink = new Link(Url::create($inquiryUrl), $inquiryNumber);

        $collection = new LinkCollection();
        $collection->set(LinkCollection::SELF, $selfLink);
        $collection->add(LinkCollection::INQUIRIES, $inquiryLink);

        $normalizer = Mockery::mock(NormalizerInterface::class);
        $normalizer
            ->expects('normalize')
            ->with($selfLink, 'json', [])
            ->andReturn(['href' => $selfUrl]);
        $normalizer
            ->expects('normalize')
            ->with([$inquiryLink], 'json', [])
            ->andReturn([['href' => $inquiryUrl, 'name' => $inquiryNumber]]);

        $linkCollectionNormalizer = new LinkCollectionNormalizer();
        $linkCollectionNormalizer->setNormalizer($normalizer);

        $result = $linkCollectionNormalizer->normalize($collection, 'json');

        self::assertEquals(
            new ArrayObject([
                LinkCollection::SELF => ['href' => $selfUrl],
                LinkCollection::INQUIRIES => [['href' => $inquiryUrl, 'name' => $inquiryNumber]],
            ]),
            $result,
        );
    }

    public function testNormalizeThrowsExceptionWhenDataIsNotALinkCollection(): void
    {
        $linkCollectionNormalizer = new LinkCollectionNormalizer();
        $linkCollectionNormalizer->setNormalizer(Mockery::mock(NormalizerInterface::class));

        $this->expectException(InvalidArgumentException::class);

        $linkCollectionNormalizer->normalize(new Link(Url::create($this->getFaker()->url())));
    }

    public function testSupportsNormalizationForLinkCollectionInstances(): void
    {
        $result = new LinkCollectionNormalizer()->supportsNormalization(new LinkCollection());

        self::assertTrue($result);
    }

    public function testDoesNotSupportNormalizationForNonLinkCollectionValues(): void
    {
        $result = new LinkCollectionNormalizer()->supportsNormalization(new Link(Url::create($this->getFaker()->url())));

        self::assertFalse($result);
    }

    public function testSupportedTypes(): void
    {
        $linkCollectionNormalizer = new LinkCollectionNormalizer();

        self::assertSame([LinkCollection::class => true], $linkCollectionNormalizer->getSupportedTypes(null));
    }
}
