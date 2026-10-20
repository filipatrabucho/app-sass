import { createApiGateway } from '@/core/api/gateway';

const gateway = createApiGateway({ baseUrl: '/api/central' });

export const centralApi = {
  me: () => gateway.get('/auth/me'),
  login: (credentials) => gateway.post('/auth/login', credentials),
  logout: () => gateway.post('/auth/logout'),

  listModules: () => gateway.get('/modules'),

  listTenants: (params) => gateway.get('/tenants', { query: params }),
  getTenant: (id) => gateway.get(`/tenants/${id}`),
  createTenant: (payload) => gateway.post('/tenants', payload),
  updateTenant: (id, payload) => gateway.put(`/tenants/${id}`, payload),
  suspendTenant: (id) => gateway.post(`/tenants/${id}/suspend`),
  activateTenant: (id) => gateway.post(`/tenants/${id}/activate`),
  updateTenantModules: (id, modules) => gateway.put(`/tenants/${id}/modules`, { modules }),
};
