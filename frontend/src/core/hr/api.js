import gateway from '../api/gateway';

const crud = (base) => ({
  list: (params) => gateway.get(base, { query: params }),
  get: (id) => gateway.get(`${base}/${id}`),
  create: (values) => gateway.post(base, values),
  update: (id, values) => gateway.put(`${base}/${id}`, values),
  remove: (id) => gateway.delete(`${base}/${id}`),
});

export const employeesApi = {
  ...crud('/employees'),
  lookup: (params) => gateway.get('/employees/lookup', { query: params }),
  attachments: (id) => gateway.get(`/employees/${id}/attachments`),
  uploadAttachment: (id, file) => {
    const body = new FormData();
    body.append('file', file);
    return gateway.post(`/employees/${id}/attachments`, body);
  },
  removeAttachment: (id, attachmentId) => gateway.delete(`/employees/${id}/attachments/${attachmentId}`),
};

export const departmentsApi = crud('/departments');
export const positionsApi = crud('/positions');
export const holidaysApi = {
  ...crud('/holidays'),
  importNational: (year) => gateway.post('/holidays/import-national', { year }),
};
export const auditApi = { list: (params) => gateway.get('/audit-logs', { query: params }) };

export const notificationsApi = {
  list: (params) => gateway.get('/notifications', { query: params }),
  read: (id) => gateway.post(`/notifications/${id}/read`),
  readAll: () => gateway.post('/notifications/read-all'),
};

export const profileApi = {
  update: (values) => gateway.put('/me/profile', values),
  avatar: (file) => {
    const body = new FormData();
    body.append('avatar', file);
    return gateway.post('/me/avatar', body);
  },
};
