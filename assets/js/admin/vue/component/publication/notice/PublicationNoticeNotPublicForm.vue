<script setup lang="ts">
import Alert from '@admin-fe/component/Alert.vue';
import Form from '@admin-fe/component/form/Form.vue';
import FormButton from '@admin-fe/component/form/FormButton.vue';
import InputText from '@admin-fe/component/form/InputText.vue';
import InputTextarea from '@admin-fe/component/form/InputTextarea.vue';
import { useFormStore } from '@admin-fe/composables';
import type { FormValue } from '@admin-fe/form/interface';
import { computed, nextTick, ref, watch } from 'vue';
import InputDate from '../file/input/InputDate.vue';
import InputGrounds from '../file/input/InputGrounds.vue';
import { noticeSchema, type GroundOptions, type Notice } from './interface';

interface Props {
  endpoint: string;
  groundOptions: GroundOptions;
  isEditMode?: boolean;
  notice: Notice | null;
}

interface Emits {
  cancel: [];
  saved: [Notice];
}

const props = withDefaults(defineProps<Props>(), {
  isEditMode: false,
});

const emit = defineEmits<Emits>();

const hasSubmitError = ref(false);
const hasDeleteError = ref(false);

const createEmptyNotice = (): Notice => ({
  id: '',
  dossier: { id: '' },
  documentName: null,
  formalDate: '',
  grounds: [],
  explanation: null,
});

const currentNotice = ref<Notice>(props.notice || createEmptyNotice());

const documentName = computed(() => currentNotice.value.documentName || '');
const formalDate = computed(() => currentNotice.value.formalDate);
const grounds = computed(() => currentNotice.value.grounds);
const explanation = computed(() => currentNotice.value.explanation || '');

const saveButtonText = computed(() =>
  props.isEditMode
    ? 'Opslaan en mededeling bijwerken'
    : 'Opslaan en mededeling toevoegen',
);

const unsetError = () => {
  hasSubmitError.value = false;
  hasDeleteError.value = false;
};

const cancel = () => {
  emit('cancel');
  unsetError();
};

const onSubmit = (formValue: FormValue) => {
  unsetError();

  return fetch(props.endpoint, {
    body: JSON.stringify(formValue),
    headers: { 'Content-Type': 'application/json', accept: 'application/json' },
    method: props.isEditMode ? 'PUT' : 'POST',
  });
};

const onSubmitSuccess = (notice: unknown) => {
  emit('saved', notice as Notice);
};

const onSubmitError = () => {
  hasSubmitError.value = true;
};

const formStore = useFormStore(onSubmit, noticeSchema);

watch(
  () => [props.notice, props.isEditMode],
  async () => {
    currentNotice.value = props.notice || createEmptyNotice();
    unsetError();

    await nextTick();
    formStore.reset();
  },
);
</script>

<template>
  <Form
    @pristineSubmit="cancel"
    @submitError="onSubmitError"
    @submitSuccess="onSubmitSuccess"
    :store="formStore"
  >
    <InputText
      :value="documentName"
      label="Documentnaam"
      name="documentName"
      :required="false"
    />

    <InputDate :value="formalDate" />

    <InputGrounds
      :minLength="1"
      :options="props.groundOptions"
      :values="grounds"
    />

    <InputTextarea
      :value="explanation"
      label="Toelichting"
      name="explanation"
      :required="false"
    />

    <div v-if="hasSubmitError" class="mb-6" data-e2e-name="save-failed">
      <Alert type="warning">
        Het opslaan van de mededeling is mislukt. Probeer het later opnieuw.
      </Alert>
    </div>

    <div v-if="hasDeleteError" class="mb-6" data-e2e-name="delete-failed">
      <Alert type="warning">
        Het verwijderen van de mededeling is mislukt. Probeer het later opnieuw.
      </Alert>
    </div>

    <FormButton>{{ saveButtonText }}</FormButton>
    <FormButton @click="cancel" :is-secondary="true">Annuleren</FormButton>
  </Form>
</template>
