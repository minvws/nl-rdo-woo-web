import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick, ref } from 'vue';
import { z } from 'zod';
import { useFormStore } from './form-store';
import { useInputStore } from './input-store';
import { minLength } from '../form/validator';

describe('the "useFormStore" composable', () => {
  let formStore: ReturnType<typeof useFormStore>;
  let inputStoreName: ReturnType<typeof useInputStore>;
  let inputStoreAge: ReturnType<typeof useInputStore>;

  beforeEach(() => {
    formStore = useFormStore(
      () => Promise.resolve(new Response()),
      z.object({}),
    );
    inputStoreName = useInputStore('name', 'Name', ref('John'), [minLength(3)]);
    inputStoreAge = useInputStore('age', 'Age', ref(25));

    formStore.addInput(inputStoreName);
    formStore.addInput(inputStoreAge);
  });

  it('should be able to retrieve the value of the form', () => {
    expect(formStore.getValue()).toEqual({
      name: 'John',
      age: 25,
    });
  });

  it('should be able to retrieve the dirty value of the form', async () => {
    expect(formStore.getDirtyValue()).toEqual({});

    inputStoreName.setValue('Ben');
    await nextTick();
    expect(formStore.getDirtyValue()).toEqual({ name: 'Ben' });

    inputStoreAge.setValue(26);
    await nextTick();
    expect(formStore.getDirtyValue()).toEqual({ name: 'Ben', age: 26 });
  });

  it('should be able to retrieve if the form is dirty', async () => {
    expect(formStore.isDirty()).toBe(false);

    inputStoreName.setValue('Ben');
    await nextTick();
    expect(formStore.isDirty()).toBe(true);
  });

  it('should be able to retrieve if the form is pristine', async () => {
    expect(formStore.isPristine()).toBe(true);

    inputStoreName.setValue('Ben');
    await nextTick();
    expect(formStore.isPristine()).toBe(false);
  });

  it('should be able to retrieve if the form is valid', async () => {
    expect(formStore.isValid()).toBe(true);

    inputStoreName.setValue('Be');
    await nextTick();
    expect(formStore.isValid()).toBe(false);

    inputStoreName.setValue('Ben');
    await nextTick();
    expect(formStore.isValid()).toBe(true);
  });

  it('should be able to remove an input store', () => {
    formStore.deleteInput('name');
    expect(formStore.getInputStore('name')).toBeUndefined();
  });

  it('should be able to reset', async () => {
    inputStoreName.setValue('Ben');
    await nextTick();
    expect(formStore.isPristine()).toBe(false);

    formStore.reset();
    await nextTick();

    expect(formStore.isPristine()).toBe(true);
  });

  it('should be able to reset submit validation errors', async () => {
    formStore.addSubmitValidationError('Some error', 'name');
    formStore.addSubmitValidationError('Some error', '');

    expect(inputStoreName.submitValidationErrors).toEqual(['Some error']);

    formStore.resetSubmitValidationErrors();

    expect(inputStoreName.submitValidationErrors).toEqual([]);
  });

  it('should be able to tell all input stores to display errors', async () => {
    vi.spyOn(inputStoreName, 'markAsShouldDisplayErrors');
    vi.spyOn(inputStoreAge, 'markAsShouldDisplayErrors');

    formStore.markAsShouldDisplayErrors();

    expect(inputStoreName.markAsShouldDisplayErrors).toHaveBeenCalled();
    expect(inputStoreAge.markAsShouldDisplayErrors).toHaveBeenCalled();
  });
});
