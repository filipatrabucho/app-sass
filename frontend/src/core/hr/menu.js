import {
  ApartmentOutlined,
  AuditOutlined,
  BellOutlined,
  CalendarOutlined,
  HomeOutlined,
  IdcardOutlined,
  TeamOutlined,
  UserOutlined,
} from '@ant-design/icons';

/** Menu do Core. area: 'portal' (todos) ou 'backoffice' (gestão; filtrado por permissão). */
export default [
  { path: '/', labelKey: 'menu.home', icon: HomeOutlined, area: 'portal' },
  { path: '/perfil', labelKey: 'menu.profile', icon: UserOutlined, area: 'portal' },
  { path: '/notificacoes', labelKey: 'menu.notifications', icon: BellOutlined, area: 'portal' },
  { path: '/feriados', labelKey: 'menu.holidays', icon: CalendarOutlined, area: 'portal' },
  {
    path: '/gestao/colaboradores',
    labelKey: 'menu.employees',
    icon: TeamOutlined,
    area: 'backoffice',
    permission: 'core.employees.view',
  },
  {
    path: '/gestao/departamentos',
    labelKey: 'menu.departments',
    icon: ApartmentOutlined,
    area: 'backoffice',
    permission: 'core.departments.manage',
  },
  {
    path: '/gestao/cargos',
    labelKey: 'menu.positions',
    icon: IdcardOutlined,
    area: 'backoffice',
    permission: 'core.positions.manage',
  },
  {
    path: '/gestao/auditoria',
    labelKey: 'menu.audit',
    icon: AuditOutlined,
    area: 'backoffice',
    permission: 'core.audit.view',
  },
];
