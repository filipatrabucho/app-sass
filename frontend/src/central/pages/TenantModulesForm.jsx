import { useEffect, useMemo } from 'react';
import { Alert, Button, DatePicker, Form, InputNumber, Switch, Table, Tag } from 'antd';
import { useDispatch, useSelector } from 'react-redux';
import { useTranslation } from 'react-i18next';
import dayjs from 'dayjs';
import { updateTenantModulesRequest } from '../store/actions';

const toDate = (value) => (value ? dayjs(value) : null);
const fromDate = (value) => (value ? value.format('YYYY-MM-DD') : null);

/**
 * Ativação de módulos por tenant: estado, período de validade e limite de utilizadores.
 */
export default function TenantModulesForm({ tenant }) {
  const { t } = useTranslation('central');
  const dispatch = useDispatch();
  const [form] = Form.useForm();
  const catalog = useSelector((state) => state.central.modules);
  const save = useSelector((state) => state.central.save);

  const rows = useMemo(() => {
    const licenses = Object.fromEntries((tenant.modules || []).map((m) => [m.key, m]));
    return catalog.map((module) => {
      const license = licenses[module.key];
      return {
        key: module.key,
        name: module.name,
        requires: module.requires,
        effective: license?.effective || false,
        active: license?.active || false,
        period: [toDate(license?.starts_at), toDate(license?.ends_at)],
        max_users: license?.max_users ?? null,
      };
    });
  }, [catalog, tenant.modules]);

  useEffect(() => {
    form.setFieldsValue({ modules: rows });
  }, [rows, form]);

  const submit = ({ modules }) =>
    dispatch(
      updateTenantModulesRequest({
        id: tenant.id,
        modules: modules.map((m, index) => ({
          key: rows[index].key,
          active: m.active,
          starts_at: fromDate(m.period?.[0]),
          ends_at: fromDate(m.period?.[1]),
          max_users: m.max_users || null,
        })),
      }),
    );

  const dependencyError = save.error?.code === 'MODULE_DEPENDENCY' ? save.error : null;

  const columns = [
    { title: t('modules.columns.module'), dataIndex: 'name' },
    {
      title: t('modules.columns.requires'),
      dataIndex: 'requires',
      render: (requires) => requires.map((r) => <Tag key={r}>{r}</Tag>),
    },
    {
      title: t('modules.columns.active'),
      render: (_, __, index) => (
        <Form.Item name={['modules', index, 'active']} valuePropName="checked" className="mb-0">
          <Switch />
        </Form.Item>
      ),
    },
    {
      title: t('modules.columns.period'),
      render: (_, __, index) => (
        <Form.Item name={['modules', index, 'period']} className="mb-0">
          <DatePicker.RangePicker allowEmpty={[true, true]} format="DD/MM/YYYY" />
        </Form.Item>
      ),
    },
    {
      title: t('modules.columns.max_users'),
      render: (_, __, index) => (
        <Form.Item name={['modules', index, 'max_users']} className="mb-0">
          <InputNumber min={1} placeholder={t('modules.unlimited')} />
        </Form.Item>
      ),
    },
    {
      title: t('modules.columns.effective'),
      dataIndex: 'effective',
      render: (effective) => (
        <Tag color={effective ? 'green' : 'default'}>{effective ? t('modules.yes') : t('modules.no')}</Tag>
      ),
    },
  ];

  return (
    <Form form={form} onFinish={submit}>
      <Alert type="info" showIcon className="page-section" title={t('modules.help')} />
      {dependencyError && (
        <Alert
          type="error"
          showIcon
          className="page-section"
          title={dependencyError.message}
          description={Object.values(dependencyError.errors || {})
            .flat()
            .join(' ')}
        />
      )}
      <Table
        rowKey="key"
        columns={columns}
        dataSource={rows}
        pagination={false}
        scroll={{ x: 'max-content' }}
      />
      <div className="text-right page-section">
        <Button type="primary" htmlType="submit" loading={save.pending}>
          {t('modules.save')}
        </Button>
      </div>
    </Form>
  );
}
