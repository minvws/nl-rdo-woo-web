<script setup>
import InputFile from '@admin-fe/component/form/InputFile.vue';
import { validators } from '@admin-fe/form';

const props = defineProps({
  allowedFileTypes: {
    type: Array,
    required: true,
    default: () => [],
  },
  allowedMimeTypes: {
    type: Array,
    required: true,
    default: () => [],
  },
  displayMaxOneFileMessage: {
    type: Boolean,
    default: false,
  },
  fileInfo: {
    type: [Object, null],
    required: false,
    default: null,
  },
  groupId: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['uploaded', 'uploadError']);
const helpText = props.displayMaxOneFileMessage
  ? 'Je kunt maximaal 1 bestand uploaden'
  : undefined;
</script>

<template>
  <InputFile
    @uploaded="(file) => emit('uploaded', file)"
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
