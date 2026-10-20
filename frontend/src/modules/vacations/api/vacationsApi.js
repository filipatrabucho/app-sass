import gateway from '@/core/api/gateway';

export const vacationsApi = {
  listMine: (params) => gateway.get('/vacations/my', { query: params }),
};
