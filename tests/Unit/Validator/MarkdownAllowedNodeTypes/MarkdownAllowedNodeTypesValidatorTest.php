<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Validator\MarkdownAllowedNodeTypes;

use League\CommonMark\Extension\CommonMark\Node\Inline\Emphasis;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\CommonMark\Node\Inline\Strong;
use League\CommonMark\Node\Block\Document;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Node\Inline\Newline;
use League\CommonMark\Node\Inline\Text;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Domain\Content\Markdown\MarkdownConverter;
use Shared\Tests\Unit\UnitTestCase;
use Shared\Validator\MarkdownAllowedNodeTypes\MarkdownAllowedNodeTypes;
use Shared\Validator\MarkdownAllowedNodeTypes\MarkdownAllowedNodeTypesValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

final class MarkdownAllowedNodeTypesValidatorTest extends UnitTestCase
{
    /** @var list<class-string> */
    private const array ALLOWED_NODE_TYPES = [
        Document::class,
        Paragraph::class,
        Text::class,
        Newline::class,
        Emphasis::class,
        Strong::class,
        Link::class,
    ];

    public function testNoViolationForNullOrEmptyValue(): void
    {
        $context = Mockery::mock(ExecutionContextInterface::class);
        $context->shouldNotReceive('buildViolation');

        $validator = new MarkdownAllowedNodeTypesValidator(new MarkdownConverter());
        $validator->initialize($context);
        $constraint = new MarkdownAllowedNodeTypes(self::ALLOWED_NODE_TYPES);

        $validator->validate(null, $constraint);
        $validator->validate('', $constraint);
    }

    public function testNoViolationForAllowedNodeTypes(): void
    {
        $context = Mockery::mock(ExecutionContextInterface::class);
        $context->shouldNotReceive('buildViolation');

        $validator = new MarkdownAllowedNodeTypesValidator(new MarkdownConverter());
        $validator->initialize($context);

        $validator->validate(
            <<<'MARKDOWN'
This is *emphasis*, **strong**, and [a link](https://example.org).  
This is a new line.
MARKDOWN,
            new MarkdownAllowedNodeTypes(self::ALLOWED_NODE_TYPES),
        );
    }

    #[DataProvider('disallowedNodeTypeProvider')]
    public function testViolationForDisallowedNodeType(
        string $markdown,
        string $expectedType,
    ): void {
        $builder = Mockery::mock(ConstraintViolationBuilderInterface::class);
        $builder->expects('setParameter')
            ->once()
            ->with('{{ type }}', $expectedType)
            ->andReturn($builder);
        $builder->expects('addViolation')->once();

        $context = Mockery::mock(ExecutionContextInterface::class);
        $context->expects('buildViolation')
            ->once()
            ->with('The Markdown contains an element that is not allowed ({{ type }}).')
            ->andReturn($builder);

        $validator = new MarkdownAllowedNodeTypesValidator(new MarkdownConverter());
        $validator->initialize($context);

        $validator->validate($markdown, new MarkdownAllowedNodeTypes(self::ALLOWED_NODE_TYPES));
    }

    public function testCustomMessageIsUsedForViolation(): void
    {
        $builder = Mockery::mock(ConstraintViolationBuilderInterface::class);
        $builder->expects('setParameter')
            ->once()
            ->with('{{ type }}', 'Heading')
            ->andReturn($builder);
        $builder->expects('addViolation')->once();

        $context = Mockery::mock(ExecutionContextInterface::class);
        $context->expects('buildViolation')
            ->once()
            ->with('Markdown node is not allowed: {{ type }}.')
            ->andReturn($builder);

        $validator = new MarkdownAllowedNodeTypesValidator(new MarkdownConverter());
        $validator->initialize($context);

        $validator->validate(
            '# Heading',
            new MarkdownAllowedNodeTypes(self::ALLOWED_NODE_TYPES, 'Markdown node is not allowed: {{ type }}.'),
        );
    }

    public function testThrowsUnexpectedTypeExceptionForWrongConstraint(): void
    {
        $validator = new MarkdownAllowedNodeTypesValidator(new MarkdownConverter());
        $validator->initialize(Mockery::mock(ExecutionContextInterface::class));

        $this->expectException(UnexpectedTypeException::class);

        $validator->validate('Markdown', Mockery::mock(Constraint::class));
    }

    public function testThrowsUnexpectedValueExceptionForNonStringValue(): void
    {
        $validator = new MarkdownAllowedNodeTypesValidator(new MarkdownConverter());
        $validator->initialize(Mockery::mock(ExecutionContextInterface::class));

        $this->expectException(UnexpectedValueException::class);

        $validator->validate(123, new MarkdownAllowedNodeTypes(self::ALLOWED_NODE_TYPES));
    }

    /**
     * @return array<string, array{markdown: string, expectedType: string}>
     */
    public static function disallowedNodeTypeProvider(): array
    {
        return [
            'heading' => [
                'markdown' => '# Heading',
                'expectedType' => 'Heading',
            ],
            'setext heading' => [
                'markdown' => <<<'MARKDOWN'
Full text
=========
MARKDOWN,
                'expectedType' => 'Heading',
            ],
        ];
    }
}
