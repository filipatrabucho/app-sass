import { useEffect } from 'react';
import { Card, Empty } from 'antd';
import { useDispatch, useSelector } from 'react-redux';
import { useTranslation } from 'react-i18next';
import { DataTable, PageHeader, StatusTag } from '@/shared/components';
import { fetchMyVacationsRequest } from '../store/actions';

/** Fase 1: página de demonstração do carregamento lazy do módulo. Completa na Fase 4. */
export default function MyVacationsPage() {
  const { t } = useTranslation('vacations');
  const dispatch = useDispatch();
  const { items, loading } = useSelector((state) => state.vacations.my);

  useEffect(() => {
    dispatch(fetchMyVacationsRequest());
  }, [dispatch]);

  const columns = [
    { title: t('fields.start_date'), dataIndex: 'start_date' },
    { title: t('fields.end_date'), dataIndex: 'end_date' },
    { title: t('fields.business_days'), dataIndex: 'business_days' },
    { title: t('fields.status'), dataIndex: 'status', render: (status) => <StatusTag status={status} /> },
  ];

  return (
    <>
      <PageHeader title={t('my.title')} subtitle={t('my.subtitle')} />
      <Card>
        <DataTable
          columns={columns}
          data={items}
          loading={loading}
          locale={{ emptyText: <Empty description={t('my.empty')} /> }}
        />
      </Card>
    </>
  );
}
