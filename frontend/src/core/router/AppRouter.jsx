import { Suspense, useMemo } from 'react';
import { useSelector } from 'react-redux';
import { BrowserRouter, Navigate, Route, Routes, useLocation } from 'react-router-dom';
import { FullPageLoader } from '@/shared/components';
import { selectActiveModules, selectAuthStatus } from '../auth/selectors';
import LoginPage from '../auth/pages/LoginPage';
import ForgotPasswordPage from '../auth/pages/ForgotPasswordPage';
import ResetPasswordPage from '../auth/pages/ResetPasswordPage';
import AppLayout from '../layout/AppLayout';
import HomePage from '../pages/HomePage';
import ResultPage from '../pages/ResultPage';
import { getInactiveModulePaths, getModules } from '../modules/registry';
import usePermission from '../permissions/usePermission';
import coreRoutes from '../hr/routes';

function RequireAuth({ children }) {
  const status = useSelector(selectAuthStatus);
  const location = useLocation();
  if (status === 'idle' || status === 'checking') return <FullPageLoader />;
  if (status !== 'authenticated') return <Navigate to="/login" replace state={{ from: location }} />;
  return children;
}

function GuestOnly({ children }) {
  const status = useSelector(selectAuthStatus);
  const location = useLocation();
  if (status === 'idle' || status === 'checking') return <FullPageLoader />;
  if (status === 'authenticated') return <Navigate to={location.state?.from?.pathname || '/'} replace />;
  return children;
}

export default function AppRouter() {
  const activeModules = useSelector(selectActiveModules);
  const loaded = useSelector((state) => state.app.loadedModules);
  const can = usePermission();

  // Só os módulos ativos E carregados geram rotas (os componentes de página são lazy).
  const moduleRoutes = useMemo(
    () =>
      [
        ...coreRoutes,
        ...getModules(activeModules.filter((k) => loaded.includes(k))).flatMap((m) => m.routes || []),
      ].filter((r) => can(r.permission)),
    [activeModules, loaded, can],
  );

  const moduleUnavailable = <ResultPage status="403" i18nKey="errors.moduleUnavailable" />;

  return (
    <BrowserRouter>
      <Suspense fallback={<FullPageLoader />}>
        <Routes>
          <Route
            path="/login"
            element={
              <GuestOnly>
                <LoginPage />
              </GuestOnly>
            }
          />
          <Route
            path="/recuperar-password"
            element={
              <GuestOnly>
                <ForgotPasswordPage />
              </GuestOnly>
            }
          />
          <Route path="/reset-password" element={<ResetPasswordPage />} />
          <Route
            element={
              <RequireAuth>
                <AppLayout />
              </RequireAuth>
            }
          >
            <Route index element={<HomePage />} />
            {moduleRoutes.map(({ path, component: Page }) => (
              <Route key={path} path={path} element={<Page />} />
            ))}
            {getInactiveModulePaths(activeModules).map((base) => (
              <Route key={base} path={`${base}/*`} element={moduleUnavailable} />
            ))}
            <Route path="/modulo-indisponivel" element={moduleUnavailable} />
            <Route path="*" element={<ResultPage status="404" i18nKey="errors.notFound" />} />
          </Route>
        </Routes>
      </Suspense>
    </BrowserRouter>
  );
}
