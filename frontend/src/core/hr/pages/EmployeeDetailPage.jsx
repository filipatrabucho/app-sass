import { useEffect, useState } from 'react';
import {
  App,
  Breadcrumb,
  Button,
  Card,
  Descriptions,
  Empty,
  Result,
  Space,
  Spin,
  Table,
  Tabs,
  Tag,
} from 'antd';
import { DownloadOutlined, EditOutlined } from '@ant-design/icons';
import { useDispatch, useSelector } from 'react-redux';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { ConfirmDelete, FileUpload, ItemList, PageHeader, StatusTag, UserAvatar } from '@/shared/components';
import { formatDate, formatDateTime, formatSize } from '@/shared/format';
import usePermission from '../../permissions/usePermission';
import { fetchAttachments, removeAttachment, uploadAttachment } from '../actions';
import { employees } from '../resources';
import EmployeeFormDrawer from '../components/EmployeeFormDrawer';

function Details({ employee }) {
  const { t } = useTranslation();
  const address = employee.address
    ? [employee.address.street, employee.address.postal_code, employee.address.city, employee.address.country]
        .filter(Boolean)
        .join(', ')
    : null;

  return (
    <Descriptions bordered column={{ xs: 1, md: 2 }} size="small">
      <Descriptions.Item label={t('employees.fields.employee_number')}>
        {employee.employee_number || '—'}
      </Descriptions.Item>
      <Descriptions.Item label={t('employees.fields.email')}>{employee.email || '—'}</Descriptions.Item>
      <Descriptions.Item label={t('employees.fields.phone')}>{employee.phone || '—'}</Descriptions.Item>
      <Descriptions.Item label={t('employees.fields.department')}>
        {employee.department?.name || '—'}
      </Descriptions.Item>
      <Descriptions.Item label={t('employees.fields.position')}>
        {employee.position?.name || '—'}
      </Descriptions.Item>
      <Descriptions.Item label={t('employees.fields.manager')}>
        {employee.manager ? (
          <Link to={`/gestao/colaboradores/${employee.manager.id}`}>{employee.manager.full_name}</Link>
        ) : (
          '—'
        )}
      </Descriptions.Item>
      <Descriptions.Item label={t('employees.fields.hire_date')}>
        {formatDate(employee.hire_date)}
      </Descriptions.Item>
      {'nif' in employee && (
        <>
          <Descriptions.Item label={t('employees.fields.nif')}>{employee.nif || '—'}</Descriptions.Item>
          <Descriptions.Item label={t('employees.fields.birth_date')}>
            {formatDate(employee.birth_date)}
          </Descriptions.Item>
          <Descriptions.Item label={t('employees.fields.iban')}>{employee.iban || '—'}</Descriptions.Item>
          <Descriptions.Item label={t('employees.sections.address')} span={2}>
            {address || '—'}
          </Descriptions.Item>
        </>
      )}
      <Descriptions.Item label={t('employees.fields.role')}>
        {employee.user
          ? employee.user.roles.map((r) => <Tag key={r}>{t(`employees.roles.${r}`)}</Tag>)
          : t('employees.noAccount')}
      </Descriptions.Item>
      <Descriptions.Item label={t('employees.fields.last_login')}>
        {formatDateTime(employee.user?.last_login_at)}
      </Descriptions.Item>
    </Descriptions>
  );
}

