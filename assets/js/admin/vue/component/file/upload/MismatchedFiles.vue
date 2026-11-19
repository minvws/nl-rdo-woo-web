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
    data-e2e-name="mismatched-files-warning"
  >
    <Alert type="warning">
      <p>
        <template v-if="props.allowMultiple"
          >De onderstaande bestanden zijn niet opgeslagen, omdat de
          bestandstypes niet overeenkomen met de extensie (bijvoorbeeld
          ".xlsx"). Mogelijk zijn de bestanden hernoemd. Upload de originele
          bestanden of sla ze opnieuw op in het juiste formaat.</template
        >
        <template v-else
          >Het bestand "{{ firstFile.name }}" is niet opgeslagen, omdat het
          bestandstype niet overeenkomt met de extensie (bijvoorbeeld ".xlsx").
          Mogelijk is het bestand hernoemd. Upload het originele bestand of sla
          het opnieuw op in het juiste formaat.</template
        >
      </p>

      <template v-if="props.allowMultiple" #extra>
        <FilesList :files="props.files" />
      </template>
    </Alert>
  </div>
</template>
