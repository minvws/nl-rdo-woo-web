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
    data-e2e-name="multiple-worksheets-warning"
  >
    <Alert type="warning">
      <p>
        <template v-if="props.allowMultiple"
          >De onderstaande bestanden bevatten meerdere werkbladen. Verwijder de
          overige werkbladen, zodat er in ieder bestand één werkblad overblijft,
          en upload de bestanden opnieuw.</template
        >
        <template v-else
          >Het bestand "{{ firstFile.name }}" bevat meerdere werkbladen.
          Verwijder de overige werkbladen, zodat er één werkblad overblijft, en
          upload het bestand opnieuw.</template
        >
      </p>

      <template v-if="props.allowMultiple" #extra>
        <FilesList :files="props.files" />
      </template>
    </Alert>
  </div>
</template>
