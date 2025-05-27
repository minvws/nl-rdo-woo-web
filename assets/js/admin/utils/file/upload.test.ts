import {
  describe,
  test,
  expect,
  beforeEach,
  vi,
  afterEach,
  Mock,
  MockInstance,
} from 'vitest';
import { uploadFile, UploadStatus } from './upload';

describe('The "uploadFile" function', () => {
  let fetchSpy: MockInstance;
  let xmlHttpRequestMock1: XMLHttpRequest;
  let xmlHttpRequestMock2: XMLHttpRequest;

  let onErrorSpy: Mock;
  let onProgressSpy: Mock;
  let onSuccessSpy: Mock;

  const createXmlHttpRequestMock = (chunkNumber: number) =>
    ({
      addEventListener: vi.fn(),
      abort: vi.fn(),
      open: vi.fn(),
      send: vi.fn().mockImplementation(() => mockUploadEvents(chunkNumber)),
      setRequestHeader: vi.fn(),
      readyState: 4,
      status: 200,
      response: {
        data: {
          mimeType: 'mocked/mime-type',
          uploadUuid: 'mocked-upload-uuid',
        },
      },
      upload: {
        addEventListener: vi.fn(),
      },
    }) as unknown as XMLHttpRequest;

  const createXmlHttpRequestMocks = () => {
    xmlHttpRequestMock1 = createXmlHttpRequestMock(1);
    xmlHttpRequestMock2 = createXmlHttpRequestMock(2);

    vi.spyOn(window, 'XMLHttpRequest')
      .mockImplementationOnce(() => xmlHttpRequestMock1)
      .mockImplementationOnce(() => xmlHttpRequestMock2);
  };

  const getHandler = (eventTarget: EventTarget, findEvent: string) =>
    (eventTarget.addEventListener as any).mock.calls.find(
      ([event]: [string]) => event === findEvent,
    )[1];

  const addEvent = async (callback: () => void) => {
    await new Promise((resolve) => setTimeout(resolve, 1000));
    callback();
  };

  const mockUploadEvents = async (chunkNumber: number) => {
    const xmlHttpRequestMock =
      chunkNumber === 1 ? xmlHttpRequestMock1 : xmlHttpRequestMock2;

    const progressHandler = getHandler(xmlHttpRequestMock.upload, 'progress');
    const loadHandler = getHandler(xmlHttpRequestMock, 'load');

    if (chunkNumber === 1) {
      // Simulate first chunk
      await addEvent(() => {
        progressHandler({ loaded: 8 * 1024 * 1024, total: 16 * 1024 * 1024 }); // 50% chunk
      });

      await addEvent(() => {
        progressHandler({ loaded: 16 * 1024 * 1024, total: 16 * 1024 * 1024 }); // 100% of chunk
      });
    } else {
      // Simulate second chunk
      await addEvent(() => {
        progressHandler({ loaded: 4 * 1024 * 1024, total: 8 * 1024 * 1024 }); // 50% of chunk
        progressHandler({ loaded: 4 * 1024 * 1024, total: 8 * 1024 * 1024 }); // Mimic progress event being called twice with the same value
      });

      await addEvent(() => {
        progressHandler({ loaded: 8 * 1024 * 1024, total: 8 * 1024 * 1024 }); // 100% of chunk
      });
    }

    await addEvent(() => {
      loadHandler();
    });
  };

  const setRequestMockProperty = (
    xmlHttpRequestMock: XMLHttpRequest,
    property: string,
    value: unknown,
  ) => {
    Object.defineProperty(xmlHttpRequestMock, property, {
      value,
      writable: true,
    });
  };

  interface UploadFileOptions {
    endpoint: string;
    payload: Record<string, string>;
    withCallbacks: boolean;
  }

  const triggerUploadFile = async (
    options: Partial<UploadFileOptions> = {},
  ) => {
    const file = new File(['x'.repeat(24 * 1024 * 1024)], 'mock-file.txt', {
      type: 'mocked/mime-type',
    });

    const { endpoint, payload, withCallbacks = true } = options;

    onErrorSpy = vi.fn();
    onProgressSpy = vi.fn();
    onSuccessSpy = vi.fn();

    return uploadFile({
      endpoint,
      file,
      payload,
      onError: withCallbacks ? onErrorSpy : undefined,
      onProgress: withCallbacks ? onProgressSpy : undefined,
      onSuccess: withCallbacks ? onSuccessSpy : undefined,
    });
  };

  beforeEach(() => {
    vi.useFakeTimers();
    createXmlHttpRequestMocks();

    fetchSpy = vi.spyOn(window, 'fetch').mockResolvedValue({
      status: 200,
      json: vi.fn().mockResolvedValue({
        status: UploadStatus.Stored,
      }),
    } as unknown as Response);
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  test('should upload the file in multiple chunks of up to 16 MB each to the provided endpoint', async () => {
    await triggerUploadFile({
      endpoint: 'mocked-endpoint',
      withCallbacks: false,
    });
    await vi.advanceTimersByTimeAsync(6000);

    expect(xmlHttpRequestMock1.open).toHaveBeenNthCalledWith(
      1,
      'POST',
      'mocked-endpoint',
      true,
    );

    expect(xmlHttpRequestMock2.open).toHaveBeenNthCalledWith(
      1,
      'POST',
      'mocked-endpoint',
      true,
    );
  });

  test('should send the correct payload', async () => {
    await triggerUploadFile({
      payload: { mocked: 'payload', another: 'another_payload' },
    });

    const formData = (xmlHttpRequestMock1 as any).send.mock.calls[0][0];

    expect(formData.get('chunkindex')).toBe('0');
    expect(formData.get('totalchunkcount')).toBe('2');
    expect(formData.get('mocked')).toBe('payload');
    expect(formData.get('another')).toBe('another_payload');
  });

  test('should upload the file to "/balie/uploader" by default', async () => {
    await triggerUploadFile();

    expect(xmlHttpRequestMock1.open).toHaveBeenCalledWith(
      'POST',
      '/balie/uploader',
      true,
    );
  });

  test('should call the provided onProgress callback with the correct progress value', async () => {
    await triggerUploadFile();
    await vi.advanceTimersByTimeAsync(6000);

    expect(onProgressSpy.mock.calls[0][0]).toBe(Math.round((8 / 24) * 100));
    expect(onProgressSpy.mock.calls[1][0]).toBe(Math.round((16 / 24) * 100));
    expect(onProgressSpy.mock.calls[2][0]).toBe(Math.round((20 / 24) * 100));
    expect(onProgressSpy.mock.calls[3][0]).toBe(100);
    expect(onProgressSpy).toHaveBeenCalledTimes(4);
  });

  describe('when the file is uploaded', () => {
    test('should make a request to the status endpoint', async () => {
      await triggerUploadFile();
      await vi.advanceTimersByTimeAsync(6000);

      expect(fetchSpy).toHaveBeenNthCalledWith(
        1,
        '/balie/api/uploader/upload/mocked-upload-uuid/status',
      );
    });

    describe('handling the response from the status endpoint', () => {
      test('should call the provided onSuccess callback when the request fails since we can assume this endpoint is not implemented yet', async () => {
        fetchSpy.mockResolvedValue(new Error('Not implemented'));

        await triggerUploadFile();
        await vi.advanceTimersByTimeAsync(6000);

        expect(onSuccessSpy).toHaveBeenCalledWith('mocked-upload-uuid', {
          mimeType: 'mocked/mime-type',
          uploadUuid: 'mocked-upload-uuid',
        });
      });

      test('should call the provided onSuccess callback when the response says the file is stored', async () => {
        await triggerUploadFile();
        await vi.advanceTimersByTimeAsync(6000);

        expect(onSuccessSpy).toHaveBeenCalledWith('mocked-upload-uuid', {
          mimeType: 'mocked/mime-type',
          uploadUuid: 'mocked-upload-uuid',
        });
      });

      test('should call the provided onError callback when the response says the file is aborted', async () => {
        fetchSpy.mockResolvedValue({
          status: 200,
          json: vi.fn().mockResolvedValue({
            status: UploadStatus.Aborted,
          }),
        } as unknown as Response);

        await triggerUploadFile();
        await vi.advanceTimersByTimeAsync(6000);

        expect(onErrorSpy).toHaveBeenCalledWith({
          isTechnialError: true,
          isUnsafeError: false,
          isWhiteListError: false,
        });
      });

      test('should call the provided onError callback when the response says the file failed validation', async () => {
        fetchSpy.mockResolvedValue({
          status: 200,
          json: vi.fn().mockResolvedValue({
            status: UploadStatus.ValidationFailed,
          }),
        } as unknown as Response);

        await triggerUploadFile();
        await vi.advanceTimersByTimeAsync(6000);

        expect(onErrorSpy).toHaveBeenCalledWith({
          isTechnialError: false,
          isUnsafeError: true,
          isWhiteListError: false,
        });
      });

      test('should recheck the status if the response says the file passed validation (it should in the end be stored)', async () => {
        fetchSpy.mockResolvedValueOnce({
          status: 200,
          json: vi.fn().mockResolvedValue({
            status: UploadStatus.ValidationPassed,
          }),
        } as unknown as Response);

        fetchSpy.mockResolvedValueOnce({
          status: 200,
          json: vi.fn().mockResolvedValue({
            status: UploadStatus.Stored,
          }),
        } as unknown as Response);

        await triggerUploadFile();
        await vi.advanceTimersByTimeAsync(6000);

        expect(fetchSpy).toHaveBeenCalledTimes(1);
        expect(onSuccessSpy).not.toHaveBeenCalled();

        await vi.advanceTimersByTimeAsync(250);
        expect(fetchSpy).toHaveBeenCalledTimes(2);
        expect(onSuccessSpy).toHaveBeenCalledWith('mocked-upload-uuid', {
          mimeType: 'mocked/mime-type',
          uploadUuid: 'mocked-upload-uuid',
        });
      });
    });
  });

  test('should call the provided onSuccess callback with the returned uploadUuid when uploading succeeds', async () => {
    await triggerUploadFile();

    expect(onSuccessSpy).not.toHaveBeenCalled();

    await vi.advanceTimersByTimeAsync(3000);
    expect(onSuccessSpy).not.toHaveBeenCalled();

    await vi.advanceTimersByTimeAsync(3000);
    expect(onSuccessSpy).toHaveBeenCalledWith('mocked-upload-uuid', {
      mimeType: 'mocked/mime-type',
      uploadUuid: 'mocked-upload-uuid',
    });
  });

  test('should call the provided onError callback when uploading fails', async () => {
    await triggerUploadFile();

    expect(onErrorSpy).not.toHaveBeenCalled();

    await vi.advanceTimersByTimeAsync(3000);
    expect(onErrorSpy).not.toHaveBeenCalled();

    setRequestMockProperty(xmlHttpRequestMock2, 'status', 500);
    setRequestMockProperty(xmlHttpRequestMock2, 'response', {
      error: 'error.technical',
    });

    await vi.advanceTimersByTimeAsync(3000);
    expect(onErrorSpy).toHaveBeenCalledWith({
      isTechnialError: true,
      isUnsafeError: false,
      isWhiteListError: false,
    });
  });
});
