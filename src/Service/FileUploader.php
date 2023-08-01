<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Dossier;
use App\Form\Dossier\DocumentUploadType;
use App\Message\ProcessDocumentMessage;
use App\Service\Storage\DocumentStorageService;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Handles a HTTP request which either contains a chunked upload or complete files. Once completed, a message will be dispatched
 * over the message bus to process the document async by a worker.
 */
class FileUploader
{
    protected MessageBusInterface $messageBus;
    protected FormFactoryInterface $formFactory;
    protected DocumentStorageService $documentStorage;

    /** @var array|string[] */
    protected array $mandatoryParams = [
        'dzchunkindex',
        'dztotalchunkcount',
        'dzchunkbyteoffset',
        'dzchunksize',
        'dzuuid',
    ];

    public function __construct(MessageBusInterface $messageBus, FormFactoryInterface $formFactory, DocumentStorageService $documentStorage)
    {
        $this->messageBus = $messageBus;
        $this->formFactory = $formFactory;
        $this->documentStorage = $documentStorage;
    }

    /**
     * Based on the request, either handle a chunked upload or a complete file upload.
     * Will return true when the complete file has been uploaded (or last chunk has been received), or false when there are more chunks
     * to be uploaded.
     */
    public function handleUpload(Request $request, Dossier $dossier): bool
    {
        if (! $request->request->has('dzchunkbyteoffset')) {
            return $this->handleCompleteFiles($request, $dossier);
        }

        return $this->handleChunkedUpload($request, $dossier);
    }

    protected function handleCompleteFiles(Request $request, Dossier $dossier): bool
    {
        // Check if document uploaded is valid (e.g. not too large). This is done through the validation of
        // a form (@TODO: we should use direct validation for this, not through a form)
        $form = $this->formFactory->create(DocumentUploadType::class, $dossier, ['csrf_protection' => false]);
        $form->handleRequest($request);
        if (! $form->isSubmitted() || ! $form->isValid()) {
            throw new \Exception('invalid data submitted');
        }

        // Dispatch message for each file uploaded to process the files
        /** @var UploadedFile[] $uploadedFiles */
        $uploadedFiles = $request->files->get('document_upload');
        //        $uploadedFiles = $form->get('upload')->getData();
        foreach ($uploadedFiles as $uploadedFile) {
            $remotePath = '/uploads/' . (string) $dossier->getId() . '/' . $uploadedFile->getClientOriginalName();
            if (! $this->documentStorage->store($uploadedFile, $remotePath)) {
                continue;
            }

            $message = new ProcessDocumentMessage(
                uuid: $dossier->getId(),
                remotePath: $remotePath,
                originalFilename: $uploadedFile->getClientOriginalName(),
                chunked: false,
            );

            $this->messageBus->dispatch($message);
        }

        return true;
    }

    /**
     * Handle a chunked upload request for a given dossier. Returns true when the complete file has been uploaded (last chunk received), or false
     * when there are more chunks to be uploaded.
     *
     * @throws \Exception
     */
    protected function handleChunkedUpload(Request $request, Dossier $dossier): bool
    {
        foreach ($this->mandatoryParams as $param) {
            if (! $request->request->has($param)) {
                throw new \Exception('Missing parameter: ' . $param);
            }
        }

        $uuid = strval($request->request->get('dzuuid'));
        $chunkIndex = intval($request->request->get('dzchunkindex'));
        $remoteChunkPath = '/uploads/chunks/' . $uuid;
        $remoteChunkFile = $remoteChunkPath . '/' . $chunkIndex;

        // Move the uploaded file to the remote chunk path
        /** @var array<string, UploadedFile> $upload */
        $upload = $request->files->get('document_upload');
        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $upload['upload'];
        $this->documentStorage->store($uploadedFile, $remoteChunkFile);

        // Check if all parts have been uploaded (ie: all chunks are found in the chunk upload dir)
        $chunkCount = intval($request->request->get('dztotalchunkcount'));
        $parts = $this->documentStorage->list($remoteChunkPath, '*');
        if (count($parts) < $chunkCount) {
            return false;
        }

        // Dispatch a message to process the uploaded file
        $message = new ProcessDocumentMessage(
            uuid: $dossier->getId(),
            remotePath: $remoteChunkPath,
            originalFilename: $uploadedFile->getClientOriginalName(),
            chunked: true,
            chunkUuid: $uuid,
            chunkCount: $chunkCount,
        );
        $this->messageBus->dispatch($message);

        return true;
    }
}
