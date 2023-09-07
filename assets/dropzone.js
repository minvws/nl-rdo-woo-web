const {Dropzone} = require("dropzone");

import "../node_modules/dropzone/src/dropzone.scss";

Dropzone.options.uploadform = {
    paramName: "document_upload[upload]",
    autoProcessQueue: true,
    uploadMultiple: false,
    addRemoveLinks: true,
    maxFiles: 2000,
    maxFilesize: 4096, // MB
    chunking: true,
    parallelChunkUploads: true,
    retryChunks: true,
    retryChunksLimit: 3,
    chunkSize:  16 * 1024 * 1024, // Bytes
    timeout: 0,
    dictDefaultMessage: "Drop PDF or ZIP files here to upload your documents",
    acceptedFiles: "application/pdf,application/x-pdf,.zip",
};

Dropzone.discover();

