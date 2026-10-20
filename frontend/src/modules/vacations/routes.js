import { lazy } from 'react';

export default [
  {
    path: '/ferias',
    component: lazy(() => import('./pages/MyVacationsPage')),
    permission: 'vacations.requests.view_own',
  },
];
