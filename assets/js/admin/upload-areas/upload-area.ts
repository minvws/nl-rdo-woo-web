import { areFilesEqual, filterDataTransferFiles } from '@utils';
import { AutoUploadFilesArea, FilesArea } from './files-area';
import { initializeInvalidFiles, InvalidFiles } from './invalid-files';
import { initializeUploadVisual, UploadVisual } from './upload-visual';

export interface UploadArea {
  cleanup: () => void;
  initialize: () => void;
}

export const uploadArea = (areaElement: HTMLElement, isThisTheOnlyUploadAreaOnThisPage: boolean) => {
  let abortController: AbortController;
  let filesArea: FilesArea | AutoUploadFilesArea;
  let inputElement: HTMLInputElement | null = null;
  let invalidFiles: InvalidFiles;
  let uploadVisual: UploadVisual;

  const initialize = () => {
    inputElement = areaElement.querySelector('.js-upload-input');
    const uploadVisualElement = areaElement.querySelector('.js-upload-visual') as HTMLElement;
    const invalidFilesElement = areaElement.closest('.js-upload-section')?.querySelector('.js-invalid-files') as HTMLElement;

    if (!uploadVisualElement || !inputElement || !invalidFilesElement) {
      return;
    }

    inputElement.setAttribute('tabindex', '-1');
    invalidFiles = initializeInvalidFiles(invalidFilesElement, getValidMimeTypes(), getMaxFileSize());
    uploadVisual = initializeUploadVisual(uploadVisualElement);

    if (isAutoUploadEnabled()) {
      filesArea = new AutoUploadFilesArea({
        areaElement: getAutoUploadFileArea(),
        canUploadMultipleFiles: canUploadMultipleFiles(),
        onFileRemovedFunction: onFileRemoved,
        onFileUploadFailedFunction: onFileUploadFailed,
      });
    } else {
      filesArea = new FilesArea({
        areaElement: getNoAutoUploadFileArea(),
        canUploadMultipleFiles: canUploadMultipleFiles(),
        onFileRemovedFunction: onFileRemoved,
        onFileUploadFailedFunction: onFileUploadFailed,
      });
    }

    if (isThisTheOnlyUploadAreaOnThisPage) {
      uploadVisual.adjustToCoverWholePage();
      addEventListeners(document.body, uploadVisualElement);
    } else {
      addEventListeners(areaElement, uploadVisualElement);
    }
  };

  const addEventListeners = (element: HTMLElement, uploadVisualElement: HTMLElement) => {
    abortController = new AbortController();

    element.addEventListener('dragenter', onDragEnter, { signal: abortController.signal });
    uploadVisualElement.addEventListener('dragleave', onDragLeave, { signal: abortController.signal });
    uploadVisualElement.addEventListener('dragover', onDragOver, { signal: abortController.signal });
    uploadVisualElement.addEventListener('drop', onFilesDropped, { signal: abortController.signal });

    inputElement?.addEventListener('change', onFilesSelected, { signal: abortController.signal });

    Array.from(areaElement.getElementsByClassName('js-select-files')).forEach((selectFilesElement) => {
      selectFilesElement.removeAttribute('tabindex');
      selectFilesElement.addEventListener('click', () => {
        inputElement?.click();
      }, { signal: abortController?.signal });
    });
  };

  const onDragEnter = (event: DragEvent) => {
    event.stopPropagation();
    event.preventDefault();

    if ((event.currentTarget as HTMLElement).contains(event.relatedTarget as Node)) {
      return;
    }

    if (!event.dataTransfer?.types.some((type) => type === 'Files')) {
      // The user is dragging something that isn't a file.
      return;
    }

    uploadVisual.slideInUp();
  };

  const onDragLeave = (event: DragEvent) => {
    event.stopPropagation();
    event.preventDefault();

    if ((event.currentTarget as HTMLElement).contains(event.relatedTarget as Node)) {
      return;
    }

    uploadVisual.slideOutDown();
  };

  const onDragOver = (event: DragEvent) => {
    event.stopPropagation();
    event.preventDefault();

    // This function doesn't do a lot but it's necessary to make the drop event work.
  };

  const onFilesDropped = async (event: DragEvent) => {
    event.stopPropagation();
    event.preventDefault();

    if ((event.currentTarget as HTMLElement).contains(event.relatedTarget as Node)) {
      return;
    }

    uploadVisual.slideOutUp();

    const dataTransfer = await filterDataTransferFiles(event.dataTransfer!, false);
    const filteredFiles = filterDroppedFiles(dataTransfer.files);
    updateInputFiles(filteredFiles);
  };

  const onFilesSelected = () => {
    updateInputFiles(inputElement?.files as FileList);
  };

  const updateInputFiles = (files: FileList) => {
    const { validFiles, invalidFiles: invalid } = invalidFiles.validate(files);
    invalidFiles.processInvalidFiles(invalid);

    if (validFiles.length === 0) {
      return;
    }

    setInputElementFiles(validFiles);
    filesChanged();
  };

  const filesChanged = () => {
    filesArea.addFiles(getInputElementFiles());

    if (canUploadMultipleFiles()) {
      const mainSelectFilesElement = areaElement.querySelector('.js-select-files-main') as HTMLElement;
      if (mainSelectFilesElement) {
        mainSelectFilesElement.textContent = mainSelectFilesElement.dataset.uploadMoreText || '';
      }
    }
  };

  const onFileRemoved = (removedFile: File) => {
    const dataTransfer = new DataTransfer();
    getInputElementFiles().forEach((file) => {
      if (areFilesEqual(file, removedFile)) {
        return;
      }

      dataTransfer.items.add(file);
    });

    setInputElementFiles(dataTransfer.files);
    filesChanged();
  };

  const onFileUploadFailed = (failedFile: File, message: string) => {
    // eslint-disable-next-line no-console
    console.log(`Failed to upload file "${failedFile.name}": ${message}`);
  };

  const getInputElementFiles = () => (inputElement ? Array.from(inputElement.files as FileList) : []);

  const setInputElementFiles = (files: FileList) => {
    if (!inputElement) {
      return;
    }

    inputElement.files = files;
  };

  const filterDroppedFiles = (files: FileList) => {
    if (!canUploadMultipleFiles() && files.length > 1) {
      const dataTransfer = new DataTransfer();
      dataTransfer.items.add(files.item(0) as File);
      return dataTransfer.files;
    }

    return files;
  };

  const getValidMimeTypes = () => {
    const validMimeTypes = new Set<string>();

    const accept = inputElement?.accept;
    if (!accept) {
      return validMimeTypes;
    }

    const mimeTypes = accept.split(',');
    mimeTypes.forEach((mimeType) => {
      validMimeTypes.add(mimeType);
    });

    return validMimeTypes;
  };

  const getMaxFileSize = () => {
    const { maxFileSize = null } = inputElement?.dataset || {};
    if (!maxFileSize) {
      return null;
    }

    return parseInt(maxFileSize, 10);
  };

  const canUploadMultipleFiles = () => Boolean(inputElement?.hasAttribute('multiple'));
  const isAutoUploadEnabled = () => getAutoUploadFileArea() !== null;
  const getNoAutoUploadFileArea = (): HTMLElement | null => areaElement.querySelector('.js-no-auto-upload-files-area');
  const getAutoUploadFileArea = (): HTMLElement | null => areaElement.querySelector('.js-auto-upload-files-area');

  const cleanup = () => {
    if (abortController) {
      abortController.abort();
    }

    if (filesArea) {
      filesArea.cleanup();
    }

    if (uploadVisual) {
      uploadVisual.cleanup();
    }
  };

  return {
    cleanup,
    initialize,
  };
};
