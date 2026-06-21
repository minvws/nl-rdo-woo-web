<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Domain\OpenApi\Links;

use ArrayObject;
use PublicationApi\Domain\OpenApi\Links\Link;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\Url;

class LinkCollectionTest extends UnitTestCase
{
    public function testSetAndRetrieveLink(): void
    {
        $collection = new LinkCollection();
        $url = Url::create($this->getFaker()->url());
        $link = new Link($url);

        $collection->set(LinkCollection::SELF, $link);

        $this->assertEquals(
            new ArrayObject([LinkCollection::SELF => $link]),
            $collection->jsonSerialize(),
        );
    }

    public function testSetAndRetrieveLinkWithCustomKey(): void
    {
        $collection = new LinkCollection();
        $url = Url::create($this->getFaker()->url());
        $link = new Link($url);
        $key = $this->getFaker()->word();

        $collection->set($key, $link);

        $this->assertEquals(
            new ArrayObject([$key => $link]),
            $collection->jsonSerialize(),
        );
    }

    public function testSetMultipleLinks(): void
    {
        $collection = new LinkCollection();
        $selfUrl = Url::create($this->getFaker()->url());
        $publicUrl = Url::create($this->getFaker()->url());

        $collection->set(LinkCollection::SELF, new Link($selfUrl));
        $collection->set(LinkCollection::PUBLIC, new Link($publicUrl));

        $serialized = $collection->jsonSerialize();
        $this->assertCount(2, $serialized);
        $this->assertTrue($serialized->offsetExists(LinkCollection::SELF));
        $this->assertTrue($serialized->offsetExists(LinkCollection::PUBLIC));
    }

    public function testOverwriteExistingLink(): void
    {
        $collection = new LinkCollection();
        $firstUrl = Url::create($this->getFaker()->url());
        $secondUrl = Url::create($this->getFaker()->url());

        $collection->set(LinkCollection::SELF, new Link($firstUrl));
        $collection->set(LinkCollection::SELF, new Link($secondUrl));

        $serialized = $collection->jsonSerialize();
        $this->assertCount(1, $serialized);
        $this->assertEquals($secondUrl->toString(), $serialized->offsetGet(LinkCollection::SELF)?->href->toString());
    }

    public function testJsonSerializeReturnsEmptyArrayObjectWhenNoLinksSet(): void
    {
        $collection = new LinkCollection();

        $this->assertEquals(new ArrayObject(), $collection->jsonSerialize());
    }
}
