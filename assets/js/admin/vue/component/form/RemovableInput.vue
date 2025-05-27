<script setup lang="ts">
import Icon from '@admin-fe/component/Icon.vue';
import type { ValidatorMessages } from '@admin-fe/form';
import type { InputValidationErrors } from '@admin-fe/form/interface';
import InputErrors from './InputErrors.vue';

interface Props {
  areErrorsVisible: boolean;
  canDelete: boolean;
  errors: InputValidationErrors;
  id: string;
  label: string;
}

interface Emits {
  delete: [];
}

const emit = defineEmits<Emits>();

const props = withDefaults(defineProps<Props>(), {
  areErrorsVisible: false,
  canDelete: false,
  errors: () => [],
});

const validatorMessages: ValidatorMessages = {
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
      :inputId="props.id"
      :validator-messages="validatorMessages"
      v-if="props.areErrorsVisible"
    />

    <div class="flex">
      <div class="grow mr-2">
        <slot />
      </div>

      <button
        v-if="props.canDelete"
        @click="onDelete"
        class="bhr-btn-ghost-danger w-10 h-10 mt-0.5"
        type="button"
      >
        <span class="sr-only">Verwijder {{ props.label }}</span>
        <Icon color="fill-current" name="trash-bin" :size="24" />
      </button>
    </div>
  </div>
</template>
