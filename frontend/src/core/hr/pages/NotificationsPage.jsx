import { useEffect, useState } from 'react';
import { Badge, Button, Card, Segmented, Typography } from 'antd';
import { useDispatch, useSelector } from 'react-redux';
import { useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { ItemList, PageHeader } from '@/shared/components';
import { formatDateTime } from '@/shared/format';
import { fetchNotifications, markAllNotificationsRead, markNotificationRead } from '../actions';

export default function NotificationsPage() {
  const { t } = useTranslation();
  const dispatch = useDispatch();
  const navigate = useNavigate();
  const [filter, setFilter] = useState('all');
  const [page, setPage] = useState(1);
  const { items, meta, unreadCount, loading } = useSelector((state) => state.hr.notifications);

  useEffect(() => {
    dispatch(fetchNotifications({ page, per_page: 15, unread: filter === 'unread' ? 1 : undefined }));
  }, [dispatch, filter, page]);

  const open = (item) => {
    if (!item.read_at) dispatch(markNotificationRead(item.id));
    if (item.link) navigate(item.link);
  };

  return (
    <>
      <PageHeader
        title={t('notifications.title')}
        subtitle={t('notifications.subtitle')}
        extra={
          <Button disabled={unreadCount === 0} onClick={() => dispatch(markAllNotificationsRead())}>
            {t('notifications.markAll')}
          </Button>
        }
      />
      <Card>
        <Segmented
          className="page-section"
          value={filter}
          onChange={(value) => {
            setFilter(value);
            setPage(1);
          }}
          options={[
            { value: 'all', label: t('shared.all') },
            { value: 'unread', label: `${t('notifications.unreadOnly')} (${unreadCount})` },
          ]}
        />
        <ItemList
          loading={loading}
          items={items}
          emptyText={t('notifications.empty')}
          pagination={
            meta && meta.total > meta.per_page
              ? { current: meta.current_page, pageSize: meta.per_page, total: meta.total, onChange: setPage }
              : null
          }
          renderItem={(item) => ({
            key: item.id,
            className: item.read_at ? undefined : 'notification-unread',
            avatar: <Badge dot={!item.read_at} />,
            title: item.title,
            description: (
              <>
                <div>{item.body}</div>
                <Typography.Text type="secondary">{formatDateTime(item.created_at)}</Typography.Text>
              </>
            ),
            actions: [
              !item.read_at && (
                <Button key="read" type="link" onClick={() => dispatch(markNotificationRead(item.id))}>
                  {t('notifications.markRead')}
                </Button>
              ),
              item.link && (
                <Button key="open" type="link" onClick={() => open(item)}>
                  {t('notifications.open')}
                </Button>
              ),
            ].filter(Boolean),
          })}
        />
      </Card>
    </>
  );
}
