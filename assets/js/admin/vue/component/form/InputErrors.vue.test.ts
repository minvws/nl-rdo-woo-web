import { getErrorsId, InputErrorId } from '@admin-fe/form';
import { mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';
import InputErrors from './InputErrors.vue';

describe('The "<InputErrors />" component', () => {
  const createComponent = () =>
    mount(InputErrors, {
      props: {
        errors: [
          {
            id: InputErrorId.Required,
          },
          {
            id: InputErrorId.Email,
          },
          {
            id: 'non-existing-id',
          },
        ],
        inputId: 'mocked-input-id',
        validatorMessages: {
          [InputErrorId.Required]: () => 'Mocked required error',
        },
      },
      shallow: true,
    });

  test('should map the errors to error messages', () => {
    const errorMessagesComponent = createComponent().findComponent({
      name: 'ErrorMessages',
    });

    expect(errorMessagesComponent.props()).toMatchObject({
      id: getErrorsId('mocked-input-id'),
      messages: [
        'Mocked required error',
        'Vul een geldig e-mailadres in (zoals voor@beeld.com)',
      ],
    });
  });
});
