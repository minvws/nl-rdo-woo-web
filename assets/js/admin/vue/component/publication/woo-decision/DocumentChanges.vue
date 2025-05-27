<script setup lang="ts">
import Alert from '@admin-fe/component/Alert.vue';
import { computed, ref } from 'vue';

interface Props {
  add?: number;
  republish?: number;
  update?: number;

  confirmEndpoint: string;
  rejectEndpoint: string;
}

interface Emits {
  goBack: [];
}

const props = withDefaults(defineProps<Props>(), {
  add: 0,
  republish: 0,
  update: 0,
});

const emit = defineEmits<Emits>();

const hasChanges = computed(() =>
  [props.add, props.republish, props.update].some((value) => value > 0),
);

const isConfirmed = ref(false);
const isRejected = ref(false);

const goBack = () => emit('goBack');

const onConfirmAndGoBack = async () => {
  await fetch(props.confirmEndpoint, {
    method: 'POST',
  });

  goBack();
};

const onConfirm = async () => {
  await fetch(props.confirmEndpoint, {
    method: 'POST',
  });

  isConfirmed.value = true;
};

const onReject = async () => {
  await fetch(props.rejectEndpoint, {
    method: 'POST',
  });

  isRejected.value = true;
};
</script>

<template>
  <template v-if="isConfirmed">
    <div class="mb-8">
      <Alert type="success">De acties worden uitgevoerd.</Alert>
    </div>

    <button
      @click="goBack"
      class="bhr-btn-filled-primary"
      type="button"
      data-e2e-name="back-to-uploading"
    >
      Terug naar uploaden
    </button>
  </template>

  <template v-else-if="isRejected">
    <div class="mb-8">
      <Alert type="danger">De acties zijn geannuleerd.</Alert>
    </div>

    <button @click="goBack" class="bhr-btn-filled-primary" type="button">
      Terug naar uploaden
    </button>
  </template>

  <div v-else-if="hasChanges" class="bhr-content" data-e2e-name="has-changes">
    <p class="font-semibold">
      Weet je zeker dat je de volgende acties uit wilt voeren?
    </p>

    <ul>
      <li>
        {{ props.add }} document{{ props.add !== 1 ? 'en' : '' }} toevoegen
      </li>
      <li>
        {{ props.republish }} document{{ props.republish !== 1 ? 'en' : '' }}
        opnieuw publiceren
      </li>
      <li>
        {{ props.update }} document{{ props.update !== 1 ? 'en' : '' }}
        vervangen
      </li>
    </ul>

    <div class="mt-4">
      <button
        @click="onConfirm"
        class="bhr-btn-filled-primary mr-4"
        type="button"
        data-e2e-name="confirm-document-processing"
      >
        Ja, verwerk documenten
      </button>

      <button @click="onReject" class="bhr-btn-bordered-primary" type="button">
        Annuleren
      </button>
    </div>
  </div>

  <template v-else>
    <div class="mb-8">
      <Alert type="success">Documenten gecontroleerd</Alert>
    </div>

    <p class="mb-8">Er zijn geen acties om uit te voeren.</p>

    <button
      @click="onConfirmAndGoBack"
      class="bhr-btn-filled-primary"
      type="button"
    >
      Terug naar uploaden
    </button>
  </template>
</template>
