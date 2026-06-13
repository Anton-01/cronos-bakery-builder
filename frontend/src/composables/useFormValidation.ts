import { ref, reactive, computed, watch, type Ref } from 'vue'
import { useValidationErrors } from './useValidationErrors'

type ValidationRule<T> = (value: T) => string | true

interface FieldConfig<T> {
  initial: T
  rules?: ValidationRule<T>[]
  label?: string
}

export function useFormValidation<
  TFields extends Record<string, FieldConfig<unknown>>
>(fieldConfigs: TFields) {
  type FormValues = { [K in keyof TFields]: TFields[K]['initial'] }

  const { validationErrors, setErrors, clearErrors, clearField, getError, hasError } = useValidationErrors()

  const form = reactive<Record<string, unknown>>({})
  const touched = reactive<Record<string, boolean>>({})
  const clientErrors = ref<Record<string, string | null>>({})

  for (const [key, config] of Object.entries(fieldConfigs)) {
    form[key] = config.initial
    touched[key] = false
    clientErrors.value[key] = null
  }

  function validateField(key: string): boolean {
    const config = fieldConfigs[key]
    if (!config?.rules) return true

    for (const rule of config.rules) {
      const result = rule(form[key])
      if (result !== true) {
        clientErrors.value[key] = result
        return false
      }
    }

    clientErrors.value[key] = null
    return true
  }

  function validateAll(): boolean {
    let valid = true
    for (const key of Object.keys(fieldConfigs)) {
      touched[key] = true
      if (!validateField(key)) valid = false
    }
    return valid
  }

  function fieldError(key: string): string | null {
    return getError(key) ?? clientErrors.value[key] ?? null
  }

  function fieldHasError(key: string): boolean {
    return hasError(key) || !!clientErrors.value[key]
  }

  function handleServerErrors(errors: Record<string, string[]>) {
    setErrors(errors)
  }

  function reset() {
    for (const [key, config] of Object.entries(fieldConfigs)) {
      form[key] = config.initial
      touched[key] = false
      clientErrors.value[key] = null
    }
    clearErrors()
  }

  function onBlur(key: string) {
    touched[key] = true
    validateField(key)
    clearField(key)
  }

  function onInput(key: string) {
    if (touched[key]) {
      validateField(key)
    }
    clearField(key)
  }

  const isValid = computed(() => {
    return Object.keys(fieldConfigs).every((key) => !fieldHasError(key))
  })

  return {
    form: form as FormValues,
    touched,
    isValid,
    validateAll,
    validateField,
    fieldError,
    fieldHasError,
    handleServerErrors,
    reset,
    onBlur,
    onInput,
  }
}

// Common validation rules
export const rules = {
  required(message = 'Este campo es obligatorio'): ValidationRule<unknown> {
    return (value) => {
      if (value === null || value === undefined || value === '') return message
      return true
    }
  },

  minLength(min: number, message?: string): ValidationRule<string> {
    return (value) => {
      if (value.length < min) return message ?? `Mínimo ${min} caracteres`
      return true
    }
  },

  maxLength(max: number, message?: string): ValidationRule<string> {
    return (value) => {
      if (value.length > max) return message ?? `Máximo ${max} caracteres`
      return true
    }
  },

  email(message = 'Email inválido'): ValidationRule<string> {
    return (value) => {
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) return message
      return true
    }
  },

  pattern(regex: RegExp, message = 'Formato inválido'): ValidationRule<string> {
    return (value) => {
      if (!regex.test(value)) return message
      return true
    }
  },
}
