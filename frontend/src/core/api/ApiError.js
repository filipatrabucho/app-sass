/**
 * Erro normalizado da API: { message, code, errors } + status HTTP.
 */
export default class ApiError extends Error {
  constructor({ status, message, code, errors }) {
    super(message || 'Erro inesperado');
    this.name = 'ApiError';
    this.status = status;
    this.code = code || 'UNKNOWN_ERROR';
    this.errors = errors || {};
  }

  get isValidation() {
    return this.status === 422;
  }

  get isUnauthorized() {
    return this.status === 401;
  }

  get isModuleNotLicensed() {
    return this.status === 403 && this.code === 'MODULE_NOT_LICENSED';
  }
}
