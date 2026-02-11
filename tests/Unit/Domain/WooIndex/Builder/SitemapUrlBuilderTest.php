<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\WooIndex\Builder;

use Carbon\CarbonImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Domain\WooIndex\Builder\Changefreq;
use Shared\Domain\WooIndex\Builder\DiWooXMLWriter;
use Shared\Domain\WooIndex\Builder\SitemapUrlBuilder;
use Shared\Domain\WooIndex\Exception\WooIndexInvalidArgumentException;
use Shared\Domain\WooIndex\Producer\DiWooDocument;
use Shared\Domain\WooIndex\Producer\DocumentHandeling;
use Shared\Domain\WooIndex\Producer\Url;
use Shared\Domain\WooIndex\Producer\UrlReference;
use Shared\Domain\WooIndex\Tooi\InformatieCategorie;
use Shared\Domain\WooIndex\Tooi\Ministerie;
use Shared\Domain\WooIndex\Tooi\SoortHandeling;
use Shared\Tests\Unit\UnitTestCase;
use Webmozart\Assert\Assert;

use function fopen;
use function rewind;
use function stream_get_contents;

final class SitemapUrlBuilderTest extends UnitTestCase
{
    /**
     * @var resource
     */
    private $stream;

    protected function setUp(): void
    {
        parent::setUp();

        $stream = fopen('php://temp', 'wb+');
        Assert::notFalse($stream);
        $this->stream = $stream;
    }

    #[DataProvider('getUrlData')]
    public function testCreatingSitemapIndex(Url $url): void
    {
        $writer = DiWooXMLWriter::toStream($this->stream);
        $writer->setIndent(true);

        $builder = new SitemapUrlBuilder();

        $builder->addUrl($writer, $url);

        $writer->flush();
        rewind($this->stream);

        $this->assertMatchesTextSnapshot(stream_get_contents($this->stream));
    }

    public function testCreatingSitemapIndexWithInvalidPriotityThrowsException(): void
    {
        $url = new Url(
            loc: 'https://example.com',
            lastmod: CarbonImmutable::parse('2021-01-01')->startOfDay(),
            diWooDocument: new DiWooDocument(
                creatiedatum: CarbonImmutable::parse('2021-05-10')->startOfDay(),
                publisher: Ministerie::mnre1025,
                officieleTitel: 'Officiële titel',
                informatieCategorie: InformatieCategorie::c_3baef532,
                documentHandeling: new DocumentHandeling(
                    soortHandeling: SoortHandeling::c_641ecd76,
                    atTime: CarbonImmutable::parse('2021-05-10')->endOfDay(),
                ),
                isPartOf: null,
                hasParts: null,
            ),
            changefreq: Changefreq::DAILY,
            priority: $priority = 1.5, // <--- this is invalid
        );

        $writer = DiWooXMLWriter::toStream($this->stream);

        $this->expectExceptionObject(WooIndexInvalidArgumentException::invalidPriority($priority));

        (new SitemapUrlBuilder())->addUrl($writer, $url);
    }

    /**
     * @return array<string,array{url:Url}>
     */
    public static function getUrlData(): array
    {
        return [
            'all fields filled' => [
                'url' => new Url(
                    loc: 'https://example.com',
                    lastmod: CarbonImmutable::parse('2021-01-01')->startOfDay(),
                    diWooDocument: new DiWooDocument(
                        creatiedatum: CarbonImmutable::parse('2021-05-10')->startOfDay(),
                        publisher: Ministerie::mnre1025,
                        officieleTitel: 'Officiële titel',
                        informatieCategorie: InformatieCategorie::c_3baef532,
                        documentHandeling: new DocumentHandeling(
                            soortHandeling: SoortHandeling::c_641ecd76,
                            atTime: CarbonImmutable::parse('2021-05-10')->endOfDay(),
                        ),
                        isPartOf: new UrlReference(
                            resource: 'https://example2.com',
                            officieleTitel: 'Officiële titel 2',
                        ),
                        hasParts: new ArrayCollection([
                            new UrlReference(
                                resource: 'https://example3.com',
                                officieleTitel: 'Officiële titel 3',
                            ),
                            new UrlReference(
                                resource: 'https://example4.com',
                                officieleTitel: 'Officiële titel 4',
                            ),
                        ]),
                    ),
                    changefreq: Changefreq::DAILY,
                    priority: 0.8,
                ),
            ],
            'optional fields empty' => [
                'url' => new Url(
                    loc: 'https://example.com',
                    lastmod: CarbonImmutable::parse('2021-01-01')->startOfDay(),
                    diWooDocument: new DiWooDocument(
                        creatiedatum: CarbonImmutable::parse('2021-05-10')->startOfDay(),
                        publisher: Ministerie::mnre1025,
                        officieleTitel: 'Officiële titel',
                        informatieCategorie: InformatieCategorie::c_3baef532,
                        documentHandeling: new DocumentHandeling(
                            soortHandeling: SoortHandeling::c_641ecd76,
                            atTime: CarbonImmutable::parse('2021-05-10')->endOfDay(),
                        ),
                        isPartOf: null,
                        hasParts: null,
                    ),
                ),
            ],
        ];
    }
}
