import { icon } from '@js/admin/component';
import { areFilesEqual, getIconNameByMimeType, hideElement, showElement, uniqueId } from '@utils';

interface FilesAreaOptions {
  areaElement: HTMLElement | null;
  canUploadMultipleFiles: boolean;
  onFileRemovedFunction: (file: File) => void;
  onFileUploadFailedFunction: (file: File, message: string) => void;
}

export class FilesArea {
  #noFilesMessageElement: HTMLElement | null;
  #store = new Map();

  areaElement: HTMLElement | null;
  canUploadMultipleFiles = false;
  listElement: HTMLElement | null;

  onFileRemovedFunction: (file: File) => void = () => {};
  onFileUploadFailedFunction: (file: File, message: string) => void = () => {};

  constructor(options: FilesAreaOptions) {
    const {
      areaElement = null,
      canUploadMultipleFiles,
      onFileRemovedFunction,
      onFileUploadFailedFunction,
    } = options;

    this.#noFilesMessageElement = areaElement?.querySelector('.js-no-files-message') as HTMLElement;

    this.areaElement = areaElement;
    this.canUploadMultipleFiles = canUploadMultipleFiles;
    this.onFileUploadFailedFunction = onFileUploadFailedFunction;
    this.onFileRemovedFunction = onFileRemovedFunction;

    this.listElement = this.#createListElement();
    this.listElement.classList.add('bhr-upload-area__files-list');
  }

  cleanup() {
    this.cleanupFiles();
  }

  cleanupFiles() {
    this.getStoreKeys().forEach((fileId) => {
      this.cleanupFile(fileId);
    });
  }

  cleanupFile(fileId: string) {
    const { cleanup } = this.getFromStore(fileId);
    cleanup();
  }

  addFiles(files: File[]) {
    if (this.canUploadMultipleFiles === false) {
      this.removeFiles();
    }

    this.showList();

    files.forEach((file) => {
      if (this.hasFile(file)) {
        return;
      }

      this.addElement(file);
    });

    this.afterListUpdated();
  }

  removeFiles() {
    this.getStoreKeys().forEach((fileId) => {
      this.removeFile(fileId, false);
    });
  }

  removeFile(fileId: string, shouldNotify = true) {
    const { file, remove } = this.getFromStore(fileId);
    remove();
    this.removeFromStore(fileId);
    this.afterListUpdated();

    if (shouldNotify) {
      this.onFileRemovedFunction(file);
    }
  }

  hasFile(file: File) {
    return this.getStoreKeys().some((fileId) => {
      const { file: storedFile } = this.getFromStore(fileId);
      return areFilesEqual(file, storedFile);
    });
  }

  addElement(file: File) {
    const fileId = uniqueId('file', 32);
    const element = this.createElement(fileId, file);
    this.listElement?.appendChild(element);

    const cleanup = this.addElementFunctionality(element, fileId, file);

    this.saveInStore(fileId, file, cleanup, () => {
      cleanup();
      element.remove();
    });
  }

  createElement(fileId: string, file: File) {
    const listItemElement = document.createElement('li');
    listItemElement.classList.add('pb-1');
    listItemElement.innerHTML = this.getElementHtml(fileId, file);
    return listItemElement;
  }

