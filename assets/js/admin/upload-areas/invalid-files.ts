import {
  formatFileSize,
  type InvalidFile,
  type InvalidFilesSet,
} from '@js/admin/utils';
import { hideElement, showElement } from '@utils';

export interface InvalidFiles {
  processInvalidFiles: (invalidFiles: InvalidFilesSet) => void;
}

export const initializeInvalidFiles = (
  invalidFilesElement: HTMLElement,
): InvalidFiles => {
  const listElement = invalidFilesElement.querySelector(
    '.js-invalid-files-list',
  );

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

  return { processInvalidFiles };
};
