/** Domínio base dos tenants, deduzido do host do painel (admin.nossaapp.pt -> nossaapp.pt). Só para UI. */
export function tenantBaseDomain() {
  return window.location.hostname.split('.').slice(1).join('.');
}

export function tenantUrl(domain) {
  const port = window.location.port ? `:${window.location.port}` : '';
  return `${window.location.protocol}//${domain}${port}`;
}
