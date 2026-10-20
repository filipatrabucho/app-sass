import gateway from '../api/gateway';

export const authApi = {
  me: () => gateway.get('/me'),
  login: (credentials) => gateway.post('/auth/login', credentials),
  logout: () => gateway.post('/auth/logout'),
  forgotPassword: (email) => gateway.post('/auth/forgot-password', { email }),
  resetPassword: (payload) => gateway.post('/auth/reset-password', payload),
};
