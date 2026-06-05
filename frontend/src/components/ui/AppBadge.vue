<script setup lang="ts">
withDefaults(
  defineProps<{
    tone?: 'success' | 'warning' | 'neutral' | 'danger'
  }>(),
  {
    tone: 'neutral',
  },
)
</script>

<template>
  <span
    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold"
    :class="{
      'bg-emerald-50 text-emerald-700': tone === 'success',
      'bg-amber-50 text-amber-700': tone === 'warning',
      'bg-slate-100 text-slate-700': tone === 'neutral',
      'bg-rose-50 text-rose-700': tone === 'danger',
    }"
  >
    <slot />
  </span>
</template>
