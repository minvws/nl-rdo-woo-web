import { areFilesEqual, isValidMaxFileSize } from './file';

export interface InvalidFile {
  file: File;
  hasMimeTypeError: boolean;
  hasSizeError: boolean;
}

export type InvalidFilesSet = Set<InvalidFile>;

export const filterDataTransferFiles = async (dataTransfer: DataTransfer, isRecursive = true) => {
  const files: File[] = [];
  const directoryPromises: Promise<File[]>[] = [];

  Array.from(dataTransfer.items).forEach((item) => {
    const entry = getEntry(item);
    if (entry && entry.isFile) {
      const file = item.getAsFile();
      files.push(file as File);
    } else if (entry && entry.isDirectory) {
      directoryPromises.push(getFilesFromDirectoryEntry(entry as FileSystemDirectoryEntry, isRecursive));
    }
  });

  const allDirectoryFiles = await Promise.all(directoryPromises);
  allDirectoryFiles.forEach((directoryFiles) => {
    addFiles(files, directoryFiles);
  });

  files.sort((fileA, fileB) => (fileA.name > fileB.name ? 1 : -1));

  return createDataTransfer(files);
};

const createDataTransfer = (files: File[]) => {
  const dataTransfer = new DataTransfer();
  files.forEach((file) => dataTransfer.items.add(file));
  return dataTransfer;
};

const getFilesFromDirectoryEntry = async (directoryEntry: FileSystemDirectoryEntry, isRecursive: boolean, files: File[] = []) => {
  const entries = await getEntriesFromDirectoryEntry(directoryEntry);
  const filePromises: Promise<File>[] = [];
  const filesPromises: Promise<File[]>[] = [];

  Array.from(entries).forEach((entry) => {
    if (entry.isFile) {
      filePromises.push(getFileFromFileEntry(entry as FileSystemFileEntry));
    } else if (entry.isDirectory && isRecursive) {
      filesPromises.push(getFilesFromDirectoryEntry(entry as FileSystemDirectoryEntry, isRecursive, files));
    }
  });

  await Promise.all(filesPromises);
  const entryFiles = await Promise.all(filePromises);
  addFiles(files, entryFiles);

  return files;
};

interface FutureDataTransferItem extends DataTransferItem {
  getAsEntry: () => FileSystemEntry | null;
}

const getEntry = (item: DataTransferItem) => {
  if ((item as FutureDataTransferItem).getAsEntry) {
    return (item as FutureDataTransferItem).getAsEntry();
  }

  return item.webkitGetAsEntry();
};

const getFileFromFileEntry = async (fileEntry: FileSystemFileEntry): Promise<File> => new Promise((resolve) => {
  fileEntry.file(resolve);
});

const getEntriesFromDirectoryEntry = async (directoryEntry: FileSystemDirectoryEntry): Promise<FileSystemEntry[]> => new Promise(
  (resolve) => {
    directoryEntry.createReader().readEntries(resolve);
  },
);

const hasFile = (files: File[], file: File) => files.some((existingFile) => areFilesEqual(existingFile, file));

const shouldIgnoreFile = (file: File) => file.name.startsWith('.');

const addFiles = (files: File[], newFiles: File[]) => {
  newFiles.forEach((file) => addFile(files, file));
};

const addFile = (files: File[], file: File) => {
  if (shouldIgnoreFile(file)) {
    return;
  }

  if (hasFile(files, file)) {
    return;
  }

  files.push(file);
};

export const validateFiles = (files: FileList, mimeTypes: string[], maxFileSize?: number) => {
  const invalidFiles: InvalidFilesSet = new Set();
  const validFiles = new DataTransfer();

  const mimeTypesSet = new Set(mimeTypes);
  const hasValidMimeTypesDefined = mimeTypesSet.size > 0;
  const hasValidMaxFileSize = isValidMaxFileSize(maxFileSize);

  const hasValidMimeType = (file: File) => {
    if (!hasValidMimeTypesDefined) {
      return true;
    }

    if (file.type === '') {
      // Firefox seems to have a bug where the file type is empty for files in a dragged directory
      return true;
    }

    return mimeTypesSet.has(file.type);
  };

  const hasValidSize = (file: File) => {
    if (!hasValidMaxFileSize) {
      return true;
    }

    return file.size <= (maxFileSize as number);
  };

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
