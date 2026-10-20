import { useEffect, useState } from 'react';
import {
  App,
  Button,
  Card,
  Checkbox,
  DatePicker,
  Form,
  Input,
  InputNumber,
  Modal,
  Select,
  Space,
  Table,
  Tag,
} from 'antd';
import { EditOutlined, ImportOutlined, PlusOutlined } from '@ant-design/icons';
import { useDispatch, useSelector } from 'react-redux';
import { useTranslation } from 'react-i18next';
import dayjs from 'dayjs';
import { ConfirmDelete, PageHeader } from '@/shared/components';
import useSaveFeedback from '@/shared/hooks/useSaveFeedback';
import usePermission from '../../permissions/usePermission';
import { importHolidays } from '../actions';
import { holidays } from '../resources';

const TYPE_COLORS = { national: 'blue', municipal: 'purple', company: 'green' };

export default function HolidaysPage() {
  const { t } = useTranslation();
  const { message } = App.useApp();
  const dispatch = useDispatch();
  const can = usePermission();
  const [form] = Form.useForm();
  const [year, setYear] = useState(dayjs().year());
  const [editing, setEditing] = useState(null);
  const { items, loading } = useSelector((state) => state.holidays.list);
  const save = useSelector((state) => state.holidays.save);
  const importState = useSelector((state) => state.hr.holidaysImport);
  const canManage = can('core.holidays.manage');
  const type = Form.useWatch('type', form);

  useEffect(() => {
    dispatch(holidays.actions.fetchList({ year }));
  }, [dispatch, year]);

  useEffect(() => {
    if (importState.message) message.info(importState.message);
  }, [importState.message, message]);

  useSaveFeedback(save, {
    form,
    onSuccess: () => setEditing(null),
    reset: () => dispatch(holidays.actions.resetSave()),
  });

  const openForm = (holiday) => {
    setEditing(holiday);
    form.resetFields();
    form.setFieldsValue(
      holiday.id
        ? { ...holiday, date: dayjs(holiday.date) }
        : { type: 'company', recurring: false, date: dayjs(`${year}-01-01`) },
    );
  };

  const submit = (values) =>
    dispatch(
      holidays.actions.save({
        id: editing?.id,
        values: { ...values, date: values.date.format('YYYY-MM-DD') },
      }),
    );

  const columns = [
    { title: t('holidays.fields.date'), dataIndex: 'date', render: (d) => dayjs(d).format('DD/MM/YYYY') },
    {
      title: t('holidays.fields.weekday'),
      dataIndex: 'date',
      key: 'weekday',
      render: (d) => dayjs(d).format('dddd'),
      responsive: ['md'],
    },
    { title: t('holidays.fields.name'), dataIndex: 'name' },
    {
      title: t('holidays.fields.type'),
      dataIndex: 'type',
      render: (v, row) => (
        <Tag color={TYPE_COLORS[v]}>
          {t(`holidays.types.${v}`)}
          {row.region ? ` · ${row.region}` : ''}
        </Tag>
      ),
    },
    {
      title: t('holidays.fields.recurring'),
      dataIndex: 'recurring',
      responsive: ['md'],
      render: (v) => (v ? t('shared.yes') : t('shared.no')),
    },
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
          <ConfirmDelete onConfirm={() => dispatch(holidays.actions.remove(row.id))} />
        </Space>
      ),
    },
  ].filter(Boolean);

  return (
    <>
      <PageHeader
        title={t('holidays.title')}
        subtitle={t('holidays.subtitle')}
        extra={
          <Space wrap>
            <InputNumber
              aria-label={t('holidays.year')}
              min={2000}
              max={2100}
              value={year}
              onChange={(v) => v && setYear(v)}
            />
            {canManage && (
              <>
                <Button
                  icon={<ImportOutlined />}
                  loading={importState.pending}
                  onClick={() => dispatch(importHolidays(year))}
                >
                  {t('holidays.importNational', { year })}
                </Button>
                <Button type="primary" icon={<PlusOutlined />} onClick={() => openForm({})}>
                  {t('holidays.new')}
                </Button>
              </>
            )}
          </Space>
        }
      />
      <Card>
        <Table
          rowKey={(r) => `${r.id}-${r.date}`}
          loading={loading}
          columns={columns}
          dataSource={items}
          pagination={false}
        />
      </Card>
      <Modal
        open={editing !== null}
        title={editing?.id ? t('holidays.edit') : t('holidays.new')}
        okText={t('shared.save')}
        cancelText={t('shared.cancel')}
        confirmLoading={save.pending}
        onOk={() => form.submit()}
        onCancel={() => setEditing(null)}
        destroyOnHidden
      >
        <Form form={form} layout="vertical" onFinish={submit}>
          <Form.Item
            name="date"
            label={t('holidays.fields.date')}
            rules={[{ required: true, message: t('validation.required') }]}
          >
            <DatePicker format="DD/MM/YYYY" className="w-full" />
          </Form.Item>
          <Form.Item
            name="name"
            label={t('holidays.fields.name')}
            rules={[{ required: true, message: t('validation.required') }]}
          >
            <Input />
          </Form.Item>
          <Form.Item name="type" label={t('holidays.fields.type')}>
            <Select
              options={['national', 'municipal', 'company'].map((v) => ({
                value: v,
                label: t(`holidays.types.${v}`),
              }))}
            />
          </Form.Item>
          {type === 'municipal' && (
            <Form.Item
              name="region"
              label={t('holidays.fields.region')}
              rules={[{ required: true, message: t('validation.required') }]}
            >
              <Input />
            </Form.Item>
          )}
          <Form.Item name="recurring" valuePropName="checked">
            <Checkbox>{t('holidays.fields.recurring')}</Checkbox>
          </Form.Item>
        </Form>
      </Modal>
    </>
  );
}
