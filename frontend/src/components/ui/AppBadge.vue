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
    class="inline-flex min-w-[6.5rem] items-center justify-center rounded-full px-3 py-1 text-center text-xs font-semibold whitespace-nowrap sm:min-w-[7rem]"
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
