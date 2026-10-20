import { Table } from 'antd';
import { useTranslation } from 'react-i18next';

/**
 * Tabela com paginação server-side (formato Laravel: { data, meta }).
 * onChange recebe { page, perPage, sorter, filters }.
 */
export default function DataTable({ columns, data = [], meta, loading, onChange, rowKey = 'id', ...rest }) {
  const { t } = useTranslation();

  const pagination = meta
    ? {
        current: meta.current_page,
        pageSize: meta.per_page,
        total: meta.total,
        showSizeChanger: true,
        showTotal: (total) => t('core:table.total', { total }),
      }
    : false;

  return (
    <Table
      rowKey={rowKey}
      columns={columns}
      dataSource={data}
      loading={loading}
      pagination={pagination}
      scroll={{ x: 'max-content' }}
      onChange={(p, filters, sorter) =>
        onChange?.({ page: p?.current, perPage: p?.pageSize, filters, sorter })
      }
      {...rest}
    />
  );
}
