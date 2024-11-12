import { uniqueId } from '@utils';

export interface Chunk {
  byteOffset: number;
  bytesSent: number;
  bytesToSend: number;
  content: Blob;
  fileId: string;
  fileName: string;
  id: number;
  index: number;
  isUploaded: boolean;
  isUploadSuccess: boolean | null;
  numberOfChunks: number;
  size: number;
  uploadFinished: boolean;
}

export const chunksStore = (file: File, onFileUploadedFunction: () => void) => {
  const MAX_CHUNK_SIZE = 16 * 1024 * 1024; // 16MB chunk size
  const fileId = uniqueId('file', 32);
  const totalSize = file.size;
  const store = new Map<number, Chunk>();

  const intialize = () => {
    const numberOfChunks = Math.ceil(totalSize / MAX_CHUNK_SIZE);

    for (let chunkIndex = 0; chunkIndex < numberOfChunks; chunkIndex += 1) {
      const start = chunkIndex * MAX_CHUNK_SIZE;
      const end = (chunkIndex + 1) * MAX_CHUNK_SIZE;
      const content = file.slice(start, end);
      const id = chunkIndex;

      const chunk: Chunk = {
        byteOffset: start,
        bytesSent: 0,
        bytesToSend: content.size,
        content,
        fileId,
        fileName: file.name,
        id,
        index: chunkIndex,
        isUploaded: false,
        isUploadSuccess: null,
        numberOfChunks,
        size: content.size,
        uploadFinished: false,
      };

      store.set(id, chunk);
    }
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

  const updateChunkUploadResult = (
    chunkId: number,
    isUploadSuccess: boolean,
  ) => {
    const chunk = getChunk(chunkId);
    chunk.isUploaded = true;
    chunk.isUploadSuccess = isUploadSuccess;
    store.set(chunkId, chunk);

    const areAllChunksUploaded = getChunks().every(
      (chunkItem) => chunkItem.isUploaded,
    );
    if (!areAllChunksUploaded) {
      return;
    }

    onFileUploadedFunction();
  };

  const getChunks = () => Array.from(store.values());
  const getChunk = (id: number) => store.get(id) as Chunk;

  intialize();

  return {
    getChunks,
    getFileProgress,
    updateChunkProgress,
    updateChunkUploadResult,
  };
};
