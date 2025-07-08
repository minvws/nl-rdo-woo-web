<script setup lang="ts">
import Alert from '@admin-fe/component/Alert.vue';
import Icon from '@admin-fe/component/Icon.vue';
import UploadArea from '@admin-fe/component/file/upload/UploadArea.vue';
import { ref } from 'vue';

interface Props {
  deleteEndpoint: string;
  logoEndpoint: string;
  uploadEndpoint: string;
  departmentId: string;
  hasLogo: boolean;
}

const props = defineProps<Props>();
const logoEndpoint = ref(props.logoEndpoint);
const shouldDisplayLogoAddedMessage = ref(false);
const shouldDisplayLogoRemovedMessage = ref(false);
const hasLogo = ref(props.hasLogo);
const payload: Record<string, string> = {
  groupId: 'department',
  departmentId: props.departmentId,
};

const removeLogo = async () => {
  await fetch(props.deleteEndpoint, {
    method: 'DELETE',
  });

  shouldDisplayLogoAddedMessage.value = false;
  shouldDisplayLogoRemovedMessage.value = true;
  hasLogo.value = false;
};

const updateCacheKey = (urlInput: string): string => {
  const dummyBase = 'https://example.nl';
  const url = new URL(urlInput, dummyBase);
  const params = url.searchParams;

  params.set('cacheKey', Date.now().toString());

  url.search = params.toString();

  return url.pathname + (url.search ? url.search : '');
};

const onUploaded = () => {
  shouldDisplayLogoRemovedMessage.value = false;
  shouldDisplayLogoAddedMessage.value = true;
  hasLogo.value = true;
  logoEndpoint.value = updateCacheKey(logoEndpoint.value);
};
</script>

<template>
  <template v-if="hasLogo">
    <div v-if="shouldDisplayLogoAddedMessage" class="pb-6">
      <Alert type="success">
        <p>Logo opgeslagen</p>
      </Alert>
    </div>

    <img class="mx-auto max-w-[80%]" :src="logoEndpoint" alt="Logo" />

    <div class="pt-8">
      <button
        @click="removeLogo"
        class="bhr-file__delete"
        type="button"
        data-e2e-name="remove-logo"
      >
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
      :payload="payload"
    />
  </template>
</template>
