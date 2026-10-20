import { useEffect, useMemo, useState } from 'react';
import { Button, Card, Form, Input, Modal, Space, Table, TreeSelect } from 'antd';
import { EditOutlined, PlusOutlined } from '@ant-design/icons';
import { useDispatch, useSelector } from 'react-redux';
import { useTranslation } from 'react-i18next';
import { ConfirmDelete, EmployeeSelect, PageHeader } from '@/shared/components';
import useSaveFeedback from '@/shared/hooks/useSaveFeedback';
import { toTree } from '@/shared/format';
import usePermission from '../../permissions/usePermission';
import { departments } from '../resources';

export default function DepartmentsPage() {
  const { t } = useTranslation();
  const dispatch = useDispatch();
  const can = usePermission();
  const [form] = Form.useForm();
  const { items, loading } = useSelector((state) => state.departments.list);
  const save = useSelector((state) => state.departments.save);
  const [editing, setEditing] = useState(null); // null = fechado; {} = novo; {...} = editar
  const canManage = can('core.departments.manage');

  useEffect(() => {
    dispatch(departments.actions.fetchList());
  }, [dispatch]);

  useSaveFeedback(save, {
    form,
    onSuccess: () => setEditing(null),
    reset: () => dispatch(departments.actions.resetSave()),
  });

  const tree = useMemo(() => toTree(items), [items]);
  const treeOptions = useMemo(() => {
    const map = (nodes) =>
      nodes.map((n) => ({
        value: n.id,
        title: n.name,
        disabled: editing?.id === n.id,
        children: n.children ? map(n.children) : undefined,
      }));
    return map(tree);
  }, [tree, editing]);

  const openForm = (department) => {
    setEditing(department);
    form.resetFields();
    form.setFieldsValue(department);
  };

  const columns = [
    { title: t('departments.fields.name'), dataIndex: 'name' },
    { title: t('departments.fields.manager'), dataIndex: ['manager', 'full_name'], render: (v) => v || '—' },
    { title: t('departments.fields.employees'), dataIndex: 'employees_count', align: 'right' },
    canManage && {
      key: 'actions',
      align: 'right',
      render: (_, row) => (
        <Space>
          <Button
            type="text"
            icon={<EditOutlined />}
            aria-label={t('shared.edit')}
            onClick={() => openForm(row)}
          />
          <ConfirmDelete onConfirm={() => dispatch(departments.actions.remove(row.id))} />
        </Space>
      ),
    },
  ].filter(Boolean);

  return (
    <>
      <PageHeader
        title={t('departments.title')}
        subtitle={t('departments.subtitle')}
        extra={
          canManage && (
            <Button type="primary" icon={<PlusOutlined />} onClick={() => openForm({})}>
              {t('departments.new')}
            </Button>
          )
        }
      />
      <Card>
        <Table
          key={items.length ? 'loaded' : 'empty'}
          rowKey="id"
          loading={loading}
          columns={columns}
          dataSource={tree}
          pagination={false}
          expandable={{ defaultExpandAllRows: true }}
        />
      </Card>
      <Modal
        open={editing !== null}
        title={editing?.id ? t('departments.edit') : t('departments.new')}
        okText={t('shared.save')}
        cancelText={t('shared.cancel')}
        confirmLoading={save.pending}
        onOk={() => form.submit()}
        onCancel={() => setEditing(null)}
        destroyOnHidden
      >
        <Form
          form={form}
          layout="vertical"
          onFinish={(values) => dispatch(departments.actions.save({ id: editing?.id, values }))}
        >
          <Form.Item
            name="name"
            label={t('departments.fields.name')}
            rules={[{ required: true, message: t('validation.required') }]}
          >
            <Input />
          </Form.Item>
          <Form.Item name="parent_id" label={t('departments.fields.parent')}>
            <TreeSelect
              allowClear
              treeDefaultExpandAll
              treeData={treeOptions}
              placeholder={t('departments.noParent')}
            />
          </Form.Item>
          <Form.Item name="manager_employee_id" label={t('departments.fields.manager')}>
            <EmployeeSelect initialOption={editing?.manager} />
          </Form.Item>
        </Form>
      </Modal>
    </>
  );
}
