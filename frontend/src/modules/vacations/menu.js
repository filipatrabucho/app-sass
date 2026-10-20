import { CalendarOutlined } from '@ant-design/icons';

/** area: 'portal' (colaborador) ou 'backoffice' (gestão). */
export default [
  {
    path: '/ferias',
    labelKey: 'vacations:menu.my',
    icon: CalendarOutlined,
    area: 'portal',
    permission: 'vacations.requests.view_own',
  },
];
