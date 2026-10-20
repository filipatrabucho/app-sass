import { Button, Layout, Menu } from 'antd';
import { LogoutOutlined, ShopOutlined } from '@ant-design/icons';
import { useDispatch, useSelector } from 'react-redux';
import { Outlet, useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { logoutRequest } from '../store/actions';

export default function CentralLayout() {
  const { t } = useTranslation('central');
  const dispatch = useDispatch();
  const navigate = useNavigate();
  const admin = useSelector((state) => state.central.auth.admin);

  return (
    <Layout className="app-layout">
      <Layout.Sider breakpoint="lg" collapsedWidth={0} width={220}>
        <div className="app-sider-logo">{t('app.name')}</div>
        <Menu
          theme="dark"
          mode="inline"
          selectedKeys={['/']}
          items={[{ key: '/', icon: <ShopOutlined />, label: t('menu.tenants') }]}
          onClick={({ key }) => navigate(key)}
        />
      </Layout.Sider>
      <Layout>
        <Layout.Header className="app-header">
          <span className="app-header-title">{admin?.name}</span>
          <Button type="text" icon={<LogoutOutlined />} onClick={() => dispatch(logoutRequest())}>
            {t('logout')}
          </Button>
        </Layout.Header>
        <Layout.Content className="app-content">
          <Outlet />
        </Layout.Content>
      </Layout>
    </Layout>
  );
}
