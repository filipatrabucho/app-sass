import { Tag } from 'antd';
import { useTranslation } from 'react-i18next';

const COLORS = {
  active: 'green',
  approved: 'green',
  pending: 'gold',
  cancel_requested: 'orange',
  suspended: 'red',
  rejected: 'red',
  inactive: 'default',
  cancelled: 'default',
};

/** Etiqueta de estado com cor e tradução standard (core:status.*). */
export default function StatusTag({ status }) {
  const { t } = useTranslation();
  return <Tag color={COLORS[status] || 'default'}>{t(`core:status.${status}`, status)}</Tag>;
}
