<script setup lang="ts">
import InputFile from '@admin-fe/component/form/InputFile.vue';
import { validators } from '@admin-fe/form';
import type { FileInfo } from '@admin-fe/component/form/interface';

interface Props {
  allowedFileTypes: string[];
  allowedMimeTypes: string[];
  displayMaxOneFileMessage?: boolean;
  fileInfo: FileInfo | null;
  groupId: string;
}

interface Emits {
  uploaded: [File];
  uploadError: [];
}

const props = withDefaults(defineProps<Props>(), {
  allowedFileTypes: () => [],
  allowedMimeTypes: () => [],
  displayMaxOneFileMessage: false,
  fileInfo: null,
  groupId: '',
});

const emit = defineEmits<Emits>();

const helpText = props.displayMaxOneFileMessage
  ? 'Je kunt maximaal 1 bestand uploaden'
  : undefined;
</script>

<template>
  <InputFile
    @uploaded="(file: File) => emit('uploaded', file)"
    @uploadError="() => emit('uploadError')"
    :allowed-file-types="props.allowedFileTypes"
    :allowed-mime-types="props.allowedMimeTypes"
    :enable-auto-upload="true"
    :help-text="helpText"
    :payload="{ groupId: props.groupId }"
    :uploaded-file-info="props.fileInfo"
    :validators="[validators.required()]"
    label="Bestand"
    name="uploadUuid"
  />
</template>
