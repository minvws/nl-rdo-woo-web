<?php

declare(strict_types=1);

namespace App\Domain\Department\Markdown;

use League\CommonMark\ConverterInterface;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Environment\EnvironmentInterface;
use League\CommonMark\Exception\CommonMarkException;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\DefaultAttributes\DefaultAttributesExtension;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Output\RenderedContentInterface;
use League\CommonMark\Parser\MarkdownParser;
use League\CommonMark\Parser\MarkdownParserInterface;
use League\CommonMark\Renderer\DocumentRendererInterface;
use League\CommonMark\Renderer\HtmlRenderer;
use League\CommonMark\Util\HtmlFilter;
use League\Config\Exception\ConfigurationExceptionInterface;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
final class MarkdownConverter implements ConverterInterface
{
    private EnvironmentInterface&EnvironmentBuilderInterface $environment;
    private MarkdownParserInterface $parser;
    private DocumentRendererInterface $renderer;

    /**
     * @throws CommonMarkException
     * @throws ConfigurationExceptionInterface
     */
    public function convert(string $input): RenderedContentInterface
    {
        $documentAST = $this->getParser()->parse($input);

        return $this->getRenderer()->renderDocument($documentAST);
    }

    public function getParser(): MarkdownParserInterface
    {
        if (isset($this->parser)) {
            return $this->parser;
        }

        return $this->parser = new MarkdownParser($this->getEnvironment());
    }

    public function getRenderer(): DocumentRendererInterface
    {
        if (isset($this->renderer)) {
            return $this->renderer;
        }

        return $this->renderer = new HtmlRenderer($this->getEnvironment());
    }

    private function getEnvironment(): EnvironmentInterface&EnvironmentBuilderInterface
    {
        if (isset($this->environment)) {
            return $this->environment;
        }

        $this->environment = new Environment([
            'html_input' => HtmlFilter::STRIP,
            'allow_unsafe_links' => false,
            'max_nesting_level' => 10,
            'max_delimiters_per_line' => 1_000,
            'default_attributes' => [
                Link::class => [
                    'target' => '_blank',
                ],
            ],
        ]);

        $this->environment->addExtension(new CustomExtension());
        $this->environment->addExtension(new ExternalLinkExtension());
        $this->environment->addExtension(new DefaultAttributesExtension());

        return $this->environment;
    }
}
