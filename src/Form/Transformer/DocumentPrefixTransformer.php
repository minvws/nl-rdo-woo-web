<?php

declare(strict_types=1);

namespace App\Form\Transformer;

use App\Domain\Publication\Dossier\DocumentPrefix;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * @template-implements DataTransformerInterface<string, DocumentPrefix|null>
 */
class DocumentPrefixTransformer implements DataTransformerInterface
{
    public function __construct(protected EntityManagerInterface $doctrine)
    {
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
        if (! is_array($value) || ! isset($value['documentPrefix']) || ! $value['documentPrefix'] instanceof DocumentPrefix) {
            return '';
        }

        return $value['documentPrefix']->getPrefix();
    }
}
