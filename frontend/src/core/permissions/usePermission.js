import { useSelector } from 'react-redux';
import { selectPermissions } from '../auth/selectors';

export function hasPermission(permissions, permission) {
  if (!permission) return true;
  const list = Array.isArray(permission) ? permission : [permission];
  return list.some((p) => permissions.includes(p));
}

/** Verificação no frontend apenas para UX (esconder menus/botões). A decisão é sempre do backend. */
export default function usePermission() {
  const permissions = useSelector(selectPermissions);
  return (permission) => hasPermission(permissions, permission);
}
