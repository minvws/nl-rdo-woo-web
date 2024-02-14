<script setup>
  import InputFile from '@admin-fe/component/form/InputFile.vue';

  const props = defineProps({
    endpoint: {
      type: String,
      required: false,
    },
  })
</script>

<template>
  <InputFile
    :allowed-mime-types="['application/zip', 'application/x-7z-compressed', 'application/pdf']"
    :allow-multiple="false"
    :endpoint="props.endpoint"
    :max-file-size="1024 * 1024 * 1024 * 16"
    helpText="De documenten die met dit besluit openbaar worden gemaakt upload je in de volgende stap. Voeg hier alleen bijlagen toe die te maken hebben met de totstandkoming van het besluit."
    label="Andere officiële bijlagen"
    name="attachment"
    tip="Tip: je kunt meerdere documenten tegelijkertijd uploaden. Sleep je hele selectie (of een zip-bestand) naar dit venster."
  />
</template>
