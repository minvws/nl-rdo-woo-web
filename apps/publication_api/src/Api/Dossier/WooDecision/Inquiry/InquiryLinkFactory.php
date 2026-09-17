<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision\Inquiry;

use PublicationApi\Domain\OpenApi\Links\Link;
use Shared\Controller\Public\Dossier\WooDecision\InquiryController;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use Shared\Domain\Publication\PublicUrlGenerator;

readonly class InquiryLinkFactory
{
    public function __construct(
        private PublicUrlGenerator $publicUrlGenerator,
    ) {
    }

    public function fromInquiry(Inquiry $inquiry): Link
    {
        return new Link(
            $this->publicUrlGenerator->buildUrlFromRoute(InquiryController::ROUTE_NAME_INQUIRY_DETAIL, ['token' => $inquiry->getToken()]),
            $inquiry->getInquiryNumber(),
        );
    }
}
