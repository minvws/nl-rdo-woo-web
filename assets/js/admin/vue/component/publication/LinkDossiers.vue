<script setup lang="ts">
import Dialog from '@admin-fe/component/Dialog.vue';
import ErrorMessages from '@admin-fe/component/form/ErrorMessages.vue';
import type { PublicationSearchResult } from '@admin-fe/component/publication/interface';
import PublicationSearchAutocomplete from '@admin-fe/component/publication/search/PublicationSearchAutocomplete.vue';
import { computed, ref } from 'vue';

const props = withDefaults(
  defineProps<{
    endpoint: string;
    name: string;
    submitErrors: string[];
  }>(),
  {
    submitErrors: () => [],
  },
);

const isDialogOpen = ref(false);
const selectedDossiers = ref<Map<string | undefined, PublicationSearchResult>>(
  new Map(),
);
const selectedDossier = ref<PublicationSearchResult | null>(null);
const hasSelectedDossiers = computed(() => selectedDossiers.value.size > 0);
const publicationSearchAutocomplete =
  ref<InstanceType<typeof PublicationSearchAutocomplete>>();
const isFirstSelectDossierErrorVisible = ref(false);

const onSelect = (result: PublicationSearchResult) => {
  selectedDossier.value = result;
  resetFirstSelectDossierErrorVisibility();
};

const onAddDossier = () => {
  if (!selectedDossier.value) {
    isFirstSelectDossierErrorVisible.value = true;
    return;
  }

  selectedDossiers.value.set(selectedDossier.value.id, selectedDossier.value);
  closeDialog();
  publicationSearchAutocomplete.value?.reset();
  resetSelectedDossier();
};

const cancel = () => {
  closeDialog();
  resetSelectedDossier();
  resetFirstSelectDossierErrorVisibility();
};

const closeDialog = () => {
  isDialogOpen.value = false;
};

const resetSelectedDossier = () => {
  selectedDossier.value = null;
};

const resetFirstSelectDossierErrorVisibility = () => {
  isFirstSelectDossierErrorVisible.value = false;
};

const hasSubmitErrors = computed(() => props.submitErrors.length > 0);
</script>

<template>
  <div
    class="bhr-form-row"
    :class="{ 'bhr-form-row--invalid': hasSubmitErrors }"
  >
    <h2 class="bhr-label">Gepubliceerde besluiten</h2>

    <ErrorMessages :messages="props.submitErrors" />

    <div class="bhr-textarea mb-8">
      <output class="block mb-3">
        <ul v-if="hasSelectedDossiers" aria-label="Gekozen besluiten">
          <li v-for="[, dossier] in selectedDossiers" :key="dossier.id">
            {{ dossier.title }}
          </li>
        </ul>
        <p v-else>Nog niets gekozen</p>
      </output>

      <button
        @click="isDialogOpen = true"
        aria-haspopup="dialog"
        class="bhr-button bhr-button--secondary"
        data-e2e-name="inquiry-decision-selector"
        type="button"
      >
        + Kies besluit...
      </button>
    </div>
  </div>

  <select class="hidden" :name="props.name" multiple>
    <option
      v-for="[, dossier] in selectedDossiers"
      :key="dossier.id"
      :value="dossier.id"
      selected
    >
      {{ dossier.title }}
    </option>
  </select>

  <Dialog v-model="isDialogOpen" title="Kies een besluit">
    <div class="bhr-form-row">
      <PublicationSearchAutocomplete
        ref="publicationSearchAutocomplete"
        @select="onSelect"
        :endpoint="props.endpoint"
        label="Te koppelen besluiten"
      />
    </div>

    <ErrorMessages
      v-if="isFirstSelectDossierErrorVisible"
      :messages="['Selecteer eerst een besluit']"
    />

    <button
      @click="onAddDossier"
      class="bhr-button bhr-button--fixed-width mr-4 bhr-button--primary"
      type="button"
    >
      Koppelen
    </button>

    <button
      @click="cancel"
      class="bhr-button bhr-button--fixed-width mr-4 bhr-button--secondary"
      type="button"
    >
      Annuleren
    </button>
  </Dialog>
</template>
