import { uploadFile, type OnUploadError } from '@js/admin/utils';
import { mount, type VueWrapper } from '@vue/test-utils';
import {
  afterEach,
  beforeEach,
  describe,
  expect,
  Mock,
  test,
  vi,
} from 'vitest';
import { nextTick } from 'vue';
import SelectedFile from './SelectedFile.vue';
import { UPLOAD_AREA_ENDPOINT } from './static';
import { createTestFile } from '@js/test';

let providedOnProgress: (progress: number) => void;
let providedOnSuccess: (uploadUuid: string) => void;
let providedOnError: (error: OnUploadError) => void;

const cleanupUpload = vi.fn();

vi.mock('@js/admin/utils', () => ({
  uploadFile: vi
    .fn()
    .mockImplementation(({ onProgress, onSuccess, onError }) => {
      providedOnProgress = onProgress;
      providedOnSuccess = onSuccess;
      providedOnError = onError;

      return cleanupUpload;
    }),
}));

describe('The <SelectedFile /> component', () => {
  const mockedProvidedFile = createTestFile({
    name: 'mock-file.txt',
    type: 'mocked/mime-type',
  });

  interface Options {
    enableAutoUpload?: boolean;
  }

  const createComponent = (options: Partial<Options> = {}) => {
    const { enableAutoUpload = false } = options;
    return mount(SelectedFile, {
      props: {
        enableAutoUpload,
        file: mockedProvidedFile,
        fileId: 'mocked-file-id',
        payload: {
          'mocked-key': 'mocked-value',
        },
      },
      global: {
        provide: {
          [UPLOAD_AREA_ENDPOINT]: 'mocked-upload-endpoint',
        },
        renderStubDefaultSlot: true,
      },
      shallow: true,
    });
  };

  const getMimeTypeIconComponent = (component: VueWrapper) =>
    component.findComponent({ name: 'MimeTypeIcon' });

  const getCollapsibleComponent = (component: VueWrapper) =>
    component.findComponent({ name: 'Collapsible' });

  const getProgressElement = (component: VueWrapper) =>
    component.find('progress');

  const getDeleteButtonElement = (component: VueWrapper) =>
    component.find('button');

  const findIconComponent = (component: VueWrapper, iconName: string) =>
    component
      .findAllComponents({ name: 'Icon' })
      .find((icon) => icon.props('name') === iconName);

  const getSpinnerIconComponent = (component: VueWrapper) =>
    findIconComponent(component, 'loader');

  const getErrorIconComponent = (component: VueWrapper) =>
    findIconComponent(component, 'cross-rounded-filled');

  const getSuccessIconComponent = (component: VueWrapper) =>
    findIconComponent(component, 'check-rounded-filled');

  beforeEach(() => {
    cleanupUpload.mockClear();
    (uploadFile as Mock).mockClear();

    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  test('should render its content in an <li> element', () => {
    const component = createComponent();

    expect(component.element.tagName).toBe('LI');
  });

  test('should render the file name', () => {
    const component = createComponent();

    expect(component.html()).toContain('mock-file.txt');
  });

  test('should render an icon displaying the file type', () => {
    const component = createComponent();

    expect(getMimeTypeIconComponent(component).props('mimeType')).toBe(
      'mocked/mime-type',
    );
  });

  describe('when auto upload is enabled', () => {
    test('should emit the uploading event when the component is mounted', async () => {
      const component = createComponent({ enableAutoUpload: true });

      await nextTick();
      expect(component.emitted('uploading')).toBeTruthy();
    });

    test('should upload the file', async () => {
      createComponent({ enableAutoUpload: true });
      await nextTick();

      expect(uploadFile).toHaveBeenNthCalledWith(1, {
        endpoint: 'mocked-upload-endpoint',
        file: mockedProvidedFile,
        onProgress: providedOnProgress,
        onSuccess: providedOnSuccess,
        onError: providedOnError,
        payload: {
          'mocked-key': 'mocked-value',
        },
      });
    });

    test('should update the progress of the upload', async () => {
      const component = createComponent({ enableAutoUpload: true });
      await nextTick();

      expect(getProgressElement(component).attributes('value')).toBe('0');

      providedOnProgress(50);
      await nextTick();
      expect(getProgressElement(component).attributes('value')).toBe('50');
    });

    test('should hide the delete button and display a spinner when the file is uploaded', async () => {
      const component = createComponent({ enableAutoUpload: true });
      await nextTick();

      expect(getDeleteButtonElement(component).exists()).toBe(true);
      expect(getSpinnerIconComponent(component)).toBeFalsy();

      providedOnProgress(100);
      await nextTick();

      expect(getDeleteButtonElement(component).exists()).toBe(false);
      expect(getSpinnerIconComponent(component)?.exists()).toBe(true);
    });

    test('should display a success icon when the file is uploaded successfully', async () => {
      const component = createComponent({ enableAutoUpload: true });
      await nextTick();

      providedOnProgress(100);
      await nextTick();

      expect(getSpinnerIconComponent(component)?.exists()).toBe(true);
      expect(getSuccessIconComponent(component)).toBeFalsy();

      providedOnSuccess('mocked-upload-uuid');
      await nextTick();
      vi.advanceTimersByTime(2000);

      expect(getSpinnerIconComponent(component)).toBeFalsy();
      expect(getSuccessIconComponent(component)?.exists()).toBe(true);
    });

    test('should emit an uploaded event when the file is uploaded successfully', async () => {
      const component = createComponent({ enableAutoUpload: true });
      await nextTick();

      expect(component.emitted('uploaded')).toBeFalsy();
      expect(cleanupUpload).not.toHaveBeenCalled();

      await getCollapsibleComponent(component).vm.$emit('collapsed');
      await nextTick();

      expect(component.emitted('uploaded')).toBeTruthy();
      expect(cleanupUpload).toHaveBeenCalled();
    });

    test('should display an error icon when the file upload fails and emit the uploadError event', async () => {
      const component = createComponent({ enableAutoUpload: true });
      await nextTick();

      providedOnProgress(100);
      await nextTick();

      expect(getSpinnerIconComponent(component)?.exists()).toBe(true);
      expect(getErrorIconComponent(component)).toBeFalsy();
      expect(component.emitted('uploadError')).toBeFalsy();

      const mockedUploadError = {
        isTechnialError: false,
        isUnsafeError: false,
        isWhiteListError: true,
      };

      providedOnError(mockedUploadError);
      await nextTick();
      vi.advanceTimersByTime(2000);

      expect(getSpinnerIconComponent(component)).toBeFalsy();
      expect(getErrorIconComponent(component)?.exists()).toBe(true);
      expect(component.emitted('uploadError')?.[0]).toEqual([
        'mocked-file-id',
        mockedProvidedFile,
        mockedUploadError,
      ]);
    });

    test('should emit the delete event when the delete button is clicked', async () => {
      const component = createComponent({ enableAutoUpload: true });
      await nextTick();

      expect(component.emitted('delete')).toBeFalsy();
      expect(cleanupUpload).not.toHaveBeenCalled();

      await getDeleteButtonElement(component).trigger('click');

      expect(component.emitted('delete')?.[0]).toEqual(['mocked-file-id']);
      expect(cleanupUpload).toHaveBeenCalled();
    });

    test('should stop uploading the file when the component is unmounted', async () => {
      const component = createComponent({ enableAutoUpload: true });
      await nextTick();

      expect(cleanupUpload).not.toHaveBeenCalled();

      component.unmount();
      expect(cleanupUpload).toHaveBeenCalled();
    });
  });
});
