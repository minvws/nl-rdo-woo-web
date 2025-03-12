import { beforeEach, describe, expect, it } from 'vitest';
import { nextTick, ref } from 'vue';
import { z } from 'zod';
import { useFormStore } from './form-store';
import { useInputStore } from './input-store';
import { minLength } from '../form/validator';

describe('the "useFormStore" composable', () => {
  let store: ReturnType<typeof useFormStore>;
  let inputStoreName: ReturnType<typeof useInputStore>;
  let inputStoreAge: ReturnType<typeof useInputStore>;

  beforeEach(() => {
    store = useFormStore(() => Promise.resolve(new Response()), z.object({}));
    inputStoreName = useInputStore('name', 'Name', ref('John'), [minLength(3)]);
    inputStoreAge = useInputStore('age', 'Age', ref(25));

    store.addInput(inputStoreName);
    store.addInput(inputStoreAge);
  });

  it('should be able to retrieve the value of the form', () => {
    expect(store.getValue()).toEqual({
      name: 'John',
      age: 25,
    });
  });

  it('should be able to retrieve the dirty value of the form', async () => {
    expect(store.getDirtyValue()).toEqual({});

    inputStoreName.setValue('Ben');
    await nextTick();
    expect(store.getDirtyValue()).toEqual({ name: 'Ben' });

    inputStoreAge.setValue(26);
    await nextTick();
    expect(store.getDirtyValue()).toEqual({ name: 'Ben', age: 26 });
  });

  it('should be able to retrieve if the form is dirty', async () => {
    expect(store.isDirty()).toBe(false);

    inputStoreName.setValue('Ben');
    await nextTick();
    expect(store.isDirty()).toBe(true);
  });

  it('should be able to retrieve if the form is pristine', async () => {
    expect(store.isPristine()).toBe(true);

    inputStoreName.setValue('Ben');
    await nextTick();
    expect(store.isPristine()).toBe(false);
  });

  it('should be able to retrieve if the form is valid', async () => {
    expect(store.isValid()).toBe(true);

    inputStoreName.setValue('Be');
    await nextTick();
    expect(store.isValid()).toBe(false);

    inputStoreName.setValue('Ben');
    await nextTick();
    expect(store.isValid()).toBe(true);
  });
});