function Documents({ employee, canManage }) {
  const { t } = useTranslation();
  const { message } = App.useApp();
  const dispatch = useDispatch();
  const { items, loading, uploading } = useSelector((state) => state.hr.attachments);

  useEffect(() => {
    dispatch(fetchAttachments(employee.id));
  }, [dispatch, employee.id]);

  const columns = [
    {
      title: t('employees.fields.full_name'),
      dataIndex: 'original_name',
      render: (name, row) => (
        <a href={`/api/employees/${employee.id}/attachments/${row.id}`} download>
          <DownloadOutlined /> {name}
        </a>
      ),
    },
    { title: t('employees.documents.size'), dataIndex: 'size', render: formatSize, responsive: ['md'] },
    { title: t('employees.documents.uploadedBy'), dataIndex: 'uploaded_by', responsive: ['md'] },
    { title: t('employees.documents.date'), dataIndex: 'created_at', render: formatDate },
    canManage && {
      title: '',
      key: 'actions',
      render: (_, row) => (
        <ConfirmDelete
          onConfirm={() => dispatch(removeAttachment({ employeeId: employee.id, attachmentId: row.id }))}
        />
      ),
    },
  ].filter(Boolean);

  return (
    <>
      {canManage && (
        <div className="page-section">
          <FileUpload
            loading={uploading}
            label={t('employees.documents.upload')}
            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.txt"
            onSelect={(file) => dispatch(uploadAttachment({ employeeId: employee.id, file }))}
            onTooLarge={() => message.error(t('shared.fileTooLarge', { max: 10 }))}
          />
        </div>
      )}
      <Table
        rowKey="id"
        size="small"
        loading={loading}
        columns={columns}
        dataSource={items}
        pagination={false}
        locale={{ emptyText: <Empty description={t('employees.documents.empty')} /> }}
      />
    </>
  );
}

function Team({ employee }) {
  const { t } = useTranslation();
  const dispatch = useDispatch();
  const { items, loading } = useSelector((state) => state.employees.list);

  useEffect(() => {
    dispatch(employees.actions.fetchList({ manager_id: employee.id, per_page: 100 }));
  }, [dispatch, employee.id]);

  return (
    <ItemList
      loading={loading}
      items={items.filter((e) => e.manager_id === employee.id)}
      emptyText={t('employees.teamEmpty')}
      renderItem={(e) => ({
        key: e.id,
        avatar: <UserAvatar name={e.full_name} src={e.avatar_url} />,
        title: <Link to={`/gestao/colaboradores/${e.id}`}>{e.full_name}</Link>,
        description: e.position?.name,
      })}
    />
  );
}

export default function EmployeeDetailPage() {
  const { id } = useParams();
  const { t } = useTranslation();
  const dispatch = useDispatch();
  const navigate = useNavigate();
  const can = usePermission();
  const { data: employee, loading, error } = useSelector((state) => state.employees.current);
  const removing = useSelector((state) => state.employees.removing);
  const [editing, setEditing] = useState(false);
  const canManage = can('core.employees.manage');

  useEffect(() => {
    dispatch(employees.actions.fetchOne(id));
  }, [dispatch, id]);

  if (error) return <Result status={error.status === 403 ? '403' : '404'} title={error.message} />;
  if (loading || !employee) return <Spin />;

  return (
    <>
      <Breadcrumb
        className="page-section"
        items={[
          { title: <Link to="/gestao/colaboradores">{t('employees.title')}</Link> },
          { title: employee.full_name },
        ]}
      />
      <PageHeader
        title={
          <Space>
            <UserAvatar name={employee.full_name} src={employee.avatar_url} size="large" />
            {employee.full_name}
            <StatusTag status={employee.status} />
          </Space>
        }
        subtitle={[employee.position?.name, employee.department?.name].filter(Boolean).join(' · ')}
        extra={
          canManage && (
            <Space>
              <Button icon={<EditOutlined />} onClick={() => setEditing(true)}>
                {t('shared.edit')}
              </Button>
              <ConfirmDelete
                iconOnly={false}
                loading={removing === employee.id}
                title={t('employees.deleteConfirm')}
                onConfirm={() => {
                  dispatch(employees.actions.remove(employee.id));
                  navigate('/gestao/colaboradores');
                }}
              />
            </Space>
          )
        }
      />
      <Card>
        <Tabs
          items={[
            { key: 'details', label: t('employees.tabs.details'), children: <Details employee={employee} /> },
            'nif' in employee && {
              key: 'documents',
              label: t('employees.tabs.documents'),
              children: <Documents employee={employee} canManage={canManage} />,
            },
            {
              key: 'team',
              label: `${t('employees.tabs.team')} (${employee.subordinates_count ?? 0})`,
              children: <Team employee={employee} />,
            },
          ].filter(Boolean)}
        />
      </Card>
      <EmployeeFormDrawer open={editing} employee={employee} onClose={() => setEditing(false)} />
    </>
  );
}
