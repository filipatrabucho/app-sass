/**
 * Converte erros 422 da API ({ campo: [msg] }) para o formato de Form.setFields do Ant Design.
 * Campos com ponto (admin.email) passam a caminhos (['admin', 'email']).
 */
export function toFormFields(errors = {}) {
  return Object.entries(errors).map(([name, messages]) => ({
    name: name.split('.').map((part) => (/^\d+$/.test(part) ? Number(part) : part)),
    errors: Array.isArray(messages) ? messages : [String(messages)],
  }));
}
