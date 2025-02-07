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
  uploadUuid: string | undefined;
}

type UploadResponse = UploadResponseSuccess | UploadResponseError;

interface UploadResponseSuccess extends Response {
  data: {
    groupId: string | null;
    mimeType: string;
    originalName: string;
    size: number;
    uploadUuid: string;
  };
}

enum UploadError {
  Techinal = 'error.technical',
  Unsafe = 'error.unsafe',
  WhiteList = 'error.whiteList',
}

interface UploadResponseError extends Response {
  error: UploadError;
}

interface OnUploadError {
  isTechnialError: boolean;
  isUnsafeError: boolean;
  isWhiteListError: boolean;
}

interface Options {
  endpoint?: string;
  file: File;
  onError?: (error: OnUploadError) => void;
  onProgress?: (progress: number) => void;
  onSuccess?: (uploadUuid: string | undefined) => void;
  payload?: Record<string, string>;
  uploadName?: string;
}

export const uploadFile = (options: Options) => {
  const {
    endpoint = '/balie/uploader',
    file,
    onError = () => {},
    onProgress = () => {},
    onSuccess = () => {},
    uploadName = 'file',
    payload = {},
  } = options;

  let cleanupUpload: () => void = () => {};

  const CHUNK_SIZE = 16 * 1024 * 1024; // 16 Mb
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
        uploadUuid: undefined,
      };

      store.set(id, chunk);
    }

    uploadNextChunk();
  };

  const uploadNextChunk = async () => {
    const chunks = getChunks();
    const nextChunkToUpload = chunks.find((chunk) => !chunk.isUploaded);

    if (!nextChunkToUpload) {
      onSuccess(chunks.find((chunk) => chunk.uploadUuid)?.uploadUuid);
      return;
    }

    cleanupUpload = uploadChunk(nextChunkToUpload);
  };

  const uploadChunk = (chunk: Chunk) => {
    const { request, sendRequest } = createChunkRequest(chunk);

    const abortController = new AbortController();

    request.upload.addEventListener(
      'progress',
      (event) => {
        const { loaded, total } = event;
        updateChunkProgress(chunk.id, loaded, total);
      },
      { signal: abortController.signal },
    );

    request.addEventListener(
      'load',
      () => {
        updateChunkUploadResult(
          chunk.id,
          isSuccessStatusCode(request.status),
          request.response,
        );
      },
      { signal: abortController.signal },
    );

    request.addEventListener('readystatechange', () => {}, {
      signal: abortController.signal,
    });

    sendRequest();

    return () => {
      abortController.abort();
      request.abort();
    };
  };

  const updateChunkUploadResult = (
    chunkId: number,
    isUploadSuccess: boolean,
    response: UploadResponse,
  ) => {
    const chunk = getChunk(chunkId);
    chunk.isUploaded = true;
    chunk.isUploadSuccess = isUploadSuccess;
    chunk.uploadUuid = isUploadSuccess
      ? (response as UploadResponseSuccess).data.uploadUuid
      : undefined;
    store.set(chunkId, chunk);

    cleanupUpload();

    const haveChunksFailed = getChunks().some(
      (chunkItem) => chunkItem.isUploadSuccess === false,
    );
    if (haveChunksFailed) {
      const { error } = response as UploadResponseError;
      onError({
        isTechnialError: error === UploadError.Techinal || !error,
        isUnsafeError: error === UploadError.Unsafe,
        isWhiteListError: error === UploadError.WhiteList,
      });
      return;
    }

    uploadNextChunk();
  };

  const updateChunkProgress = (
    chunkId: number,
    bytesSent: number,
    bytesToSend: number,
  ) => {
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
    const [totalBytesSent, totalBytesToSend] = getChunks().reduce(
      ([cummulativeBytesSent, cummulativeBytesToSend], chunk) => [
        cummulativeBytesSent + chunk.bytesSent,
        cummulativeBytesToSend + chunk.bytesToSend,
      ],
      [0, 0],
    );

    return Math.round((totalBytesSent / totalBytesToSend) * 100);
  };

  const createChunkRequest = (
    chunk: Chunk,
  ): { request: XMLHttpRequest; sendRequest: () => void } => {
    const formData = new FormData();
    formData.append(uploadName, chunk.content, file.name);
    formData.append('chunkindex', chunk.index.toString());
    formData.append('totalchunkcount', chunk.numberOfChunks.toString());
    formData.append('uuid', fileId); // each chunk of a file should have the same uuid

    Object.entries(payload).forEach(([key, value]) => {
      formData.append(key, value);
    });

    const request = new XMLHttpRequest();
    request.open('POST', endpoint, true);
    request.responseType = 'json';
    request.setRequestHeader('Accept', 'application/json');

    return {
      request,
      sendRequest: () => request.send(formData),
    };
  };

  const getChunk = (id: number) => store.get(id) as Chunk;
  const getChunks = () => Array.from(store.values());

  intialize();

  return cleanupUpload;
};
