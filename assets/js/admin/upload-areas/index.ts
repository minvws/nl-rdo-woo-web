import { uploadArea, UploadArea } from './upload-area';

export const uploadAreas = () => {
  const instances = new Set<UploadArea>();
  const INITIALIZED_ATTRIBUTE_NAME = 'data-initialized';

  const initialize = () => {
    cleanup();

    const uploadAreaElements = Array.from(document.getElementsByClassName('js-upload-area')) as HTMLElement[];
    uploadAreaElements.forEach((uploadAreaElement) => {
      if (uploadAreaElement.hasAttribute(INITIALIZED_ATTRIBUTE_NAME)) {
        return;
      }

      uploadAreaElement.setAttribute(INITIALIZED_ATTRIBUTE_NAME, 'true');
      const instance = uploadArea(uploadAreaElement, uploadAreaElements.length === 1);
      instance.initialize();
      instances.add(instance);
    });
  };

  const cleanup = () => {
    instances.forEach((instance) => {
      instance.cleanup();
    });
    instances.clear();
  };

  return {
    cleanup,
    initialize,
  };
};