  getElementHtml(fileId: string, file: File) {
    const progressId = `progress-${fileId}`;
    return `
        <div class="flex">
            <div class="flex grow pl-4 py-1 truncate">
                <span class="mr-2">${icon({ name: getIconNameByMimeType(file.type), size: 20 })}</span>
                <div class="leading-none pt-1.5 truncate">${file.name}</div>
            </div>
            <div>
                <button class="cursor-pointer py-1 px-2 mr-2 hover-focus:text-maximum-red ${this.removeButtonClass}" type="button">
                    ${icon({ color: 'fill-current', name: 'trash-bin', size: 16 })} <span class="sr-only">Verwijder ${file.name}</span>
                </button>
                <div class="py-1 px-2 mr-2 hidden ${this.spinnerClass}">
                    ${icon({ name: 'loader', css: 'animate-spin', size: 16 })}
                </div>
                <div class="py-1 px-2 mr-2 hidden ${this.uploadFailedClass}">
                    ${icon({ name: 'cross-rounded-filled', color: 'fill-maximum-red', size: 16 })}
                </div>
                <div class="py-1 px-2 mr-2 hidden ${this.uploadSuccessClass}">
                    ${icon({ name: 'check-rounded-filled', color: 'fill-philippine-green', size: 16 })}
                </div>
            </div>
        </div>
        <div class="${this.progressWrapperClass} hidden">
            <label class="sr-only" for="${progressId}">Voortgang van ${file.name}</label>
            <div class="relative mx-4">
                <progress class="bhr-upload-area__progress ${this.progressClass}" id="${progressId}" max="100" value="0" />
            </div>
        </div>`;
  }

  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  addElementFunctionality(listItemElement: HTMLElement, fileId: string, file: File) {
    return this.addRemoveElementFunctionality(listItemElement, fileId);
  }

  addRemoveElementFunctionality(listItemElement: HTMLElement, fileId: string) {
    this.makeElementTransitionable(listItemElement);

    const abortController = new AbortController();

    this.getRemoveButton(listItemElement)?.addEventListener('click', () => {
      this.removeFile(fileId);
    }, { signal: abortController.signal });

    return () => {
      abortController.abort();
    };
  }

  makeElementTransitionable(listItemElement: HTMLElement) {
    listItemElement.classList.add('transition-all', 'delay-[3000ms]', 'duration-500', 'overflow-hidden');
    const height = listItemElement.offsetHeight;
    listItemElement.style.height = `${height}px`;
  }

  getNumberOfFiles() {
    return this.#store.size;
  }

  getStoreKeys() {
    return Array.from(this.#store.keys());
  }

  getFromStore(fileId: string) {
    return this.#store.get(fileId);
  }

  removeFromStore(fileId: string) {
    this.#store.delete(fileId);
  }

  saveInStore(fileId: string, file: File, cleanupFunction: () => void, removeFunction: () => void) {
    this.#store.set(fileId, { file, cleanup: cleanupFunction, remove: removeFunction });
  }

  #createListElement = () => {
    const listElement = document.createElement('ul');
    hideElement(listElement);
    this.areaElement?.appendChild(listElement);

    return listElement;
  };

  afterListUpdated() {
    if (this.getNumberOfFiles() === 0) {
      this.hideList();
      this.showNoMessagesElement();
      this.updateListGrid();
      return;
    }

    this.showList();
    this.hideNoMessagesElement();
    this.updateListGrid();
  }

  updateListGrid() {
    const numberOfFiles = this.getNumberOfFiles();

    this.listElement?.classList.remove('grid', 'grid-cols-2', 'grid-cols-3');
    if (numberOfFiles >= 3) {
      this.listElement?.classList.add('grid', 'grid-cols-3');
      return;
    }

    if (numberOfFiles === 2) {
      this.listElement?.classList.add('grid', 'grid-cols-2');
    }
  }

  hideList() {
    hideElement(this.listElement);
  }

  showList() {
    showElement(this.listElement);
  }

  hideNoMessagesElement() {
    hideElement(this.#noFilesMessageElement);
  }

  showNoMessagesElement() {
    showElement(this.#noFilesMessageElement);
  }

  get spinnerClass() {
    return 'js-spinner';
  }

  get removeButtonClass() {
    return 'js-remove-file';
  }

  get uploadFailedClass() {
    return 'js-upload-failed';
  }

  get uploadSuccessClass() {
    return 'js-upload-success';
  }

  get progressClass() {
    return 'js-progress';
  }

  get progressWrapperClass() {
    return 'js-progress-wrapper';
  }

  getRemoveButton(listItemElement: HTMLElement) {
    return listItemElement.querySelector(`.${this.removeButtonClass}`);
  }
}
