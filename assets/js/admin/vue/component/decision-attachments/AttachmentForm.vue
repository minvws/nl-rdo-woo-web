<script setup>
  import { computed, nextTick, ref, watch } from 'vue';

  import Form from '@admin-fe/component/form/Form.vue';
  import FormButton from '@admin-fe/component/form/FormButton.vue';
  import InputSelect from '@admin-fe/component/form/InputSelect.vue';
  import InputText from '@admin-fe/component/form/InputText.vue';
  import { validators } from '@admin-fe/form';
  import { useFormStore } from '@admin-fe/composables';

  const emit = defineEmits(['cancel', 'saved']);

  const props = defineProps({
    attachment: {
      type: Object,
      required: true,
    },
    documentTypes: {
      type: Array,
      required: true,
      default: () => [],
    },
    isEditMode: {
      type: Boolean,
      default: false,
    },
  });

  const attachmentId = computed(() => props.attachment.id);
  const fileName = computed(() => props.attachment.fileName);
  const documentType = computed(() => props.attachment.documentType);
  const date = computed(() => props.attachment.date);

  const documentTypeOptions = props.documentTypes.map((documentType) => ({
    value: documentType.id,
    label: documentType.label,
  }));

  const cancel = () => {
    emit('cancel');
  }

  const onSubmit = (formValue) => {
    if (props.isEditMode) {
      return new Promise((resolve, reject) => {
        setTimeout(() => {
          resolve({ ...formValue, id: attachmentId.value, mimeType: 'application/pdf', fileSize: 1024 * 1024 });
        }, 1500);
      });
    }

    return new Promise((resolve, reject) => {
      setTimeout(() => {
        resolve({ ...formValue, id: Math.round(Math.random() * 100), mimeType: 'application/pdf', fileSize: 1024 * 1024 });
      }, 1500);
    });
  }

  const onSubmitSuccess = (attachment) => {
    emit('saved', attachment);
  }

  const onSubmitError = (response) => {
    console.log('error', response);
  }

  const formStore = useFormStore(onSubmit);

  watch(() => props.attachment.id, async () => {
    await nextTick(); // wait for the inputs to be updated
    formStore.reset();
  });
</script>

<template>
  <Form
    @pristineSubmit="cancel"
    @submitError="onSubmitError"
    @submitSuccess="onSubmitSuccess"
    :store="formStore"
  >
    <InputText
      :validators="[
        validators.required(),
        validators.minLength(3),
      ]"
      :value="fileName"
      helpText="De bestandsnaam is zichtbaar voor bezoekers van de website. Verwijder namen, initialen en andere persoonsgegevens."
      label="Bestandsnaam"
      name="fileName"
    />

    <InputSelect
      :options="documentTypeOptions"
      :validators="[
        validators.required(),
      ]"
      :value="documentType"
      emptyLabel="Kies een documentsoort"
      helpText="Wat is de rol van dit document in het Woo-proces?"
      label="Soort document"
      name="documentType"
    />

    <InputText
      :validators="[
        validators.required(),
      ]"
      :value="date"
      class="sm:max-w-[50%]"
      helpText="Bij een brief of besluit: de datering die in het document wordt gebruikt."
      label="Formele datum bijlage"
      name="date"
      type="date"
    />

    <FormButton>Opslaan</FormButton>
    <FormButton @click="cancel" :is-secondary="true">Annuleren</FormButton>
  </Form>
</template>
