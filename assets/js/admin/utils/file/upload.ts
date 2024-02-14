import { isSuccessStatusCode } from '@js/admin/utils';
import { uniqueId } from '@utils';

export interface Chunk {
  byteOffset: number;
  bytesSent: number;
  bytesToSend: number;
  content: Blob;
  id: number;
  index: number;
  isUploaded: boolean;
  isUploadSuccess: boolean | null;
  numberOfChunks: number;
  size: number;
  uploadFinished: boolean;
}

interface Options {
  chunkSize?: number;
  endpoint: string;
  file: File;
  inputName: string;
  onError?: () => void;
  onProgress?: (progress: number) => void;
  onSuccess?: () => void;
}

export const uploadFile = (options: Options) => {
  const {
    endpoint,
    file,
    inputName,
    onError = () => {},
    onProgress = () => {},
    onSuccess = () => {},
  } = options;

  const CHUNK_SIZE = 16 * 1024 * 1024;
  const cleanupFunctions: (() => void)[] = [];
  const fileId = uniqueId('file', 32);
  const store = new Map<number, Chunk>();
  const totalFileSize = file.size;
  let uploadProgress = 0;

  const intialize = () => {
    const numberOfChunks = Math.ceil(totalFileSize / CHUNK_SIZE);

    for (let chunkIndex = 0; chunkIndex < numberOfChunks; chunkIndex += 1) {
      const start = chunkIndex * CHUNK_SIZE;
      const end = (chunkIndex + 1) * CHUNK_SIZE;
      const content = file.slice(start, end);
      const id = chunkIndex;

      const chunk: Chunk = {
        byteOffset: start,
        bytesSent: 0,
        bytesToSend: content.size,
        content,
        id,
        index: chunkIndex,
        isUploaded: false,
        isUploadSuccess: null,
        numberOfChunks,
        size: content.size,
        uploadFinished: false,
      };

      store.set(id, chunk);

      cleanupFunctions.push(uploadChunk(chunk, id));
    }
  };

  const uploadChunk = (chunk: Chunk, chunkId: number) => {
    const { request, sendRequest } = createChunkRequest(chunk);

    const abortController = new AbortController();

    request.upload.addEventListener('progress', (event) => {
      const { loaded, total } = event;
      updateChunkProgress(chunkId, loaded, total);
    }, { signal: abortController.signal });

    request.addEventListener('load', () => {
      updateChunkUploadResult(chunkId, isSuccessStatusCode(request.status));
    }, { signal: abortController.signal });

    sendRequest();

    return () => {
      abortController.abort();
      request.abort();
    };
  };

  const updateChunkUploadResult = (chunkId: number, isUploadSuccess: boolean) => {
    const chunk = getChunk(chunkId);
    chunk.isUploaded = true;
    chunk.isUploadSuccess = isUploadSuccess;
    store.set(chunkId, chunk);

    const areAllChunksUploaded = getChunks().every((chunkItem) => chunkItem.isUploaded);
    if (!areAllChunksUploaded) {
      return;
    }

    const haveChunksFailed = getChunks().some((chunkItem) => chunkItem.isUploadSuccess === false);
    if (haveChunksFailed) {
      cleanup();
      onError();
      return;
    }

    onSuccess();
  };

  const updateChunkProgress = (chunkId: number, bytesSent: number, bytesToSend: number) => {
    const chunk = getChunk(chunkId);
    chunk.bytesSent = bytesSent;
    chunk.bytesToSend = bytesToSend;
    store.set(chunkId, chunk);

    const fileProgress = getFileProgress();
    if (uploadProgress === fileProgress) {
      return;
    }

    uploadProgress = fileProgress;
    onProgress(fileProgress);
  };

  const getFileProgress = () => {
    const [totalBytesSent, totalBytesToSend] = getChunks().reduce(([cummulativeBytesSent, cummulativeBytesToSend], chunk) => [
      cummulativeBytesSent + chunk.bytesSent, cummulativeBytesToSend + chunk.bytesToSend], [0, 0]);

    return Math.round((totalBytesSent / totalBytesToSend) * 100);
  };

  const createChunkRequest = (chunk: Chunk): { request: XMLHttpRequest, sendRequest: () => void } => {
    const formData = new FormData();
    formData.append(inputName, chunk.content, file.name);
    formData.append('chunkbyteoffset', chunk.byteOffset.toString());
    formData.append('chunkindex', chunk.index.toString());
    formData.append('totalchunkcount', chunk.numberOfChunks.toString());
    formData.append('uuid', fileId); // each chunk of a file should have the same uuid

    const request = new XMLHttpRequest();
    request.open('POST', endpoint, true);

    return {
      request,
      sendRequest: () => request.send(formData),
    };
  };

  const getChunk = (id: number) => store.get(id) as Chunk;
  const getChunks = () => Array.from(store.values());

  const cleanup = () => {
    cleanupFunctions.forEach((cleanupFunction) => cleanupFunction());
  };

  intialize();

  return cleanup;
};
