<script setup>
  import Icon from '../Icon.vue';
  import { computed } from 'vue';

  const props = defineProps({
    errors: {
      type: Array,
      default: () => [],
      required: true
    },
  });

  const numberOfErrors = computed(() => props.errors.length);
  const hasErrors = computed(() => numberOfErrors.value > 0);
</script>

<template>
  <div class="flex pb-3" :id="id" v-if="hasErrors">
    <span class="mr-2">
      <Icon color="fill-bhr-maximum-red" name="exclamation" />
    </span>

    <div class="text-bhr-maximum-red">
      <ul v-if="numberOfErrors > 1" class="bhr-ul">
        <li v-for="error in errors" class="bhr-li">{{ error }}</li>
      </ul>
      <p v-else>{{ errors[0] }}</p>
    </div>
  </div>
</template>
