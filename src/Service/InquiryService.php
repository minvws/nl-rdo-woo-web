<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Inquiry;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Storage and retrieval of inquiry ids. This can be used for searching for preview documents with given inquiry ids.
 */
class InquiryService
{
    protected const INQUIRY_KEY = 'inquiries';

    public function __construct(protected RequestStack $requestStack)
    {
    }

    public function saveInquiry(Inquiry $inquiry): void
    {
        $id = (string) $inquiry->getId();

        $inquiryIds = $this->requestStack->getSession()->get(self::INQUIRY_KEY, []);
        if (! is_array($inquiryIds)) {
            $inquiryIds = [];
        }
        if (in_array($id, $inquiryIds)) {
            return;
        }

        $inquiryIds[] = $id;
        $this->requestStack->getSession()->set(self::INQUIRY_KEY, $inquiryIds);
    }

    public function clearInquiries(): void
    {
        $this->requestStack->getSession()->remove(self::INQUIRY_KEY);
    }

    /**
     * @return string[]
     */
    public function getInquiries(): array
    {
        /** @var string[] $ret */
        $ret = $this->requestStack->getSession()->get(self::INQUIRY_KEY, []);

        return is_array($ret) ? $ret : [];
    }
}
