import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick, ref } from 'vue';
import { minLength } from '../form/validator';
import { useInputStore } from './input-store';
import { useMultiInputStore } from './multi-input-store';

describe('the "useMultiInputStore" composable', () => {
  let multiInputStore: ReturnType<typeof useMultiInputStore>;
  let inputStoreName: ReturnType<typeof useInputStore>;
  let inputStoreAge: ReturnType<typeof useInputStore>;

  beforeEach(() => {
    multiInputStore = useMultiInputStore(
      'person',
      'Person',
      ref({ name: 'John', age: 25 }),
    );

    inputStoreName = useInputStore('name', 'Name', ref('John'), [minLength(3)]);
    inputStoreAge = useInputStore('age', 'Age', ref(25), [minLength(3)]);

    multiInputStore.addInputStore(inputStoreName);
    multiInputStore.addInputStore(inputStoreAge);
  });

  it('should be able to retrieve the value of the store', () => {
    expect(multiInputStore.value).toEqual({
      name: 'John',
      age: 25,
    });
  });

  it('should be able to add an input store', () => {
    const inputStoreHobbies = useInputStore(
      'hobbies',
      'Hobbies',
      ref(['Reading', 'Writing']),
    );
    multiInputStore.addInputStore(inputStoreHobbies);

    expect(multiInputStore.findInputStore('hobbies')).toEqual(
      inputStoreHobbies,
    );
    expect(multiInputStore.findInputStore('non-existing')).toBeUndefined();
  });

  it('should be able to remove an input store', () => {
    multiInputStore.removeInputStore(inputStoreName);

    expect(multiInputStore.findInputStore('name')).toBeUndefined();
  });

  it('should be able to retrieve if one of the input stores has visible errors', async () => {
    inputStoreName.setValue('Be');
    await nextTick();
    inputStoreName.markAsTouched();

    expect(multiInputStore.hasVisibleErrors).toBe(true);
  });

  it('should be able to retrieve the errors of the store', async () => {
    inputStoreName.setValue('Be');
    await nextTick();

    expect(multiInputStore.errors).toEqual([
      {
        actualLength: 2,
        id: 'minLength',
        minLength: 3,
        tooLittleLength: 1,
      },
    ]);
  });

  it('should be able to retrieve if the store is dirty', async () => {
    expect(multiInputStore.isDirty).toBe(false);

    inputStoreName.setValue('Ben');
    await nextTick();

    expect(multiInputStore.isDirty).toBe(true);

    multiInputStore.reset();
    expect(multiInputStore.isDirty).toBe(false);

    multiInputStore.makeDirty();
    expect(multiInputStore.isDirty).toBe(true);
  });

  it('should be able to retrieve if the form is invalid', async () => {
    expect(multiInputStore.isInvalid).toBe(false);

    inputStoreName.setValue('Be');
    await nextTick();

    expect(multiInputStore.isInvalid).toBe(true);
  });

  it('should be able to retrieve if the form is valid', async () => {
    expect(multiInputStore.isValid).toBe(true);

    inputStoreName.setValue('Be');
    await nextTick();

    expect(multiInputStore.isValid).toBe(false);
  });

  it('should be able to retrieve if the form is pristine', async () => {
    expect(multiInputStore.isPristine).toBe(true);

    inputStoreName.setValue('Ben');
    await nextTick();

    expect(multiInputStore.isPristine).toBe(false);
  });

  it('should be able to retrieve if the form is touched', async () => {
    expect(multiInputStore.isTouched).toBe(false);

    inputStoreName.markAsTouched();

    expect(multiInputStore.isTouched).toBe(true);
  });

  it('should be able to retrieve if the form is dirty', async () => {
    expect(multiInputStore.isDirty).toBe(false);

    inputStoreName.setValue('Ben');
    await nextTick();

    expect(multiInputStore.isDirty).toBe(true);
  });

  it('should be able to retrieve the name and label of the store', async () => {
    expect(multiInputStore.name).toEqual('person');
    expect(multiInputStore.label).toEqual('Person');
  });

  it('should be able to add, retrieve and reset the submit validation errors of the store', async () => {
    multiInputStore.addSubmitValidationError('Some error', 'name');
    multiInputStore.addSubmitValidationError('Another error', 'non-existing');
    multiInputStore.addSubmitValidationError('Another error', '');

    expect(multiInputStore.submitValidationErrors).toEqual({
      age: [],
      name: ['Some error'],
    });

    multiInputStore.resetSubmitValidationErrors();

    expect(multiInputStore.submitValidationErrors).toEqual({
      age: [],
      name: [],
    });
  });

  it('should be able to tell all input stores to display errors', async () => {
    vi.spyOn(inputStoreName, 'markAsShouldDisplayErrors');
    vi.spyOn(inputStoreAge, 'markAsShouldDisplayErrors');

    multiInputStore.markAsShouldDisplayErrors();

    expect(inputStoreName.markAsShouldDisplayErrors).toHaveBeenCalled();
    expect(inputStoreAge.markAsShouldDisplayErrors).toHaveBeenCalled();
  });

  it('should be able to mark all stores as touched', async () => {
    vi.spyOn(inputStoreName, 'markAsTouched');
    vi.spyOn(inputStoreAge, 'markAsTouched');

    multiInputStore.markAsTouched();

    expect(inputStoreName.markAsTouched).toHaveBeenCalled();
    expect(inputStoreAge.markAsTouched).toHaveBeenCalled();
  });
});
