import dayjs from 'dayjs';

export const formatDate = (value) => (value ? dayjs(value).format('DD/MM/YYYY') : '—');
export const formatDateTime = (value) => (value ? dayjs(value).format('DD/MM/YYYY HH:mm') : '—');
export const formatSize = (bytes = 0) =>
  bytes > 1024 * 1024
    ? `${(bytes / 1024 / 1024).toFixed(1)} MB`
    : `${Math.max(1, Math.round(bytes / 1024))} KB`;

/** Lista plana com parent_id -> árvore (children) para Table/TreeSelect. */
export function toTree(items, { idKey = 'id', parentKey = 'parent_id' } = {}) {
  const byId = new Map(items.map((item) => [item[idKey], { ...item, children: [] }]));
  const roots = [];
  byId.forEach((node) => {
    const parent = node[parentKey] ? byId.get(node[parentKey]) : null;
    (parent ? parent.children : roots).push(node);
  });
  const clean = (nodes) =>
    nodes.map((n) =>
      n.children.length ? { ...n, children: clean(n.children) } : { ...n, children: undefined },
    );
  return clean(roots);
}
