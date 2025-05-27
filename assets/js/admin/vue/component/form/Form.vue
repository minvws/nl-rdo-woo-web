<script setup lang="ts">
import type { FormStore } from '@admin-fe/composables/form-store';
import type { InputStore } from '@admin-fe/composables/input-store';
import { isSuccessStatusCode, validateData } from '@js/admin/utils';
import { provide, ref } from 'vue';
import Pending from '../Pending.vue';

interface Props {
  action?: string;
  method?: string;
  store: FormStore;
}

interface Emits {
  pristineSubmit: [];
  submitError: [unknown];
  submitSuccess: [unknown];
}

const props = withDefaults(defineProps<Props>(), {
  method: 'GET',
});

const emit = defineEmits<Emits>();

const isSubmitting = ref(false);

const onSubmit = async () => {
  if (isSubmitting.value) {
    return;
  }
  props.store.markAsShouldDisplayErrors();

  if (props.store.isValid() && props.store.isPristine()) {
    emit('pristineSubmit');
    return;
  }

  if (props.store.isValid()) {
    props.store.resetSubmitValidationErrors();

    isSubmitting.value = true;
    const response = await props.store.submit(
      props.store.getValue(),
      props.store.getDirtyValue(),
    );
    const json = await response.json();
    let data;
    try {
      data = validateData(json, props.store.submitResponseSchema);
    } catch (error) {
      emit('submitError', error);
      return;
    } finally {
      isSubmitting.value = false;
    }

    if (isSuccessStatusCode(response.status)) {
      emit('submitSuccess', data);
      return;
    }

    if (response.status === 422) {
      (data.violations || []).forEach(
        (violation: { propertyPath: string; message: string }) => {
          props.store.addSubmitValidationError(
            violation.message,
            violation.propertyPath,
          );
        },
      );
      return;
    }

    emit('submitError', data);
  }
};

provide('form', {
  addInput: (inputStore: InputStore) => {
    props.store.addInput(inputStore);
  },
});
</script>

<template>
  <form
    @submit.prevent="onSubmit"
    :action="props.action"
    :method="props.method"
    novalidate
  >
    <Pending :isPending="isSubmitting">
      <slot />
    </Pending>
  </form>
</template>
