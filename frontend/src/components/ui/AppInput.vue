<script setup lang="ts">
defineProps<{
  id?: string
  label?: string
  modelValue: string
  type?: string
  placeholder?: string
  required?: boolean
  inputmode?: 'none' | 'text' | 'tel' | 'url' | 'email' | 'numeric' | 'decimal' | 'search'
  maxlength?: number
  error?: string
}>()

defineEmits<{
  'update:modelValue': [value: string]
}>()
</script>

<template>
  <label class="grid gap-1.5 text-sm font-medium text-slate-700" :for="id">
    <span v-if="label">{{ label }}</span>
    <input
      :id="id"
      :type="type ?? 'text'"
      :value="modelValue"
      :placeholder="placeholder"
      :required="required"
      :inputmode="inputmode"
      :maxlength="maxlength"
      :aria-invalid="Boolean(error)"
      class="h-10 rounded-lg border border-[#E2E8F0] bg-white px-3 text-sm text-[#0F172A] outline-none transition placeholder:text-slate-400 focus:border-[#6FF6A5] focus:ring-4 focus:ring-[#6FF6A5]/20 aria-[invalid=true]:border-rose-300 aria-[invalid=true]:focus:border-rose-400 aria-[invalid=true]:focus:ring-rose-100"
      @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
    />
    <span v-if="error" class="text-xs font-medium text-rose-600">{{ error }}</span>
  </label>
</template>
