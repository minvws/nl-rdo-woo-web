<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\WooIndex\Builder;

use App\Domain\WooIndex\Builder\SitemapUrlBuilder;
use App\Domain\WooIndex\Changefreq;
use App\Domain\WooIndex\DiWooInvalidArgumentException;
use App\Domain\WooIndex\Producer\DiWooDocument;
use App\Domain\WooIndex\Producer\DocumentHandeling;
use App\Domain\WooIndex\Producer\Url;
use App\Domain\WooIndex\Tooi\InformatieCategorie;
use App\Domain\WooIndex\Tooi\Ministerie;
use App\Domain\WooIndex\Tooi\SoortHandeling;
use App\Domain\WooIndex\WriterFactory\DiWooXMLWriter;
use App\Tests\Unit\UnitTestCase;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;

final class SitemapUrlBuilderTest extends UnitTestCase
{
    #[DataProvider('getUrlData')]
    public function testCreatingSitemapIndex(Url $url): void
    {
        $writer = new DiWooXMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);

        $builder = new SitemapUrlBuilder();

        $builder->addUrl($writer, $url);

        $this->assertMatchesTextSnapshot($writer->flush());
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
            ),
            changefreq: Changefreq::DAILY,
            priority: $priority = 1.5, // <--- this is invalid
        );

        $writer = new DiWooXMLWriter();
        $writer->openMemory();

        $this->expectExceptionObject(DiWooInvalidArgumentException::invalidPriority($priority));

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
                    ),
                ),
            ],
        ];
    }
}
