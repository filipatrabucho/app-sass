import { useEffect } from 'react';
import { Card, Col, Descriptions, Empty, Row, Statistic, Tag } from 'antd';
import { BellOutlined, TeamOutlined } from '@ant-design/icons';
import { useDispatch, useSelector } from 'react-redux';
import { Link, useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import dayjs from 'dayjs';
import { ItemList, PageHeader, UserAvatar } from '@/shared/components';
import { formatDate } from '@/shared/format';
import { selectActiveModules, selectAuth } from '../auth/selectors';
import { getModules } from '../modules/registry';
import { holidays } from '../hr/resources';
import usePermission from '../permissions/usePermission';

export default function HomePage() {
  const { t } = useTranslation();
  const dispatch = useDispatch();
  const navigate = useNavigate();
  const can = usePermission();
  const { user, employee, tenant } = useSelector(selectAuth);
  const activeModules = useSelector(selectActiveModules);
  const holidayList = useSelector((state) => state.holidays.list.items);
  const unreadCount = useSelector((state) => state.hr.notifications.unreadCount);

  useEffect(() => {
    dispatch(holidays.actions.fetchList({ year: dayjs().year() }));
  }, [dispatch]);

  const today = dayjs().format('YYYY-MM-DD');
  const upcoming = holidayList.filter((h) => h.date >= today).slice(0, 4);
  const shortcuts = getModules(activeModules)
    .flatMap((m) => m.menu || [])
    .filter((item) => can(item.permission));

  return (
    <>
      <PageHeader
        title={t('home.title', { name: employee?.first_name || user?.name })}
        subtitle={t('home.subtitle', { tenant: tenant?.name })}
      />
      <Row gutter={[16, 16]}>
        <Col xs={24} lg={12}>
          <Card
            title={t('home.myInfo')}
            extra={<Link to="/perfil">{t('menu.profile')}</Link>}
            className="h-full"
          >
            {employee ? (
              <div className="home-me">
                <UserAvatar size={64} name={employee.full_name} src={employee.avatar_url} />
                <Descriptions column={1} size="small">
                  <Descriptions.Item label={t('home.position')}>
                    {employee.position?.name || '—'}
                  </Descriptions.Item>
                  <Descriptions.Item label={t('home.department')}>
                    {employee.department?.name || '—'}
                  </Descriptions.Item>
                  <Descriptions.Item label={t('home.manager')}>
                    {employee.manager?.full_name || '—'}
                  </Descriptions.Item>
                  <Descriptions.Item label={t('home.hireDate')}>
                    {formatDate(employee.hire_date)}
                  </Descriptions.Item>
                </Descriptions>
              </div>
            ) : (
              <Empty description={t('home.noEmployee')} />
            )}
          </Card>
        </Col>
        <Col xs={12} lg={6}>
          <Card hoverable onClick={() => navigate('/notificacoes')} className="h-full">
            <Statistic title={t('home.unread')} value={unreadCount} prefix={<BellOutlined />} />
          </Card>
        </Col>
        <Col xs={12} lg={6}>
          <Card
            hoverable={can('core.employees.view')}
            onClick={() => can('core.employees.view') && navigate('/gestao/colaboradores')}
            className="h-full"
          >
            <Statistic
              title={t('home.team')}
              value={employee?.subordinates_count ?? 0}
              prefix={<TeamOutlined />}
            />
          </Card>
        </Col>
        <Col xs={24} lg={12}>
          <Card title={t('home.upcomingHolidays')} extra={<Link to="/feriados">{t('home.seeAll')}</Link>}>
            <ItemList
              items={upcoming}
              emptyText={t('home.noHolidays')}
              renderItem={(h) => ({
                key: `${h.id}-${h.date}`,
                title: h.name,
                description: dayjs(h.date).format('dddd, D [de] MMMM'),
                extra: <Tag>{t(`holidays.types.${h.type}`)}</Tag>,
              })}
            />
          </Card>
        </Col>
        <Col xs={24} lg={12}>
          <Card title={t('home.modules')}>
            {shortcuts.length === 0 ? (
              <Empty description={t('home.noModules')} />
            ) : (
              <Row gutter={[16, 16]}>
                {shortcuts.map(({ path, labelKey, icon: Icon }) => (
                  <Col key={path} xs={24} sm={12}>
                    <Card hoverable size="small" onClick={() => navigate(path)}>
                      <Card.Meta avatar={Icon ? <Icon /> : null} title={t(labelKey)} />
                    </Card>
                  </Col>
                ))}
              </Row>
            )}
          </Card>
        </Col>
      </Row>
    </>
  );
}
