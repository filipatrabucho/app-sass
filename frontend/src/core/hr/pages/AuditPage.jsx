import { useEffect } from 'react';
import { Card, Select, Space, Table, Tag } from 'antd';
import { useDispatch, useSelector } from 'react-redux';
import { useTranslation } from 'react-i18next';
import { DataTable, PageHeader } from '@/shared/components';
import { formatDateTime } from '@/shared/format';
import { audit } from '../resources';

const ACTION_COLORS = { created: 'green', updated: 'blue', deleted: 'red', restored: 'gold' };
const ENTITIES = ['employee', 'department', 'position', 'holiday', 'user'];

const show = (value) => {
  if (value === null || value === undefined || value === '') return '—';
  return typeof value === 'object' ? JSON.stringify(value) : String(value);
};

function Changes({ row }) {
  const { t } = useTranslation();
  const keys = [...new Set([...Object.keys(row.old_values || {}), ...Object.keys(row.new_values || {})])];
  const data = keys.map((key) => ({
    key,
    before: show(row.old_values?.[key]),
    after: show(row.new_values?.[key]),
  }));

  return (
    <Table
      size="small"
      pagination={false}
      dataSource={data}
      columns={[
        { title: t('audit.fields.field'), dataIndex: 'key' },
        { title: t('audit.fields.before'), dataIndex: 'before' },
        { title: t('audit.fields.after'), dataIndex: 'after' },
      ]}
    />
  );
}

export default function AuditPage() {
  const { t } = useTranslation();
  const dispatch = useDispatch();
  const { items, meta, loading, params } = useSelector((state) => state.audit.list);

  useEffect(() => {
    dispatch(audit.actions.fetchList({ page: 1 }));
  }, [dispatch]);

  const query = (changes) => dispatch(audit.actions.fetchList({ ...params, page: 1, ...changes }));

  const columns = [
    { title: t('audit.fields.date'), dataIndex: 'created_at', render: formatDateTime },
    { title: t('audit.fields.user'), dataIndex: 'user', render: (u) => u || t('audit.system') },
    {
      title: t('audit.fields.action'),
      dataIndex: 'action',
      render: (a) => <Tag color={ACTION_COLORS[a]}>{t(`audit.actions.${a}`)}</Tag>,
    },
    {
      title: t('audit.fields.entity'),
      dataIndex: 'auditable_type',
      render: (e) => t(`audit.entities.${e}`, e),
    },
    { title: t('audit.fields.id'), dataIndex: 'auditable_id', responsive: ['md'] },
    { title: t('audit.fields.ip'), dataIndex: 'ip', responsive: ['lg'] },
  ];

  return (
    <>
      <PageHeader title={t('audit.title')} subtitle={t('audit.subtitle')} />
      <Card>
        <Space wrap className="page-section">
          <Select
            allowClear
            className="filter-select"
            placeholder={t('audit.fields.entity')}
            onChange={(auditable_type) => query({ auditable_type })}
            options={ENTITIES.map((e) => ({ value: e, label: t(`audit.entities.${e}`) }))}
          />
          <Select
            allowClear
            className="filter-select"
            placeholder={t('audit.fields.action')}
            onChange={(action) => query({ action })}
            options={Object.keys(ACTION_COLORS).map((a) => ({ value: a, label: t(`audit.actions.${a}`) }))}
          />
        </Space>
        <DataTable
          columns={columns}
          data={items}
          meta={meta}
          loading={loading}
          expandable={{ expandedRowRender: (row) => <Changes row={row} /> }}
          onChange={({ page, perPage }) =>
            dispatch(audit.actions.fetchList({ ...params, page, per_page: perPage }))
          }
        />
      </Card>
    </>
  );
}
