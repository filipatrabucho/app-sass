import { lazy } from 'react';

/** Rotas do Core de RH (sempre disponíveis; filtradas por permissão). */
export default [
  { path: '/perfil', component: lazy(() => import('./pages/ProfilePage')) },
  { path: '/notificacoes', component: lazy(() => import('./pages/NotificationsPage')) },
  { path: '/feriados', component: lazy(() => import('./pages/HolidaysPage')) },
  {
    path: '/gestao/colaboradores',
    component: lazy(() => import('./pages/EmployeesPage')),
    permission: 'core.employees.view',
  },
  {
    path: '/gestao/colaboradores/:id',
    component: lazy(() => import('./pages/EmployeeDetailPage')),
    permission: 'core.employees.view',
  },
  {
    path: '/gestao/departamentos',
    component: lazy(() => import('./pages/DepartmentsPage')),
    permission: 'core.departments.manage',
  },
  {
    path: '/gestao/cargos',
    component: lazy(() => import('./pages/PositionsPage')),
    permission: 'core.positions.manage',
  },
  {
    path: '/gestao/auditoria',
    component: lazy(() => import('./pages/AuditPage')),
    permission: 'core.audit.view',
  },
];
