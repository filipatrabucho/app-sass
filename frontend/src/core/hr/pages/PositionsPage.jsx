import { useEffect, useState } from 'react';
import { Button, Card, Form, Input, Modal, Space, Table } from 'antd';
import { EditOutlined, PlusOutlined } from '@ant-design/icons';
import { useDispatch, useSelector } from 'react-redux';
import { useTranslation } from 'react-i18next';
import { ConfirmDelete, PageHeader } from '@/shared/components';
import useSaveFeedback from '@/shared/hooks/useSaveFeedback';
import { positions } from '../resources';

export default function PositionsPage() {
  const { t } = useTranslation();
  const dispatch = useDispatch();
  const [form] = Form.useForm();
  const { items, loading } = useSelector((state) => state.positions.list);
  const save = useSelector((state) => state.positions.save);
  const [editing, setEditing] = useState(null);

  useEffect(() => {
    dispatch(positions.actions.fetchList());
  }, [dispatch]);

  useSaveFeedback(save, {
    form,
    onSuccess: () => setEditing(null),
    reset: () => dispatch(positions.actions.resetSave()),
  });

  const openForm = (position) => {
    setEditing(position);
    form.resetFields();
    form.setFieldsValue(position);
  };

  const columns = [
    { title: t('positions.fields.name'), dataIndex: 'name' },
    { title: t('positions.fields.employees'), dataIndex: 'employees_count', align: 'right' },
    {
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
          <ConfirmDelete onConfirm={() => dispatch(positions.actions.remove(row.id))} />
        </Space>
      ),
    },
  ];

  return (
    <>
      <PageHeader
        title={t('positions.title')}
        subtitle={t('positions.subtitle')}
        extra={
          <Button type="primary" icon={<PlusOutlined />} onClick={() => openForm({})}>
            {t('positions.new')}
          </Button>
        }
      />
      <Card>
        <Table rowKey="id" loading={loading} columns={columns} dataSource={items} pagination={false} />
      </Card>
      <Modal
        open={editing !== null}
        title={editing?.id ? t('positions.edit') : t('positions.new')}
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
          onFinish={(values) => dispatch(positions.actions.save({ id: editing?.id, values }))}
        >
          <Form.Item
            name="name"
            label={t('positions.fields.name')}
            rules={[{ required: true, message: t('validation.required') }]}
          >
            <Input />
          </Form.Item>
        </Form>
      </Modal>
    </>
  );
}
