import { formatFileSize } from '@js/admin/utils';
import { hideElement, showElement } from '@utils';

export interface InvalidFiles {
  processInvalidFiles: (invalidFiles: File[]) => void;
}

export const initializeInvalidFiles = (
  invalidFilesElement: HTMLElement,
): InvalidFiles => {
  const listElement = invalidFilesElement.querySelector(
    '.js-invalid-files-list',
  );

  const processInvalidFiles = (files: File[]) => {
    if (files.length === 0) {
      hideElement(invalidFilesElement);
      return;
    }

    displayInvalidFiles(files);
    showElement(invalidFilesElement);
  };

  const displayInvalidFiles = (files: File[]) => {
    if (listElement) {
      listElement.innerHTML = '';
    }

    files.forEach((file) => {
      const listItemElement = document.createElement('li');
      listItemElement.classList.add('bhr-li');

      listItemElement.innerHTML = formatInvalidFile(file);
      listElement?.appendChild(listItemElement);
    });
  };

  const formatInvalidFile = (file: File) => {
    return `<div class="truncate">${file.name} (${formatFileSize(file.size)})</div>`;
  };

  return { processInvalidFiles };
};
