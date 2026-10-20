import { useEffect } from 'react';
import { App as AntApp, ConfigProvider } from 'antd';
import { useDispatch, useSelector } from 'react-redux';
import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { FullPageLoader } from '@/shared/components';
import { antdConfig } from '@/shared/theme';
import CentralLayout from './layout/CentralLayout';
import LoginPage from './pages/LoginPage';
import TenantsPage from './pages/TenantsPage';
import TenantDetailPage from './pages/TenantDetailPage';
import { bootstrapRequest } from './store/actions';

function CentralRoutes() {
  const status = useSelector((state) => state.central.auth.status);

  if (status === 'idle' || status === 'checking') return <FullPageLoader />;

  if (status !== 'authenticated') {
    return (
      <Routes>
        <Route path="*" element={<LoginPage />} />
      </Routes>
    );
  }

  return (
    <Routes>
      <Route element={<CentralLayout />}>
        <Route index element={<TenantsPage />} />
        <Route path="/tenants/:id" element={<TenantDetailPage />} />
        <Route path="*" element={<Navigate to="/" replace />} />
      </Route>
    </Routes>
  );
}

export default function CentralApp() {
  const dispatch = useDispatch();

  useEffect(() => {
    dispatch(bootstrapRequest());
  }, [dispatch]);

  return (
    <ConfigProvider {...antdConfig}>
      <AntApp>
        <BrowserRouter>
          <CentralRoutes />
        </BrowserRouter>
      </AntApp>
    </ConfigProvider>
  );
}
