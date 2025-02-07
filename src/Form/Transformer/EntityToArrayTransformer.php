<?php

declare(strict_types=1);

namespace App\Form\Transformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * @template-implements DataTransformerInterface<mixed, mixed>
 */
class EntityToArrayTransformer implements DataTransformerInterface
{
    public function transform(mixed $value): ArrayCollection
    {
        return new ArrayCollection([$value]);
    }

    public function reverseTransform(mixed $value): mixed
    {
        if ($value instanceof Collection) {
            return $value->isEmpty() ? null : $value->first();
        }

        return $value;
    }
}
