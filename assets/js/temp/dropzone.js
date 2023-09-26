const {Dropzone} = require("dropzone");

import "dropzone/src/basic.scss";

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
    dictDefaultMessage: "<span class=''>Tip: je kan meerdere documenten tegelijkertijd uploaden. Sleep je hele selectie (of een zip-bestand) naar dit venster.</span><span class='text-sea-blue'><strong>Bestand selecteren</strong></span><span>ZIP, 7Z of PDFs (Max 10 GB)</span>",
    acceptedFiles: "application/pdf,application/x-pdf,.zip",
};

Dropzone.discover();

