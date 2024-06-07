<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Inquiry;
use PHPUnit\Framework\TestCase;

final class InquiryTest extends TestCase
{
    public function testGetDownloadFilePrefix(): void
    {
        $inquiry = new Inquiry();
        $inquiry->setCasenr('tst-123');

        $translatableMessage = $inquiry->getDownloadFilePrefix();

        self::assertEquals(
            'admin.dossiers.inquiries.number',
            $translatableMessage->getMessage(),
        );

        self::assertEquals(
            [
                'caseNr' => 'tst-123',
            ],
            $translatableMessage->getPlaceholders(),
        );
    }
}
