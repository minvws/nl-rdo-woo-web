import { formatFileSize, hideElement, showElement } from '@utils';

interface InvalidFile {
  file: File;
  hasMimeTypeError: boolean;
  hasSizeError: boolean;
}

type InvalidFilesSet = Set<InvalidFile>;

export interface InvalidFiles {
  processInvalidFiles: (invalidFiles: InvalidFilesSet) => void;
  validate: (files: FileList) => { invalidFiles: InvalidFilesSet; validFiles: FileList };
}

export const initializeInvalidFiles = (
  invalidFilesElement: HTMLElement,
  validMimeTypes = new Set<string>(),
  maxFileSize: number | null = null,
): InvalidFiles => {
  const listElement = invalidFilesElement.querySelector('.js-invalid-files-list');

  const validate = (files: FileList) => {
    const invalidFiles: InvalidFilesSet = new Set();
    const validFiles = new DataTransfer();

    Array.from(files).forEach((file) => {
      const hasMimeTypeError = !hasValidMimeType(file);
      const hasSizeError = !hasValidSize(file);

      if (hasMimeTypeError || hasSizeError) {
        invalidFiles.add({ file, hasMimeTypeError, hasSizeError });
      } else {
        validFiles.items.add(file);
      }
    });

    return { invalidFiles, validFiles: validFiles.files };
  };

  const hasValidMimeTypesDefined = () => validMimeTypes.size > 0;
  const hasValidMaxFileSize = () => maxFileSize !== null && maxFileSize > 0;

  const hasValidMimeType = (file: File) => {
    if (!hasValidMimeTypesDefined()) {
      return true;
    }

    if (file.type === '') {
      // Firefox seems to have a bug where the file type is empty for files in a dragged directory
      return true;
    }

    return validMimeTypes.has(file.type);
  };

  const hasValidSize = (file: File) => {
    if (!hasValidMaxFileSize()) {
      return true;
    }

    return file.size <= (maxFileSize as number);
  };

  const processInvalidFiles = (invalidFiles: InvalidFilesSet) => {
    if (invalidFiles.size === 0) {
      hideElement(invalidFilesElement);
      return;
    }

    displayInvalidFiles(invalidFiles);
    showElement(invalidFilesElement);
  };

  const displayInvalidFiles = (invalidFiles: InvalidFilesSet) => {
    if (listElement) {
      listElement.innerHTML = '';
    }

    invalidFiles.forEach((invalidFile) => {
      const listItemElement = document.createElement('li');
      listItemElement.classList.add('bhr-li');

      listItemElement.innerHTML = formatInvalidFile(invalidFile);
      listElement?.appendChild(listItemElement);
    });
  };

  const formatInvalidFile = (invalidFile: InvalidFile) => {
    const { file } = invalidFile;

    return `<div class="truncate">${file.name} (${formatFileSize(file.size)})</div>`;
  };

  return {
    processInvalidFiles,
    validate,
  };
};
