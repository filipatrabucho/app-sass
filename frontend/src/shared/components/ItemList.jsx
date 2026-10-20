import { Empty, Pagination, Spin } from 'antd';

/**
 * Lista simples (substitui o List do AntD, obsoleto na v6).
 * renderItem devolve { key, avatar?, title, description?, extra?, actions?, className?, onClick? }.
 */
export default function ItemList({ items = [], renderItem, loading = false, emptyText, pagination }) {
  return (
    <Spin spinning={loading}>
      {items.length === 0 && !loading ? (
        <Empty description={emptyText} />
      ) : (
        <ul className="item-list">
          {items.map((item) => {
            const row = renderItem(item);
            return (
              <li key={row.key} className={['item-list-row', row.className].filter(Boolean).join(' ')}>
                {row.avatar && <div className="item-list-avatar">{row.avatar}</div>}
                <div className="item-list-body">
                  <div className="item-list-title">{row.title}</div>
                  {row.description && <div className="item-list-description">{row.description}</div>}
                </div>
                {row.extra && <div className="item-list-extra">{row.extra}</div>}
                {row.actions?.length > 0 && <div className="item-list-actions">{row.actions}</div>}
              </li>
            );
          })}
        </ul>
      )}
      {pagination && <Pagination className="item-list-pagination" size="small" {...pagination} />}
    </Spin>
  );
}
