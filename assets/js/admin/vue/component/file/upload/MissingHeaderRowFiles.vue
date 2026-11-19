<script setup lang="ts">
import { computed } from 'vue';
import Alert from '../../Alert.vue';
import FilesList from './FilesList.vue';

interface Props {
  allowMultiple?: boolean;
  files?: File[];
}

const props = withDefaults(defineProps<Props>(), {
  files: () => [],
});

const numberOfFiles = computed(() => props.files.length);
const firstFile = computed(() => props.files[0]);
</script>

<template>
  <div
    v-if="numberOfFiles > 0"
    class="mb-4"
    data-e2e-name="missing-header-row-warning"
  >
    <Alert type="warning">
      <p>
        <template v-if="props.allowMultiple"
          >De eerste rij van het eerste werkblad in onderstaande bestanden bevat
          geen kolomnamen. Voeg kolomnamen toe aan de eerste rij en upload de
          bestanden opnieuw.</template
        >
        <template v-else
          >De eerste rij van het eerste werkblad in "{{ firstFile.name }}" bevat
          geen kolomnamen. Voeg kolomnamen toe aan de eerste rij en upload het
          bestand opnieuw.</template
        >
      </p>

      <template v-if="props.allowMultiple" #extra>
        <FilesList :files="props.files" />
      </template>
    </Alert>
  </div>
</template>
