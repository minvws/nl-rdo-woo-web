import { icon, skipLink } from '@js/admin/component';
import { areFilesEqual, getIconNameByMimeType } from '@js/admin/utils';
import {
  hideElement,
  isFocusWithinElement,
  showElement,
  uniqueId,
} from '@utils';

interface FilesAreaOptions {
  areaElement: HTMLElement | null;
  canUploadMultipleFiles: boolean;
  onFileRemovedFunction: (file: File) => void;
  onFileUploadFailedFunction: (file: File, message: string) => void;
  returnFocusToElement: HTMLElement | null;
}

export class FilesArea {
  #noFilesMessageElement: HTMLElement | null;
  #store = new Map();

  areaElement: HTMLElement | null;
  canUploadMultipleFiles = false;
  listElement: HTMLElement;
  listTitleElement: HTMLHeadingElement;
  returnFocusToElement: HTMLElement | null;
  skipLinkClassName = 'js-files-area-skip-link';

  onBeforeFileRemoveFunction: () => void = () => {};
  onFileRemovedFunction: (file: File) => void = () => {};
  onFileUploadFailedFunction: (file: File, message: string) => void = () => {};

  constructor(options: FilesAreaOptions) {
    const {
      areaElement = null,
      canUploadMultipleFiles,
      onFileRemovedFunction,
      onFileUploadFailedFunction,
      returnFocusToElement,
    } = options;

    this.#noFilesMessageElement = areaElement?.querySelector(
      '.js-no-files-message',
    ) as HTMLElement;

    this.areaElement = areaElement;
    this.canUploadMultipleFiles = canUploadMultipleFiles;
    this.onFileUploadFailedFunction = onFileUploadFailedFunction;
    this.onFileRemovedFunction = onFileRemovedFunction;
    this.returnFocusToElement = returnFocusToElement;

    this.listTitleElement = this.#createListTitleElement();

    this.listElement = this.#createListElement();
    this.listElement.classList.add('bhr-upload-area__files-list');

    this.areaElement?.appendChild(this.#createAboveListSkipLink());
    this.areaElement?.appendChild(this.listTitleElement);
    this.areaElement?.appendChild(this.listElement);
    this.areaElement?.appendChild(this.#createBelowListSkipLink());
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
    const isFocusWithinListItem = isFocusWithinElement(
      document.getElementById(fileId),
    );

    remove();
    this.removeFromStore(fileId);
    this.afterListUpdated();

    if (isFocusWithinListItem) {
      this.returnFocusToElement?.focus();
    }

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
    element.id = fileId;
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
          <button class="cursor-pointer py-1 px-2 mr-2 hover-focus:text-bhr-maximum-red ${this.removeButtonClass}" type="button">
            ${icon({ color: 'fill-current', name: 'trash-bin', size: 16 })} <span class="sr-only">Verwijder ${file.name}</span>
          </button>
          <div class="py-1 px-2 mr-2 hidden ${this.spinnerClass}" tabindex="-1">
            ${icon({ name: 'loader', css: 'animate-spin', size: 16 })}
          </div>
          <div class="py-1 px-2 mr-2 hidden ${this.uploadFailedClass}" tabindex="-1">
            ${icon({ name: 'cross-rounded-filled', color: 'fill-bhr-maximum-red', size: 16 })}
          </div>
          <div class="py-1 px-2 mr-2 hidden ${this.uploadSuccessClass}" tabindex="-1">
            ${icon({ name: 'check-rounded-filled', color: 'fill-bhr-philippine-green', size: 16 })}
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

  addElementFunctionality(
    listItemElement: HTMLElement,
    fileId: string,
    // We need to pass the file argument to this function since it's necessary in places where this class is extended.
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    _file: File,
  ) {
    return this.addRemoveElementFunctionality(listItemElement, fileId);
  }

  addRemoveElementFunctionality(listItemElement: HTMLElement, fileId: string) {
    this.makeElementTransitionable(listItemElement);

    const abortController = new AbortController();

    this.getRemoveButton(listItemElement)?.addEventListener(
      'click',
      () => {
        this.removeFile(fileId);
      },
      { signal: abortController.signal },
    );

    return () => {
      abortController.abort();
    };
  }

  makeElementTransitionable(listItemElement: HTMLElement) {
    listItemElement.classList.add(
      'transition-all',
      'delay-[3000ms]',
      'duration-500',
      'overflow-hidden',
    );
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

  saveInStore(
    fileId: string,
    file: File,
    cleanupFunction: () => void,
    removeFunction: () => void,
  ) {
    this.#store.set(fileId, {
      file,
      cleanup: cleanupFunction,
      remove: removeFunction,
    });
  }

  #createListTitleElement = () => {
    const listTitleElement = document.createElement('h3');
    listTitleElement.classList.add('sr-only');
    listTitleElement.textContent = 'Te uploaden bestanden';
    hideElement(listTitleElement);

    return listTitleElement;
  };

  #createListElement = () => {
    const listElement = document.createElement('ul');
    hideElement(listElement);

    return listElement;
  };

  #createAboveListSkipLink() {
    return skipLink({
      content: 'Naar einde van lijst met te uploaden bestanden',
      css: `focus:mt-2 hidden ${this.skipLinkClassName}`,
      id: 'begin-van-lijst-met-te-uploaden-bestanden',
      href: '#einde-van-lijst-met-te-uploaden-bestanden',
    });
  }

  #createBelowListSkipLink() {
    return skipLink({
      content: 'Naar begin van lijst met te uploaden bestanden',
      css: `focus:mb-2 hidden ${this.skipLinkClassName}`,
      id: 'einde-van-lijst-met-te-uploaden-bestanden',
      href: '#begin-van-lijst-met-te-uploaden-bestanden',
    });
  }

  afterListUpdated() {
    const doesOneOfSkipLinksHaveFocus = this.isFocusOnOneOfSkipLinks();

    if (this.getNumberOfFiles() === 0) {
      this.hideListTitle();
      this.hideList();
      this.hideSkipLinks();
      this.showNoMessagesElement();
      this.updateListGrid();

      if (doesOneOfSkipLinksHaveFocus) {
        this.returnFocusToElement?.focus();
      }

      return;
    }

    this.showListTitle();
    this.showList();
    this.showSkipLinks();
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

  isFocusOnOneOfSkipLinks(): boolean {
    return (
      this.getSkipLinks().some((skipLinkElement) =>
        isFocusWithinElement(skipLinkElement),
      ) ?? false
    );
  }

  getSkipLinks() {
    const nodeList = this.areaElement?.querySelectorAll(
      `.${this.skipLinkClassName}`,
    ) as NodeListOf<HTMLElement> | null;
    return Array.from(nodeList || []);
  }

  hideSkipLinks() {
    this.getSkipLinks().forEach(hideElement);
  }

  showSkipLinks() {
    this.getSkipLinks().forEach(showElement);
  }

  hideList() {
    hideElement(this.listElement);
  }

  showList() {
    showElement(this.listElement);
  }

  hideListTitle() {
    hideElement(this.listTitleElement);
  }

  showListTitle() {
    showElement(this.listTitleElement);
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
    return listItemElement.querySelector(
      `.${this.removeButtonClass}`,
    ) as HTMLElement | null;
  }
}
