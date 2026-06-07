<script setup lang="ts">
import { computed } from 'vue'
import AppInput from './AppInput.vue'
import { formatPhone, normalizePhone } from '@/composables/usePhone'

const props = withDefaults(
  defineProps<{
    id?: string
    label?: string
    modelValue: string
    placeholder?: string
    required?: boolean
    error?: string
  }>(),
  {
    placeholder: '83999999999',
    required: false,
  },
)

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const displayValue = computed(() => formatPhone(props.modelValue))

function updateValue(value: string) {
  emit('update:modelValue', normalizePhone(value))
}

function handleBeforeInput(event: InputEvent) {
  if (event.inputType?.startsWith('insert') && event.data && /\D/.test(event.data)) {
    event.preventDefault()
  }
}

function handlePaste(event: ClipboardEvent) {
  const pasted = event.clipboardData?.getData('text') ?? ''

  event.preventDefault()
  emit('update:modelValue', normalizePhone(pasted))
}
</script>

<template>
  <AppInput
    v-bind="$attrs"
    :id="id"
    :label="label"
    :model-value="displayValue"
    :placeholder="placeholder"
    :required="required"
    :error="error"
    inputmode="numeric"
    autocomplete="tel"
    :maxlength="15"
    @update:model-value="updateValue"
    @beforeinput="handleBeforeInput"
    @paste="handlePaste"
  />
</template>
