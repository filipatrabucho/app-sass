import { useEffect, useState } from 'react';
import { Button, Card, Input, Select, Space, Tag } from 'antd';
import { PlusOutlined } from '@ant-design/icons';
import { useDispatch, useSelector } from 'react-redux';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { DataTable, PageHeader, StatusTag, UserAvatar } from '@/shared/components';
import usePermission from '../../permissions/usePermission';
import { departments, employees } from '../resources';
import EmployeeFormDrawer from '../components/EmployeeFormDrawer';

const SORTS = { full_name: 'name', hire_date: 'hire_date', employee_number: 'employee_number' };

export default function EmployeesPage() {
  const { t } = useTranslation();
  const dispatch = useDispatch();
  const can = usePermission();
  const { items, meta, loading, params } = useSelector((state) => state.employees.list);
  const departmentList = useSelector((state) => state.departments.list.items);
  const [drawerOpen, setDrawerOpen] = useState(false);

  useEffect(() => {
    dispatch(employees.actions.fetchList({ page: 1, status: 'active' }));
    dispatch(departments.actions.fetchList());
  }, [dispatch]);

  const query = (changes) => dispatch(employees.actions.fetchList({ ...params, page: 1, ...changes }));

  const columns = [
    {
      title: t('employees.fields.full_name'),
      dataIndex: 'full_name',
      sorter: true,
      render: (name, row) => (
        <Space>
          <UserAvatar name={name} src={row.avatar_url} size="small" />
          <Link to={`/gestao/colaboradores/${row.id}`}>{name}</Link>
        </Space>
      ),
    },
    {
      title: t('employees.fields.employee_number'),
      dataIndex: 'employee_number',
      sorter: true,
      responsive: ['md'],
    },
    { title: t('employees.fields.department'), dataIndex: ['department', 'name'], responsive: ['sm'] },
    { title: t('employees.fields.position'), dataIndex: ['position', 'name'], responsive: ['lg'] },
    { title: t('employees.fields.manager'), dataIndex: ['manager', 'full_name'], responsive: ['lg'] },
    {
      title: t('employees.fields.role'),
      dataIndex: 'user',
      responsive: ['md'],
      render: (user) =>
        user ? (
          user.roles.map((r) => <Tag key={r}>{t(`employees.roles.${r}`)}</Tag>)
        ) : (
          <Tag>{t('employees.noAccount')}</Tag>
        ),
    },
    { title: t('employees.fields.status'), dataIndex: 'status', render: (s) => <StatusTag status={s} /> },
  ];

  return (
    <>
      <PageHeader
        title={t('employees.title')}
        subtitle={t('employees.subtitle')}
        extra={
          can('core.employees.manage') && (
            <Button type="primary" icon={<PlusOutlined />} onClick={() => setDrawerOpen(true)}>
              {t('employees.new')}
            </Button>
          )
        }
      />
      <Card>
        <Space wrap className="page-section">
          <Input.Search
            allowClear
            placeholder={t('employees.searchPlaceholder')}
            onSearch={(search) => query({ search })}
          />
          <Select
            allowClear
            className="filter-select"
            placeholder={t('employees.allDepartments')}
            onChange={(department_id) => query({ department_id })}
            options={departmentList.map((d) => ({ value: d.id, label: d.name }))}
          />
          <Select
            allowClear
            className="filter-select"
            defaultValue="active"
            placeholder={t('employees.allStatuses')}
            onChange={(status) => query({ status })}
            options={['active', 'inactive'].map((s) => ({ value: s, label: t(`status.${s}`) }))}
          />
        </Space>
        <DataTable
          columns={columns}
          data={items}
          meta={meta}
          loading={loading}
          onChange={({ page, perPage, sorter }) => {
            const field = SORTS[Array.isArray(sorter?.field) ? sorter.field[0] : sorter?.field];
            const sort =
              field && sorter.order ? `${sorter.order === 'descend' ? '-' : ''}${field}` : undefined;
            dispatch(employees.actions.fetchList({ ...params, page, per_page: perPage, sort }));
          }}
        />
      </Card>
      <EmployeeFormDrawer open={drawerOpen} employee={null} onClose={() => setDrawerOpen(false)} />
    </>
  );
}
