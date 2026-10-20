import { useEffect } from 'react';
import { App as AntApp, ConfigProvider } from 'antd';
import { useDispatch } from 'react-redux';
import { bootstrapRequest } from '@/core/auth/actions';
import AppRouter from '@/core/router/AppRouter';
import { antdConfig } from '@/shared/theme';

export default function App() {
  const dispatch = useDispatch();

  useEffect(() => {
    dispatch(bootstrapRequest());
  }, [dispatch]);

  return (
    <ConfigProvider {...antdConfig}>
      <AntApp>
        <AppRouter />
      </AntApp>
    </ConfigProvider>
  );
}
