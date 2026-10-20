import { Card, Col, Empty, Row } from 'antd';
import { useSelector } from 'react-redux';
import { useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { PageHeader } from '@/shared/components';
import { selectActiveModules, selectTenant, selectUser } from '../auth/selectors';
import { getModules } from '../modules/registry';
import usePermission from '../permissions/usePermission';

export default function HomePage() {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const can = usePermission();
  const user = useSelector(selectUser);
  const tenant = useSelector(selectTenant);
  const activeModules = useSelector(selectActiveModules);
  const shortcuts = getModules(activeModules)
    .flatMap((m) => m.menu || [])
    .filter((item) => can(item.permission));

  return (
    <>
      <PageHeader
        title={t('home.title', { name: user?.name })}
        subtitle={t('home.subtitle', { tenant: tenant?.name })}
      />
      <Card title={t('home.modules')}>
        {shortcuts.length === 0 ? (
          <Empty description={t('home.noModules')} />
        ) : (
          <Row gutter={[16, 16]}>
            {shortcuts.map(({ path, labelKey, icon: Icon }) => (
              <Col key={path} xs={24} sm={12} md={8}>
                <Card hoverable onClick={() => navigate(path)}>
                  <Card.Meta avatar={Icon ? <Icon /> : null} title={t(labelKey)} />
                </Card>
              </Col>
            ))}
          </Row>
        )}
      </Card>
    </>
  );
}
