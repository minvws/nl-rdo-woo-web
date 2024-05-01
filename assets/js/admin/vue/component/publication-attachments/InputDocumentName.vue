<script setup>
  import InputText from '@admin-fe/component/form/InputText.vue';
  import { validators } from '@admin-fe/form';

  const props = defineProps({
    value: {
      type: String,
      required: false,
      default: '',
    },
  });
</script>

<template>
  <InputText
    :is-disabled="props.isDisabled"
    :validators="[
      validators.required(),
    ]"
    :value="props.value"
    helpText="De bestandsnaam is zichtbaar voor bezoekers van de website. Verwijder namen, initialen en andere persoonsgegevens."
    is-disabled-message="Dit veld wordt weer ingeschakeld zodra het bestand is geüpload."
    label="Bestandsnaam"
    name="name"
  />
</template>
