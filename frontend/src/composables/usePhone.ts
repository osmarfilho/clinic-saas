export const PHONE_MIN_DIGITS = 10
export const PHONE_MAX_DIGITS = 11
export const PHONE_ERROR_MESSAGE = 'Telefone deve conter entre 10 e 11 dígitos.'

export function normalizePhone(value = '') {
  return value.replace(/\D/g, '').slice(0, PHONE_MAX_DIGITS)
}

export function validatePhone(value = '') {
  const digits = normalizePhone(value)

  return digits.length >= PHONE_MIN_DIGITS && digits.length <= PHONE_MAX_DIGITS
    ? ''
    : PHONE_ERROR_MESSAGE
}

export function formatPhone(value = '') {
  const digits = normalizePhone(value)

  if (!digits) return ''

  if (digits.length <= 10) {
    return digits
      .replace(/(\d{2})(\d)/, '($1) $2')
      .replace(/(\d{4})(\d)/, '$1-$2')
  }

  return digits
    .replace(/(\d{2})(\d)/, '($1) $2')
    .replace(/(\d{5})(\d)/, '$1-$2')
}
