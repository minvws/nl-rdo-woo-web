<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload;

use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use Psr\Http\Message\StreamInterface;
use Shared\Domain\Upload\StreamUpload;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Unit\UnitTestCase;
use Stringable;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\Uid\UuidV6;

class StreamUploadTest extends UnitTestCase
{
    public function testStreamUpload(): void
    {
        $upload = new StreamUpload(
            fileName: 'test.pdf',
            stream: $this->getMockedStream(),
            groupId: UploadGroupId::WOO_DECISION_DOCUMENTS,
            additionalParameters: ['foo' => 'bar'],
            uploadId: 'foobar',
        );

        $this->assertMatchesObjectSnapshot($upload);
    }

    public function testStreamUploadWithStringableName(): void
    {
        $fileName = new class implements Stringable {
            public function __toString(): string
            {
                return 'test.pdf';
            }
        };

        $upload = new StreamUpload(
            fileName: $fileName,
            stream: $this->getMockedStream(),
            groupId: UploadGroupId::WOO_DECISION_DOCUMENTS,
            additionalParameters: ['foo' => 'bar'],
            uploadId: 'upload-id',
        );

        $this->assertMatchesObjectSnapshot($upload);
    }

    public function testAutoSetsUploadIdWhenNotDefined(): void
    {
        $upload = new StreamUpload(
            fileName: 'test.pdf',
            stream: $this->getMockedStream(),
            groupId: UploadGroupId::WOO_DECISION_DOCUMENTS,
            additionalParameters: new InputBag(['foo' => 'bar']),
        );

        $this->assertTrue(UuidV6::isValid($upload->uploadId), 'uploadId should be a valid UUIDv6');
    }

    public function testFileNameMustHaveExtension(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException('fileName must have an extension'));

        new StreamUpload(
            fileName: 'test',
            stream: $this->getMockedStream(),
            groupId: UploadGroupId::WOO_DECISION_DOCUMENTS,
            additionalParameters: ['foo' => 'bar'],
        );
    }

    public function testAdditionalParametersCanBeInitializedUsingAnArray(): void
    {
        $upload = new StreamUpload(
            fileName: 'test.pdf',
            stream: $this->getMockedStream(),
            groupId: UploadGroupId::WOO_DECISION_DOCUMENTS,
            additionalParameters: ['foo' => 'bar'],
        );

        $this->assertInstanceOf(InputBag::class, $upload->additionalParameters);
        $this->assertEquals('bar', $upload->additionalParameters->get('foo'));
    }

    private function getMockedStream(): StreamInterface&MockInterface
    {
        $stream = Mockery::mock(StreamInterface::class);
        $stream->expects('getSize')->zeroOrMoreTimes()->andReturn(1337);
        $stream->expects('isSeekable')->zeroOrMoreTimes()->andReturn(true);
        $stream->expects('isWritable')->zeroOrMoreTimes()->andReturn(false);
        $stream->expects('isReadable')->zeroOrMoreTimes()->andReturn(true);
        $stream->expects('getContents')->zeroOrMoreTimes()->andReturn('stream contents');
        $stream->expects('getMetadata')->zeroOrMoreTimes()->andReturn(['uri' => 'php://temp']);

        return $stream;
    }
}
