<script setup lang="ts">
  interface Props {
    href: string;
    id: string;
  }

  const props = withDefaults(defineProps<Props>(), {
    href: '',
  });

  const href = props.href.startsWith('#') ? props.href : `#${props.href}`;
</script>

<template>
  <a
    class="sr-only focus:not-sr-only focus:bhr-a focus:no-underline focus:inline-block focus:p-2"
    :href="href"
    :id="props.id"
  >
    <slot />
  </a>
</template>
