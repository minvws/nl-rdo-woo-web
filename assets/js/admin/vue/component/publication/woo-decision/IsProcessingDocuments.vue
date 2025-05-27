<script setup lang="ts">
import Alert from '@admin-fe/component/Alert.vue';
import { useTemplateRef } from 'vue';

const wrapperElement = useTemplateRef('wrapperElement');

defineExpose({
  setFocus: () => wrapperElement.value?.focus(),
});
</script>

<template>
  <div
    class="mb-4"
    tabindex="-1"
    ref="wrapperElement"
    data-e2e-name="upload-busy"
  >
    <Alert type="info">
      <h3 class="font-bold">
        Wacht niet langer meer na het uploaden van je bestanden
      </h3>

      <template #extra>
        We zijn nu bezig met het verwerken van de documenten. Dit kan soms wat
        tijd in beslag nemen. Je kunt hier later terugkomen, de documenten
        worden op de achtergrond verwerkt.
      </template>
    </Alert>
  </div>
</template>
