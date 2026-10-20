import { Badge, Button, Dropdown, Empty, Typography } from 'antd';
import { BellOutlined } from '@ant-design/icons';
import { useDispatch, useSelector } from 'react-redux';
import { useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { formatDateTime } from '@/shared/format';
import { markNotificationRead } from '../actions';

/** Sino do cabeçalho: contagem de não lidas + últimas notificações (atualizadas pela saga). */
export default function NotificationBell() {
  const { t } = useTranslation();
  const dispatch = useDispatch();
  const navigate = useNavigate();
  const { items, unreadCount } = useSelector((state) => state.hr.notifications);

  const recent = items.slice(0, 5);
  const menu = {
    items: [
      ...(recent.length
        ? recent.map((n) => ({
            key: n.id,
            label: (
              <div className={n.read_at ? 'bell-item' : 'bell-item bell-item-unread'}>
                <Typography.Text strong={!n.read_at}>{n.title}</Typography.Text>
                <Typography.Text type="secondary" className="bell-item-date">
                  {formatDateTime(n.created_at)}
                </Typography.Text>
              </div>
            ),
          }))
        : [
            {
              key: 'empty',
              disabled: true,
              label: <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} description={t('notifications.empty')} />,
            },
          ]),
      { type: 'divider' },
      { key: 'all', label: t('home.seeAll') },
    ],
    onClick: ({ key }) => {
      if (key === 'all') return navigate('/notificacoes');
      const item = items.find((n) => n.id === key);
      if (!item) return undefined;
      if (!item.read_at) dispatch(markNotificationRead(item.id));
      return navigate(item.link || '/notificacoes');
    },
  };

  return (
    <Dropdown menu={menu} trigger={['click']} placement="bottomRight">
      <Button type="text" aria-label={t('menu.notifications')}>
        <Badge count={unreadCount} size="small">
          <BellOutlined className="header-icon" />
        </Badge>
      </Button>
    </Dropdown>
  );
}
