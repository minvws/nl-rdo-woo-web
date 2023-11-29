<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Dossier;
use App\Form\Dossier\DocumentUploadType;
use App\Message\ProcessDocumentMessage;
use App\Service\Storage\DocumentStorageService;
use Psr\Log\LoggerInterface;
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
    /** @var array|string[] */
    protected array $mandatoryParams = [
        'chunkindex',
        'totalchunkcount',
        'chunkbyteoffset',
        'uuid',
    ];

    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly FormFactoryInterface $formFactory,
        private readonly DocumentStorageService $documentStorage,
        private readonly LoggerInterface $logger,
        private readonly DocumentUploadQueue $uploadQueue,
    ) {
    }

    /**
     * Based on the request, either handle a chunked upload or a complete file upload.
     * Will return true when the complete file has been uploaded (or last chunk has been received), or false when there are more chunks
     * to be uploaded.
     */
    public function handleUpload(Request $request, Dossier $dossier): bool
    {
        if (! $request->request->has('chunkbyteoffset')) {
            return $this->handleCompleteFiles($request, $dossier);
        }

        return $this->handleChunkedUpload($request, $dossier);
    }

    protected function handleCompleteFiles(Request $request, Dossier $dossier): bool
    {
        $dossierId = $dossier->getId();
        if ($dossierId === null) {
            return false;
        }

        $form = $this->formFactory->create(DocumentUploadType::class, $dossier, ['csrf_protection' => false]);
        $form->handleRequest($request);
        if (! $form->isSubmitted() || ! $form->isValid()) {
            throw new \Exception('invalid data submitted');
        }

        // Dispatch message for each file uploaded to process the files
        /** @var UploadedFile[] $uploadedFiles */
        $uploadedFiles = $request->files->get('document_upload');
        foreach ($uploadedFiles as $uploadedFile) {
            $this->logger->info('uploaded document file', [
                'path' => $uploadedFile->getRealPath(),
                'original_file' => $uploadedFile->getClientOriginalName(),
                'size' => $uploadedFile->getSize(),
                'file_hash' => hash_file('sha256', $uploadedFile->getRealPath()),
            ]);

            $remotePath = '/uploads/' . (string) $dossier->getId() . '/' . $uploadedFile->getClientOriginalName();
            if (! $this->documentStorage->store($uploadedFile, $remotePath)) {
                continue;
            }

            $this->uploadQueue->add($dossier, $uploadedFile->getClientOriginalName());

            $message = new ProcessDocumentMessage(
                dossierUuid: $dossierId,
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
        $dossierId = $dossier->getId();
        if ($dossierId == null) {
            return false;
        }

        foreach ($this->mandatoryParams as $param) {
            if (! $request->request->has($param)) {
                throw new \Exception('Missing parameter: ' . $param);
            }
        }

        $uuid = strval($request->request->get('uuid'));
        $chunkIndex = intval($request->request->get('chunkindex'));
        $remoteChunkPath = '/uploads/chunks/' . $uuid;
        $remoteChunkFile = $remoteChunkPath . '/' . $chunkIndex;

        // Move the uploaded file to the remote chunk path
        /** @var array<string, UploadedFile> $upload */
        $upload = $request->files->get('document_upload');
        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $upload['upload'];

        $this->logger->info('uploaded document chunk file', [
            'path' => $uploadedFile->getRealPath(),
            'original_file' => $uploadedFile->getClientOriginalName(),
            'size' => $uploadedFile->getSize(),
            'chunk_index' => $chunkIndex,
            'file_hash' => hash_file('sha256', $uploadedFile->getRealPath()),
        ]);

        $this->documentStorage->store($uploadedFile, $remoteChunkFile);

        // Check if all parts have been uploaded (ie: all chunks are found in the chunk upload dir)
        $chunkCount = intval($request->request->get('totalchunkcount'));
        $parts = $this->documentStorage->list($remoteChunkPath, '*');
        if (count($parts) < $chunkCount) {
            return false;
        }

        $this->uploadQueue->add($dossier, $uploadedFile->getClientOriginalName());

        // Dispatch a message to process the uploaded file
        $message = new ProcessDocumentMessage(
            dossierUuid: $dossierId,
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
