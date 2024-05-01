<script setup>
  import { provide, ref } from 'vue';
  import  Pending from '../Pending.vue';
  import { isSuccessStatusCode } from '@js/admin/utils';

  const props = defineProps({
    action: {
      type: String,
      required: true
    },
    method: {
      type: String,
      required: false,
      default: 'GET'
    },
    store: {
      type: Object,
      required: false,
      default: () => ({}),
    },
  });

  const emit = defineEmits(['pristineSubmit', 'submitError', 'submitSuccess']);
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
      const response = await props.store.submit(props.store.getValue(), props.store.getDirtyValue());
      const data = await response.json();

      isSubmitting.value = false;
      if (isSuccessStatusCode(response.status)) {
        emit('submitSuccess', data);
        return;
      }

      if (response.status === 422) {
        (data.violations || []).forEach((violation) => {
          props.store.addSubmitValidationError(violation.propertyPath, violation.message);
        });
        return;
      }

      emit('submitError', data);
    }
  }

  provide('form', {
    addInput: (inputStore) => {
      props.store.addInput(inputStore);
    },
  });
</script>

<template>
  <form @submit.prevent="onSubmit" :action="props.action" :method="props.method" novalidate>
    <Pending :isPending="isSubmitting">
      <slot />
    </Pending>
  </form>
</template>
