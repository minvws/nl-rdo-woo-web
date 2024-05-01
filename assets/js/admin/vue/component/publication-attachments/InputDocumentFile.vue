<script setup>
  import InputFile from '@admin-fe/component/form/InputFile.vue';
  import { MimeTypes } from '@js/admin/utils';
  import { validators } from '@admin-fe/form';

  const props = defineProps({
    fileInfo: {
      type: String,
      required: false,
      default: '',
    },
    groupId: {
      type: String,
      default: '',
    },
  });

  const emit = defineEmits(['uploaded', 'uploadError', 'uploading']);
</script>

<template>
  <InputFile
    @uploaded="(file) => emit('uploaded', file)"
    @uploadError="() => emit('uploadError')"
    @uploading="() => emit('uploading')"
    :allowed-mime-types="MimeTypes.Pdf"
    :enable-auto-upload="true"
    :group-id="props.groupId"
    :uploaded-file-info="props.fileInfo"
    :validators="[
      validators.required(),
    ]"
    label="Bestand"
    name="uploadUuid"
  />
</template>
