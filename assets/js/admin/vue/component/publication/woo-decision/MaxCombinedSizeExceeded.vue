<script setup lang="ts">
import Alert from '@admin-fe/component/Alert.vue';
import { formatFileSize } from '@js/admin/utils';
import { useTemplateRef } from 'vue';

interface Props {
  rejectEndpoint: string;
  maxSize: number;
}

interface Emits {
  rejected: [];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const buttonElement = useTemplateRef('buttonElement');

const reject = async () => {
  await fetch(props.rejectEndpoint, {
    method: 'POST',
  });

  emit('rejected');
};

defineExpose({
  setFocus: () => buttonElement.value?.focus(),
});
</script>

<template>
  <div class="mb-8" data-e2e-name="max-combined-size-exceeded">
    <Alert type="danger">
      De maximaal toegestane grootte van alle bestanden samen is overschreden.
      Upload de bestanden opnieuw en zorg ervoor dat ze samen niet groter zijn
      dan {{ formatFileSize(props.maxSize) }}.
    </Alert>
  </div>

  <button
    class="bhr-btn-bordered-primary"
    type="button"
    ref="buttonElement"
    @click="reject"
  >
    Bestanden opnieuw uploaden
  </button>
</template>
