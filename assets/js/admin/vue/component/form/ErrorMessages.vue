<script setup>
  import Icon from '../Icon.vue';

  const props = defineProps({
    id: {
      type: String,
    },
    messages: {
      type: Array,
      default: () => [],
      required: true
    },
  });
  const numberOfErrors = props.messages.length;
  const hasErrors = numberOfErrors > 0;
</script>

<template>
  <div class="flex pb-3" :id="id" v-if="hasErrors">
    <span class="mr-2">
        <Icon color="fill-bhr-maximum-red" name="exclamation" />
    </span>

    <div class="text-bhr-maximum-red">
        <ul v-if="numberOfErrors > 1" class="bhr-ul">
          <li v-for="message in props.messages" class="bhr-li">{{ message }}</li>
        </ul>
        <p v-else>{{ props.messages[0] }}</p>
      </div>
  </div>
</template>
