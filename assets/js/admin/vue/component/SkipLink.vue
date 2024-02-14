<script setup>
  const props = defineProps({
    href: {
      type: String,
      required: true,
      default: '',
    },
    id: {
      type: String,
      required: true,
    },
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
