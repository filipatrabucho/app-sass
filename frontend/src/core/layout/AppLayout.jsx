import { useEffect, useMemo, useState } from 'react';
import { Button, Dropdown, Grid, Layout, Menu, Space } from 'antd';
import { LogoutOutlined, MenuOutlined, UserOutlined } from '@ant-design/icons';
import { UserAvatar } from '@/shared/components';
import NotificationBell from '../hr/components/NotificationBell';
import coreMenu from '../hr/menu';
import { useDispatch, useSelector } from 'react-redux';
import { Outlet, useLocation, useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { logoutRequest } from '../auth/actions';
import { selectActiveModules, selectTenant, selectUser } from '../auth/selectors';
import { moduleUnavailableHandled } from '../app/actions';
import { getModules } from '../modules/registry';
import usePermission from '../permissions/usePermission';

const AREAS = ['portal', 'backoffice'];

/** Menu = itens do core + itens dos módulos ativos, agrupados por área e filtrados por permissão. */
function useMenuItems() {
  const { t } = useTranslation();
  const can = usePermission();
  const activeModules = useSelector(selectActiveModules);
  const loaded = useSelector((state) => state.app.loadedModules);

  return useMemo(() => {
    // Core primeiro; os módulos são inseridos antes dos itens de gestão do core.
    const entries = [
      ...coreMenu,
      ...getModules(activeModules.filter((k) => loaded.includes(k))).flatMap((m) => m.menu || []),
    ].filter((item) => can(item.permission));

    return AREAS.map((area) => ({
      type: 'group',
      key: area,
      label: t(`menu.${area}`),
      children: entries
        .filter((item) => (item.area || 'portal') === area)
        .map(({ path, label, labelKey, icon: Icon }) => ({
          key: path,
          label: labelKey ? t(labelKey) : label,
          icon: Icon ? <Icon /> : null,
        })),
    })).filter((group) => group.children.length > 0);
  }, [t, can, activeModules, loaded]);
}

export default function AppLayout() {
  const { t } = useTranslation();
  const dispatch = useDispatch();
  const navigate = useNavigate();
  const location = useLocation();
  const screens = Grid.useBreakpoint();
  const isMobile = !screens.lg;
  const [collapsed, setCollapsed] = useState(false);
  const user = useSelector(selectUser);
  const employee = useSelector((state) => state.auth.employee);
  const tenant = useSelector(selectTenant);
  const moduleUnavailable = useSelector((state) => state.app.moduleUnavailable);
  const items = useMenuItems();

  useEffect(() => {
    if (moduleUnavailable) {
      dispatch(moduleUnavailableHandled());
      navigate('/modulo-indisponivel', { replace: true });
    }
  }, [moduleUnavailable, dispatch, navigate]);

  const selectedKey =
    items
      .flatMap((g) => g.children)
      .map((i) => i.key)
      .filter((key) => (key === '/' ? location.pathname === '/' : location.pathname.startsWith(key)))
      .sort((a, b) => b.length - a.length)[0] || '/';

  const userMenu = {
    items: [
      { key: 'profile', icon: <UserOutlined />, label: t('menu.profile') },
      { type: 'divider' },
      { key: 'logout', icon: <LogoutOutlined />, label: t('auth.logout') },
    ],
    onClick: ({ key }) => (key === 'logout' ? dispatch(logoutRequest()) : navigate('/perfil')),
  };

  return (
    <Layout className="app-layout">
      <Layout.Sider
        collapsible
        trigger={null}
        collapsed={collapsed}
        collapsedWidth={isMobile ? 0 : 80}
        breakpoint="lg"
        onBreakpoint={setCollapsed}
        width={240}
      >
        <div className="app-sider-logo">{collapsed ? '' : tenant?.name || t('app.name')}</div>
        <Menu
          theme="dark"
          mode="inline"
          selectedKeys={[selectedKey]}
          items={items}
          onClick={({ key }) => {
            navigate(key);
            if (isMobile) setCollapsed(true);
          }}
        />
      </Layout.Sider>
      <Layout>
        <Layout.Header className="app-header">
          <Button type="text" icon={<MenuOutlined />} onClick={() => setCollapsed((c) => !c)} />
          <span className="app-header-title">{tenant?.name}</span>
          <Space size="small">
            <NotificationBell />
            <Dropdown menu={userMenu} trigger={['click']}>
              <Button type="text" className="header-user">
                <UserAvatar size="small" name={user?.name} src={employee?.avatar_url} />
                {!isMobile && user?.name}
              </Button>
            </Dropdown>
          </Space>
        </Layout.Header>
        <Layout.Content className="app-content">
          <Outlet />
        </Layout.Content>
      </Layout>
    </Layout>
  );
}
