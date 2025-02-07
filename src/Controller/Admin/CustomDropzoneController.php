<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Oneup\UploaderBundle\Controller\AbstractChunkedController;
use Oneup\UploaderBundle\Uploader\Response\EmptyResponse;
use Symfony\Component\HttpFoundation\File\Exception\UploadException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webmozart\Assert\Assert;

class CustomDropzoneController extends AbstractChunkedController
{
    public function upload(): JsonResponse
    {
        $request = $this->getRequest();
        $response = new EmptyResponse();
        $files = $this->getFiles($request->files);
        $statusCode = 200;

        $chunked = $request->request->get('chunkindex') !== null;

        foreach ($files as $file) {
            try {
                $chunked ?
                    $this->handleChunkedUpload($file, $response, $request) :
                    $this->handleUpload($file, $response, $request)
                ;
            } catch (UploadException $e) {
                $statusCode = 500; // Dropzone displays error if HTTP response is 40x or 50x
                $this->errorHandler->addException($response, $e);

                /** @var TranslatorInterface $translator */
                $translator = $this->container->get('translator');
                $message = $translator->trans($e->getMessage(), [], 'OneupUploaderBundle');
                $response = $this->createSupportedJsonResponse(['error' => $message]);
                $response->setStatusCode(400);

                return $response;
            }
        }

        return $this->createSupportedJsonResponse($response->assemble(), $statusCode);
    }

    /**
     * @return array{bool,string,int,string}
     */
    protected function parseChunkedRequest(Request $request): array
    {
        $totalChunkCount = $this->getAsInt($request, 'totalchunkcount');
        $index = $this->getAsInt($request, 'chunkindex');
        $last = ($index + 1) === $totalChunkCount;
        $uuid = $this->getAsString($request, 'uuid');

        /** @var UploadedFile $file */
        $file = $request->files->get('file');

        Assert::isInstanceOf($file, UploadedFile::class);

        $orig = $file->getClientOriginalName();

        return [$last, $uuid, $index, $orig];
    }

    private function getAsString(Request $request, string $key): string
    {
        $value = $request->get($key);

        if (! (is_string($value) || is_numeric($value))) {
            throw new \InvalidArgumentException(sprintf('The value of the key "%s" is not a string.', $key));
        }

        return (string) $value;
    }

    public function getAsInt(Request $request, string $key): int
    {
        $value = $request->get($key);

        if (! is_numeric($value)) {
            throw new \InvalidArgumentException(sprintf('The value of the key "%s" is not a number.', $key));
        }

        return (int) $value;
    }
}
