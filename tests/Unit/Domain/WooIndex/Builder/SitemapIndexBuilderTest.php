<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\WooIndex\Builder;

use App\Domain\WooIndex\Builder\SitemapIndexBuilder;
use App\Domain\WooIndex\WriterFactory\MemoryWriterFactory;
use App\Tests\Unit\UnitTestCase;

final class SitemapIndexBuilderTest extends UnitTestCase
{
    public function testCreatingSitemapIndex(): void
    {
        $builder = new SitemapIndexBuilder(new MemoryWriterFactory());
        $builder->setXMLWriterConfigurator(function (\XMLWriter $writer): void {
            $writer->setIndent(true);
        });

        $writer = $builder->open('sitemap-index.xml');
        $builder->addSitemap($writer, 'https://example.com/sitemap1.xml');
        $builder->addSitemap($writer, 'https://example.com/sitemap1.xml');
        $builder->addSitemap($writer, 'https://example.com/sitemap1.xml');
        $builder->close($writer);

        $this->assertMatchesTextSnapshot($writer->flush());
    }
}
