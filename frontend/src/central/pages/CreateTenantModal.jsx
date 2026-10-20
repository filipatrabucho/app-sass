import { useEffect } from 'react';
import { Checkbox, Col, Divider, Form, Input, Modal, Row } from 'antd';
import { useDispatch, useSelector } from 'react-redux';
import { useTranslation } from 'react-i18next';
import { toFormFields } from '@/core/api/formErrors';
import { clearSaveState, createTenantRequest } from '../store/actions';
import { tenantBaseDomain } from '../tenantDomain';

export default function CreateTenantModal({ open, onClose }) {
  const { t } = useTranslation(['central', 'core']);
  const dispatch = useDispatch();
  const [form] = Form.useForm();
  const save = useSelector((state) => state.central.save);
  const modules = useSelector((state) => state.central.modules);
  const subdomain = Form.useWatch('subdomain', form);

  useEffect(() => {
    if (save.error?.errors) form.setFields(toFormFields(save.error.errors));
  }, [save.error, form]);

  useEffect(() => {
    if (open && save.success === 'created') {
      form.resetFields();
      onClose(true);
    }
  }, [save.success, open, form, onClose]);

  const required = { required: true, message: t('core:validation.required') };

  return (
    <Modal
      open={open}
      title={t('tenants.new')}
      okText={t('form.create')}
      cancelText={t('form.cancel')}
      confirmLoading={save.pending}
      onOk={() => form.submit()}
      onCancel={() => {
        dispatch(clearSaveState());
        onClose(false);
      }}
      width={640}
      destroyOnHidden
    >
      <Form
        form={form}
        layout="vertical"
        requiredMark={false}
        onFinish={(values) => dispatch(createTenantRequest(values))}
      >
        <Divider titlePlacement="start">{t('form.company')}</Divider>
        <Row gutter={16}>
          <Col xs={24} md={16}>
            <Form.Item name="name" label={t('form.name')} rules={[required]}>
              <Input />
            </Form.Item>
          </Col>
          <Col xs={24} md={8}>
            <Form.Item name="nif" label={t('form.nif')}>
              <Input />
            </Form.Item>
          </Col>
          <Col xs={24} md={12}>
            <Form.Item
              name="subdomain"
              label={t('form.subdomain')}
              rules={[required]}
              extra={t('form.subdomainHelp', { domain: `${subdomain || '…'}.${tenantBaseDomain()}` })}
            >
              <Input />
            </Form.Item>
          </Col>
          <Col xs={24} md={12}>
            <Form.Item name="contact_email" label={t('form.contact_email')} rules={[{ type: 'email' }]}>
              <Input />
            </Form.Item>
          </Col>
        </Row>

        <Divider titlePlacement="start">{t('form.admin')}</Divider>
        <Row gutter={16}>
          <Col xs={24} md={12}>
            <Form.Item name={['admin', 'name']} label={t('form.admin_name')} rules={[required]}>
              <Input />
            </Form.Item>
          </Col>
          <Col xs={24} md={12}>
            <Form.Item
              name={['admin', 'email']}
              label={t('form.admin_email')}
              rules={[required, { type: 'email', message: t('core:validation.email') }]}
            >
              <Input />
            </Form.Item>
          </Col>
          <Col xs={24} md={12}>
            <Form.Item name={['admin', 'password']} label={t('form.admin_password')} rules={[required]}>
              <Input.Password autoComplete="new-password" />
            </Form.Item>
          </Col>
        </Row>

        <Form.Item name="modules" label={t('form.modules')}>
          <Checkbox.Group options={modules.map((m) => ({ label: m.name, value: m.key }))} />
        </Form.Item>
      </Form>
    </Modal>
  );
}
