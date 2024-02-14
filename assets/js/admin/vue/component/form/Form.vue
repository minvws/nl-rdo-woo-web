<script setup>
  import { provide, ref } from 'vue';
  import  Pending from '../Pending.vue';

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
      isSubmitting.value = true;
      const response = await props.store.submit(props.store.getValue());
      isSubmitting.value = false;
      if (Math.round(Math.random() * 100) < 80) {
        emit('submitSuccess', response);
      } else {
        emit('submitError', response);
      }
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
