<script setup lang="ts">
import Icon from '../Icon.vue';
import { computed } from 'vue';

interface Props {
  id?: string;
  messages: string[];
}

const props = withDefaults(defineProps<Props>(), {
  messages: () => [],
});

const numberOfMessages = computed(() => props.messages.length);
const hasMessages = computed(() => numberOfMessages.value > 0);
</script>

<template>
  <div class="flex pb-3" :id="id" v-if="hasMessages">
    <span class="mr-2">
      <Icon color="fill-bhr-maximum-red" name="exclamation-filled" />
    </span>

    <div class="text-bhr-maximum-red">
      <ul v-if="numberOfMessages > 1" class="bhr-ul">
        <li
          v-for="(message, index) in props.messages"
          :key="index"
          class="bhr-li"
        >
          {{ message }}
        </li>
      </ul>
      <p v-else>{{ props.messages[0] }}</p>
    </div>
  </div>
</template>
