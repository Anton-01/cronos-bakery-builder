import { ref, type Ref } from 'vue'

interface ValidationErrors {
  [field: string]: string[]
}

const validationErrors: Ref<ValidationErrors> = ref({})

export function useValidationErrors() {
  function setErrors(errors: ValidationErrors) {
    validationErrors.value = errors
  }

  function clearErrors() {
    validationErrors.value = {}
  }

  function clearField(field: string) {
    const copy = { ...validationErrors.value }
    delete copy[field]
    validationErrors.value = copy
  }

  function getError(field: string): string | null {
    return validationErrors.value[field]?.[0] ?? null
  }

  function hasError(field: string): boolean {
    return !!validationErrors.value[field]?.length
  }

  return {
    validationErrors,
    setErrors,
    clearErrors,
    clearField,
    getError,
    hasError,
  }
}
