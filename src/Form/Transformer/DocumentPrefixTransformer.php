<?php

declare(strict_types=1);

namespace App\Form\Transformer;

use App\Entity\DocumentPrefix;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * @template-implements DataTransformerInterface<string, DocumentPrefix|null>
 */
class DocumentPrefixTransformer implements DataTransformerInterface
{
    protected EntityManagerInterface $doctrine;

    public function __construct(EntityManagerInterface $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function transform(mixed $value): ?DocumentPrefix
    {
        if (is_null($value)) {
            return $value;
        }

        return $this->doctrine->getRepository(DocumentPrefix::class)->findOneBy(['prefix' => $value]);
    }

    public function reverseTransform(mixed $value): string
    {
        if (! $value instanceof DocumentPrefix) {
            return '';
        }

        return $value->getPrefix();
    }
}
