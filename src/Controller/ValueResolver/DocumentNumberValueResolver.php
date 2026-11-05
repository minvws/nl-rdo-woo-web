<?php

declare(strict_types=1);

namespace Shared\Controller\ValueResolver;

use Shared\ValueObject\DocumentNumber;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

#[AutoconfigureTag('controller.argument_value_resolver', ['priority' => 150])]
readonly class DocumentNumberValueResolver implements ValueResolverInterface
{
    /**
     * @return iterable<DocumentNumber>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ($argument->getType() !== DocumentNumber::class) {
            return [];
        }

        $documentNumber = $request->attributes->getString($argument->getName());
        if ($documentNumber === '') {
            return [];
        }

        return [DocumentNumber::fromUnnormalizedString($documentNumber)];
    }
}
