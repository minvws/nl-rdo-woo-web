<?php

declare(strict_types=1);

namespace Admin\Tests\Unit\Api\Admin\NoticeNotPublic;

use Admin\Api\Admin\ApiDossierAccessChecker;
use Admin\Api\Admin\NoticeNotPublic\NoticeNotPublicInput;
use Admin\Api\Admin\NoticeNotPublic\NoticeNotPublicProcessor;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Validator\Exception\ValidationException;
use Mockery;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\PlainDate;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class NoticeNotPublicProcessorTest extends UnitTestCase
{
    public function testProcessThrowsValidationExceptionWhenHandlerThrowsValidationFailedException(): void
    {
        $input = new NoticeNotPublicInput();
        $input->documentName = $this->getFaker()->word();
        $input->formalDate = PlainDate::create('2024-01-01');
        $input->grounds = [$this->getFaker()->word()];
        $input->explanation = $this->getFaker()->sentence();

        $validationFailedException = new ValidationFailedException($this->getFaker()->word(), new ConstraintViolationList());
        $handlerFailedException = new HandlerFailedException(new Envelope(new stdClass()), ['handler' => $validationFailedException]);

        $messageBus = Mockery::mock(MessageBusInterface::class);
        $messageBus->expects('dispatch')
            ->andThrow($handlerFailedException);

        $dossierAccessChecker = Mockery::mock(ApiDossierAccessChecker::class);
        $dossierAccessChecker->expects('ensureUserIsAllowedToUpdateDossier');

        $processor = new NoticeNotPublicProcessor($messageBus, $dossierAccessChecker);

        $this->expectException(ValidationException::class);
        $processor->process($input, new Post(), ['dossierId' => $this->getFaker()->uuid()]);
    }
}
