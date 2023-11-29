import { hideElement, showElement } from '@utils';
import { FilesArea } from '../files-area';
import { chunksStore as createChunksStore, Chunk } from './chunks-store';

export class AutoUploadFilesArea extends FilesArea {
  addElementFunctionality(listItemElement: HTMLElement, fileId: string, file: File) {
    const functionalities = [
      this.addRemoveElementFunctionality(listItemElement, fileId),
      this.addUploadFunctionality(listItemElement, fileId, file),
    ];

    return () => {
      functionalities.forEach((cleanupFunction) => cleanupFunction());
    };
  }

  addUploadFunctionality(listItemElement: HTMLElement, fileId: string, file: File) {
    this.showProgress(listItemElement);
    this.makeElementTransitionable(listItemElement);

    const chunkCleanupFunctions: (() => void)[] = [];
    const cleanup = () => {
      chunkCleanupFunctions.forEach((cleanupFunction) => cleanupFunction());
    };

    const chunksStore = createChunksStore(file, () => {
      const cleanupFunction = this.onFileResult(listItemElement, fileId, true, '');
      chunkCleanupFunctions.push(cleanupFunction);
    });

    chunksStore.getChunks().forEach((chunk) => {
      const cleanupFunction = this.uploadChunk(
        chunk,

        (bytesOfChunkSent: number, bytesOfChunkToSend: number) => {
          chunksStore.updateChunkProgress(chunk.id, bytesOfChunkSent, bytesOfChunkToSend);
          this.updateProgress(listItemElement, chunksStore.getFileProgress());
        },

        (isUploadSuccess: boolean) => {
          if (!isUploadSuccess) {
            cleanup();
            const errorMessage = this.createErrorMessageForFailedChunk(chunk);
            this.onFileResult(listItemElement, fileId, false, errorMessage);
            return;
          }

          chunksStore.updateChunkUploadResult(chunk.id, isUploadSuccess);
        },
      );

      chunkCleanupFunctions.push(cleanupFunction);
    });

    return cleanup;
  }

  createErrorMessageForFailedChunk(chunk: Chunk) {
    const { index, numberOfChunks } = chunk;
    return `something went wrong while uploading chunk ${index + 1}/${numberOfChunks}`;
  }

  makeElementTransitionable(listItemElement: HTMLElement) {
    listItemElement.classList.add('transition-all', 'duration-500', 'overflow-hidden');
    const height = listItemElement.offsetHeight;
    listItemElement.style.height = `${height}px`;
  }

  uploadChunk(
    chunk: Chunk,
    onChunkProgressFunction: (loaded: number, total: number) => void,
    onChunkUploadedFunction: (isUploadSuccess: boolean) => void,
  ) {
    const { request, sendRequest } = this.createChunkRequest(chunk);

    const abortController = new AbortController();

    request.upload.addEventListener('progress', (event) => {
      const { loaded, total } = event;
      onChunkProgressFunction(loaded, total);
    }, { signal: abortController.signal });

    request.addEventListener('load', () => {
      const isUploadSuccess = request.status >= 200 && request.status < 300;
      onChunkUploadedFunction(isUploadSuccess);
    }, { signal: abortController.signal });

    sendRequest();

    return () => {
      abortController.abort();
      request.abort();
    };
  }

  createChunkRequest(chunk: Chunk) {
    const { endpoint = '', name = '' } = this.areaElement?.dataset || {};

    const formData = new FormData();
    formData.append(name, chunk.content, chunk.fileName);
    formData.append('chunkbyteoffset', chunk.byteOffset.toString());
    formData.append('chunkindex', chunk.index.toString());
    formData.append('totalchunkcount', chunk.numberOfChunks.toString());
    formData.append('uuid', chunk.fileId); // each chunk of a file should have the same uuid

    const request = new XMLHttpRequest();
    request.open('POST', endpoint, true);

    return {
      request,
      sendRequest: () => request.send(formData),
    };
  }

  onFileResult(listItemElement: HTMLElement, fileId: string, isUploadSuccess: boolean, errorMessage: string) {
    this.hideProgress(listItemElement);

    this.displayUploadResultIcon(listItemElement, isUploadSuccess);

    let timeoutId: NodeJS.Timeout | null = null;
    if (this.canUploadMultipleFiles) {
      // If only one file can be uploaded, we don't want to remove it from the list when uploading is finished.

      listItemElement.classList.add('delay-[3000ms]');
      listItemElement.style.height = '0';

      timeoutId = setTimeout(() => {
        this.removeFile(fileId);
      }, 3000 + 500 + 10); // delay + transition duration + some extra
    }

    if (!isUploadSuccess) {
      const { file } = this.getFromStore(fileId);
      this.onFileUploadFailedFunction(file, errorMessage);
    }

    return () => {
      if (timeoutId) {
        clearTimeout(timeoutId);
      }
    };
  }

  updateProgress(listItemElement: HTMLElement, progress: number) {
    const progressElement = listItemElement.querySelector(`.${this.progressClass}`) as HTMLProgressElement;
    const { value: currentProgress } = progressElement;

    if (progress === currentProgress) {
      return;
    }

    if (progress === 100) {
      this.hideRemoveButton(listItemElement);
      this.showSpinner(listItemElement);
    }

    progressElement.value = progress;
  }

  displayUploadResultIcon(listItemElement: HTMLElement, isUploadSuccess: boolean) {
    this.hideRemoveButton(listItemElement);
    this.hideSpinner(listItemElement);

    const elementClass = isUploadSuccess ? this.uploadSuccessClass : this.uploadFailedClass;
    const iconElement = listItemElement.querySelector(`.${elementClass}`);
    showElement(iconElement);
  }

  hideProgress(listItemElement: HTMLElement) {
    hideElement(this.#getProgressWrapperElement(listItemElement));
  }

  showProgress(listItemElement: HTMLElement) {
    showElement(this.#getProgressWrapperElement(listItemElement));
  }

  hideSpinner(listItemElement: HTMLElement) {
    hideElement(this.#getSpinnerElement(listItemElement));
  }

  showSpinner(listItemElement: HTMLElement) {
    showElement(this.#getSpinnerElement(listItemElement));
  }

  hideRemoveButton(listItemElement: HTMLElement) {
    const removeButton = this.getRemoveButton(listItemElement);
    hideElement(removeButton);
  }

  #getProgressWrapperElement(listItemElement: HTMLElement) {
    return listItemElement.querySelector(`.${this.progressWrapperClass}`);
  }

  #getSpinnerElement(listItemElement: HTMLElement) {
    return listItemElement.querySelector(`.${this.spinnerClass}`);
  }
}
