<script setup lang="ts">
import InputFile from '@admin-fe/component/form/InputFile.vue';
import type { FileInfo } from '@admin-fe/component/form/interface';
import { validators } from '@admin-fe/form';
import type { FileUploadLimit } from '@js/admin/utils/file/interface';

interface Props {
  displayMaxOneFileMessage?: boolean;
  dossierId?: null | string;
  fileInfo: FileInfo | null;
  fileLimits: FileUploadLimit[];
  groupId: string;
}

interface Payload {
  groupId: string;
  dossierId?: string;
}

interface Emits {
  uploaded: [File];
  uploadError: [];
}

const props = withDefaults(defineProps<Props>(), {
  displayMaxOneFileMessage: false,
  fileInfo: null,
  fileLimits: () => [],
  groupId: '',
});

const payload: Payload = {
  groupId: props.groupId,
  dossierId: props.dossierId || undefined,
};

const emit = defineEmits<Emits>();

const helpText = props.displayMaxOneFileMessage
  ? 'Je kunt maximaal 1 bestand uploaden'
  : undefined;
</script>

<template>
  <InputFile
    @uploaded="(file: File) => emit('uploaded', file)"
    @uploadError="() => emit('uploadError')"
    :enable-auto-upload="true"
    :file-limits="props.fileLimits"
    :help-text="helpText"
    :payload="payload"
    :uploaded-file-info="props.fileInfo"
    :validators="[validators.required()]"
    label="Bestand"
    name="uploadUuid"
  />
</template>
