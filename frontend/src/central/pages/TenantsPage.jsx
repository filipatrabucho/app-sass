import { useCallback, useEffect, useState } from 'react';
import { App, Button, Card, Input, Select, Space } from 'antd';
import { PlusOutlined } from '@ant-design/icons';
import { useDispatch, useSelector } from 'react-redux';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import dayjs from 'dayjs';
import { DataTable, PageHeader, StatusTag } from '@/shared/components';
import { clearSaveState, fetchModulesRequest, fetchTenantsRequest } from '../store/actions';
import CreateTenantModal from './CreateTenantModal';

export default function TenantsPage() {
  const { t } = useTranslation(['central', 'core']);
  const { message } = App.useApp();
  const dispatch = useDispatch();
  const { items, meta, loading, params } = useSelector((state) => state.central.tenants);
  const [creating, setCreating] = useState(false);

  useEffect(() => {
    dispatch(fetchTenantsRequest({ page: 1 }));
    dispatch(fetchModulesRequest());
  }, [dispatch]);

  const query = (changes) => dispatch(fetchTenantsRequest({ ...params, page: 1, ...changes }));

  const closeModal = useCallback(
    (created) => {
      setCreating(false);
      if (created) message.success(t('tenants.created'));
      dispatch(clearSaveState());
    },
    [dispatch, message, t],
  );

  const columns = [
    {
      title: t('tenants.columns.name'),
      dataIndex: 'name',
      render: (name, row) => <Link to={`/tenants/${row.id}`}>{name}</Link>,
    },
    { title: t('tenants.columns.nif'), dataIndex: 'nif' },
    { title: t('tenants.columns.domain'), dataIndex: 'domains', render: (d) => d?.[0] },
    { title: t('tenants.columns.status'), dataIndex: 'status', render: (s) => <StatusTag status={s} /> },
    {
      title: t('tenants.columns.created_at'),
      dataIndex: 'created_at',
      render: (d) => (d ? dayjs(d).format('DD/MM/YYYY') : ''),
    },
  ];

  return (
    <>
      <PageHeader
        title={t('tenants.title')}
        subtitle={t('tenants.subtitle')}
        extra={
          <Button type="primary" icon={<PlusOutlined />} onClick={() => setCreating(true)}>
            {t('tenants.new')}
          </Button>
        }
      />
      <Card>
        <Space wrap className="page-section">
          <Input.Search
            allowClear
            placeholder={t('tenants.search')}
            onSearch={(search) => query({ search })}
          />
          <Select
            allowClear
            placeholder={t('tenants.allStatuses')}
            className="w-full"
            popupMatchSelectWidth={false}
            onChange={(status) => query({ status })}
            options={['active', 'suspended'].map((s) => ({ value: s, label: t(`core:status.${s}`) }))}
          />
        </Space>
        <DataTable
          columns={columns}
          data={items}
          meta={meta}
          loading={loading}
          onChange={({ page, perPage }) =>
            dispatch(fetchTenantsRequest({ ...params, page, per_page: perPage }))
          }
        />
      </Card>
      <CreateTenantModal open={creating} onClose={closeModal} />
    </>
  );
}
