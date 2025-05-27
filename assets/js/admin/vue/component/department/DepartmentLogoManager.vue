<script setup lang="ts">
import Alert from '@admin-fe/component/Alert.vue';
import Icon from '@admin-fe/component/Icon.vue';
import UploadArea from '@admin-fe/component/file/upload/UploadArea.vue';
import type { UploadSuccessData } from '@js/admin/utils';
import { ref } from 'vue';

interface Props {
  deleteEndpoint: string;
  logoEndpoint: string | null;
  uploadEndpoint: string;
}

const props = defineProps<Props>();
const logoEndpoint = ref<string | null>(props.logoEndpoint);
const shouldDisplayLogoAddedMessage = ref(false);
const shouldDisplayLogoRemovedMessage = ref(false);

const removeLogo = async () => {
  await fetch(props.deleteEndpoint, {
    method: 'DELETE',
  });

  shouldDisplayLogoAddedMessage.value = false;
  shouldDisplayLogoRemovedMessage.value = true;
  logoEndpoint.value = null;
};

const onUploaded = (
  _file: File,
  _uploadId: string,
  uploadSuccessData: UploadSuccessData<
    Record<'department', { asset_endpoint: string }>
  >,
) => {
  shouldDisplayLogoRemovedMessage.value = false;
  shouldDisplayLogoAddedMessage.value = true;
  logoEndpoint.value = uploadSuccessData.department.asset_endpoint;
};
</script>

<template>
  <template v-if="logoEndpoint">
    <div v-if="shouldDisplayLogoAddedMessage" class="pb-6">
      <Alert type="success">
        <p>Logo opgeslagen</p>
      </Alert>
    </div>

    <img class="mx-auto max-w-[80%]" :src="logoEndpoint" alt="Logo" />

    <div class="pt-8">
      <button @click="removeLogo" class="bhr-file__delete" type="button">
        <Icon color="fill-current" :size="18" name="trash-bin" />
        <span class="ml-2">Verwijder logo</span>
      </button>
    </div>
  </template>

  <template v-else>
    <label class="sr-only" for="file">Logo</label>

    <div v-if="shouldDisplayLogoRemovedMessage" class="pb-6">
      <Alert type="success">
        <p>Logo verwijderd</p>
      </Alert>
    </div>

    <UploadArea
      @uploaded="onUploaded"
      :allowed-file-types="['SVG']"
      :allowed-mime-types="['image/svg+xml']"
      :enable-auto-upload="true"
      :endpoint="props.uploadEndpoint"
      :max-file-size="1024 * 1024 * 10"
      id="file"
      name="file"
      :payload="{ groupId: 'department' }"
    />
  </template>
</template>
