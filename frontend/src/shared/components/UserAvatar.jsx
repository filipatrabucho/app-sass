import { Avatar } from 'antd';

const initials = (name = '') =>
  name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0].toUpperCase())
    .join('');

/** Fotografia do colaborador (servida pela API com a sessão) ou iniciais. */
export default function UserAvatar({ name, src, size = 'default' }) {
  return (
    <Avatar src={src || undefined} size={size} className="user-avatar">
      {initials(name)}
    </Avatar>
  );
}
