<script setup lang="ts">
withDefaults(
  defineProps<{
    type?: 'button' | 'submit' | 'reset'
    variant?: 'primary' | 'secondary' | 'ghost' | 'danger'
    disabled?: boolean
  }>(),
  {
    type: 'button',
    variant: 'primary',
    disabled: false,
  },
)
</script>

<template>
  <button
    :type="type"
    :disabled="disabled"
    class="inline-flex h-10 min-w-0 items-center justify-center gap-2 whitespace-nowrap rounded-lg px-4 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-60"
    :class="{
      'bg-[#6FF6A5] text-[#0F172A] shadow-sm hover:bg-[#5DE091]': variant === 'primary',
      'border border-[#E2E8F0] bg-white text-[#0F172A] hover:bg-slate-50': variant === 'secondary',
      'bg-transparent text-slate-600 hover:bg-slate-100 hover:text-[#0F172A]': variant === 'ghost',
      'bg-rose-600 text-white hover:bg-rose-700': variant === 'danger',
    }"
  >
    <slot />
  </button>
</template>
