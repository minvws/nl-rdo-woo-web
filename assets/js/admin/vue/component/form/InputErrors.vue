<script setup>
  import Icon from '../Icon.vue';
  import { computed, watch } from 'vue';
  import { getErrorsId, validatorMessages as validatorMessageFunctions } from '@admin-fe/form';

  const props = defineProps({
    errors: {
      type: Array,
      default: () => [],
      required: true
    },
    inputId: {
      type: String,
      required: true
    },
    validatorMessages: {
      type: Object,
      default: () => ({}),
    },
    value: {
      type: [Array, Boolean, Number, Object, String],
    },
  });

  const errorMessages = computed(() => props.errors.map((error) => {
    const validatorMessage = props.validatorMessages[error.id] || (validatorMessageFunctions[error.id]);
    return validatorMessage ? validatorMessage(error, props.value) : null;
  }).filter(Boolean));
  const numberOfErrors = computed(() => errorMessages.value.length);
  const hasErrors = computed(() => numberOfErrors.value > 0);
  const id = getErrorsId(props.inputId);
</script>

<template>
  <div class="flex pb-3" :id="id" v-if="hasErrors">
    <span class="mr-2">
        <Icon color="fill-bhr-maximum-red" name="exclamation" />
    </span>

    <div class="text-bhr-maximum-red">
        <ul v-if="numberOfErrors > 1" class="bhr-ul">
          <li v-for="errorMessage in errorMessages" class="bhr-li">{{ errorMessage }}</li>
        </ul>
        <p v-else>{{ errorMessages[0] }}</p>
      </div>
  </div>
</template>
