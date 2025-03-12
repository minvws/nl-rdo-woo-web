<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\WooIndex\Builder;

use App\Domain\WooIndex\Builder\SitemapBuilder;
use App\Domain\WooIndex\WriterFactory\MemoryWriterFactory;
use App\Tests\Unit\UnitTestCase;

final class SitemapBuilderTest extends UnitTestCase
{
    public function testCreatingSitemapIndex(): void
    {
        $builder = new SitemapBuilder(new MemoryWriterFactory());
        $builder->setXMLWriterConfigurator(function (\XMLWriter $writer): void {
            $writer->setIndent(true);
        });

        $writer = $builder->open('sitemap.xml');

        $writer->writeElement('test', 'some placeholder content');

        $builder->close($writer);

        $this->assertMatchesTextSnapshot($writer->flush());
    }
}
