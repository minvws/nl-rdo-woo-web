<script setup>
import Icon from '@admin-fe/component/Icon.vue';
import InputErrors from './InputErrors.vue';

const emit = defineEmits(['delete']);

const props = defineProps({
  areErrorsVisible: {
    type: Boolean,
    default: false,
  },
  canDelete: {
    type: Boolean,
    default: false,
  },
  errors: {
    type: Array,
    default: () => [],
  },
  id: {
    type: String,
    required: true,
  },
  label: {
    type: String,
    required: true,
  },
});

const validatorMessages = {
  forbidden: () => 'Deze waarde is meerdere keren ingevuld.',
};

const onDelete = () => {
  emit('delete');
};
</script>

<template>
  <div>
    <label class="sr-only" :for="props.id">{{ label }}</label>

    <InputErrors
      class="mt-2"
      :errors="props.errors"
      :inputId="inputId"
      :validator-messages="validatorMessages"
      :value="value"
      v-if="props.areErrorsVisible"
    />

    <div class="flex">
      <div class="grow">
        <slot />
      </div>

      <button
        v-if="props.canDelete"
        @click="onDelete"
        class="bhr-button cursor-pointer shadow-none text-bhr-independence hover-focus:text-bhr-maximum-red"
        type="button"
      >
        <span class="sr-only">Verwijder {{ props.label }}</span>
        <Icon color="fill-current" name="trash-bin" :size="18" />
      </button>
    </div>
  </div>
</template>
