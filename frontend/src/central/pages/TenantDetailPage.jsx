import { useEffect } from 'react';
import { App, Breadcrumb, Button, Card, Col, Form, Input, Popconfirm, Row, Space, Spin } from 'antd';
import { useDispatch, useSelector } from 'react-redux';
import { Link, useParams } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { toFormFields } from '@/core/api/formErrors';
import { PageHeader, StatusTag } from '@/shared/components';
import {
  clearSaveState,
  fetchModulesRequest,
  fetchTenantRequest,
  setTenantStatusRequest,
  updateTenantRequest,
} from '../store/actions';
import { tenantUrl } from '../tenantDomain';
import TenantModulesForm from './TenantModulesForm';

export default function TenantDetailPage() {
  const { id } = useParams();
  const { t } = useTranslation(['central', 'core']);
  const { message } = App.useApp();
  const dispatch = useDispatch();
  const [form] = Form.useForm();
  const { data: tenant, loading } = useSelector((state) => state.central.tenant);
  const save = useSelector((state) => state.central.save);

  useEffect(() => {
    dispatch(fetchTenantRequest(id));
    dispatch(fetchModulesRequest());
  }, [dispatch, id]);

  useEffect(() => {
    if (tenant)
      form.setFieldsValue({ name: tenant.name, nif: tenant.nif, contact_email: tenant.contact_email });
  }, [tenant, form]);

  useEffect(() => {
    if (save.success === 'saved') {
      message.success(t('tenant.saved'));
      dispatch(clearSaveState());
    }
    if (save.error?.errors && save.error.code === 'VALIDATION_ERROR')
      form.setFields(toFormFields(save.error.errors));
  }, [save, message, t, dispatch, form]);

  if (loading || !tenant) {
    return <Spin />;
  }

  const suspended = tenant.status === 'suspended';
  const domain = tenant.domains?.[0];

  return (
    <>
      <Breadcrumb
        className="page-section"
        items={[{ title: <Link to="/">{t('tenant.back')}</Link> }, { title: tenant.name }]}
      />
      <PageHeader
        title={
          <Space>
            {tenant.name}
            <StatusTag status={tenant.status} />
          </Space>
        }
        subtitle={domain}
        extra={
          <Space wrap>
            {domain && (
              <Button href={tenantUrl(domain)} target="_blank" rel="noreferrer">
                {t('tenant.open')}
              </Button>
            )}
            {suspended ? (
              <Button onClick={() => dispatch(setTenantStatusRequest({ id, status: 'active' }))}>
                {t('tenant.activate')}
              </Button>
            ) : (
              <Popconfirm
                title={t('tenant.suspendConfirm')}
                onConfirm={() => dispatch(setTenantStatusRequest({ id, status: 'suspended' }))}
              >
                <Button danger>{t('tenant.suspend')}</Button>
              </Popconfirm>
            )}
          </Space>
        }
      />

      <Card title={t('tenant.details')} className="page-section">
        <Form
          form={form}
          layout="vertical"
          onFinish={(values) => dispatch(updateTenantRequest({ id, values }))}
        >
          <Row gutter={16}>
            <Col xs={24} md={10}>
              <Form.Item
                name="name"
                label={t('form.name')}
                rules={[{ required: true, message: t('core:validation.required') }]}
              >
                <Input />
              </Form.Item>
            </Col>
            <Col xs={24} md={6}>
              <Form.Item name="nif" label={t('form.nif')}>
                <Input />
              </Form.Item>
            </Col>
            <Col xs={24} md={8}>
              <Form.Item name="contact_email" label={t('form.contact_email')} rules={[{ type: 'email' }]}>
                <Input />
              </Form.Item>
            </Col>
          </Row>
          <div className="text-right">
            <Button type="primary" htmlType="submit" loading={save.pending}>
              {t('form.save')}
            </Button>
          </div>
        </Form>
      </Card>

      <Card title={t('modules.title')}>
        <TenantModulesForm tenant={tenant} />
      </Card>
    </>
  );
}
