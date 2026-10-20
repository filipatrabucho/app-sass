import { useEffect } from 'react';
import {
  App,
  Button,
  Card,
  Col,
  Descriptions,
  Empty,
  Form,
  Input,
  Row,
  Select,
  Space,
  Typography,
} from 'antd';
import { useDispatch, useSelector } from 'react-redux';
import { useTranslation } from 'react-i18next';
import { toFormFields } from '@/core/api/formErrors';
import { FileUpload, PageHeader, UserAvatar } from '@/shared/components';
import { formatDate } from '@/shared/format';
import { selectAuth } from '../../auth/selectors';
import { resetProfileState, updateProfile, uploadAvatar } from '../actions';

export default function ProfilePage() {
  const { t } = useTranslation();
  const { message } = App.useApp();
  const dispatch = useDispatch();
  const [form] = Form.useForm();
  const { user, employee } = useSelector(selectAuth);
  const profile = useSelector((state) => state.hr.profile);

  useEffect(() => {
    form.setFieldsValue({
      phone: employee?.phone,
      address: employee?.address || {},
      iban: employee?.iban,
      locale: user?.locale,
    });
  }, [employee, user, form]);

  useEffect(() => {
    if (profile.success) {
      message.success(t('profile.saved'));
      dispatch(resetProfileState());
    } else if (profile.error) {
      if (profile.error.errors) form.setFields(toFormFields(profile.error.errors));
      else message.error(profile.error.message);
      if (profile.error.errors?.avatar) message.error(profile.error.errors.avatar[0]);
      dispatch(resetProfileState());
    }
  }, [profile, message, t, dispatch, form]);

  if (!employee) {
    return (
      <>
        <PageHeader title={t('profile.title')} />
        <Card>
          <Empty description={t('home.noEmployee')} />
        </Card>
      </>
    );
  }

  return (
    <>
      <PageHeader title={t('profile.title')} subtitle={t('profile.subtitle')} />
      <Row gutter={[16, 16]}>
        <Col xs={24} lg={8}>
          <Card className="page-section">
            <div className="profile-photo">
              <UserAvatar size={112} name={employee.full_name} src={employee.avatar_url} />
              <Typography.Title level={4} className="mb-0">
                {employee.full_name}
              </Typography.Title>
              <Typography.Text type="secondary">{employee.position?.name}</Typography.Text>
              <FileUpload
                accept="image/png,image/jpeg,image/webp"
                maxSizeMb={2}
                loading={profile.uploading}
                label={t('profile.changePhoto')}
                onSelect={(file) => dispatch(uploadAvatar(file))}
                onTooLarge={() => message.error(t('shared.fileTooLarge', { max: 2 }))}
              />
              <Typography.Text type="secondary">{t('profile.photoHelp')}</Typography.Text>
            </div>
          </Card>
          <Card title={t('profile.organization')}>
            <Descriptions column={1} size="small">
              <Descriptions.Item label={t('employees.fields.employee_number')}>
                {employee.employee_number || '—'}
              </Descriptions.Item>
              <Descriptions.Item label={t('home.department')}>
                {employee.department?.name || '—'}
              </Descriptions.Item>
              <Descriptions.Item label={t('home.manager')}>
                {employee.manager?.full_name || '—'}
              </Descriptions.Item>
              <Descriptions.Item label={t('home.hireDate')}>
                {formatDate(employee.hire_date)}
              </Descriptions.Item>
              <Descriptions.Item label={t('employees.fields.email')}>
                {employee.email || user.email}
              </Descriptions.Item>
            </Descriptions>
            <Typography.Text type="secondary">{t('profile.readonlyHelp')}</Typography.Text>
          </Card>
        </Col>
        <Col xs={24} lg={16}>
          <Card title={t('profile.contacts')}>
            <Form form={form} layout="vertical" onFinish={(values) => dispatch(updateProfile(values))}>
              <Row gutter={16}>
                <Col xs={24} md={12}>
                  <Form.Item name="phone" label={t('employees.fields.phone')}>
                    <Input inputMode="tel" />
                  </Form.Item>
                </Col>
                <Col xs={24} md={12}>
                  <Form.Item name="iban" label={t('employees.fields.iban')}>
                    <Input />
                  </Form.Item>
                </Col>
                <Col xs={24}>
                  <Form.Item name={['address', 'street']} label={t('employees.fields.street')}>
                    <Input />
                  </Form.Item>
                </Col>
                <Col xs={12} md={8}>
                  <Form.Item name={['address', 'postal_code']} label={t('employees.fields.postal_code')}>
                    <Input />
                  </Form.Item>
                </Col>
                <Col xs={12} md={8}>
                  <Form.Item name={['address', 'city']} label={t('employees.fields.city')}>
                    <Input />
                  </Form.Item>
                </Col>
                <Col xs={24} md={8}>
                  <Form.Item name={['address', 'country']} label={t('employees.fields.country')}>
                    <Input />
                  </Form.Item>
                </Col>
                <Col xs={24} md={12}>
                  <Form.Item name="locale" label={t('profile.language')}>
                    <Select
                      options={[
                        { value: 'pt_PT', label: 'Português (Portugal)' },
                        { value: 'en', label: 'English' },
                      ]}
                    />
                  </Form.Item>
                </Col>
              </Row>
              <Space className="w-full form-actions">
                <Button type="primary" htmlType="submit" loading={profile.pending}>
                  {t('shared.save')}
                </Button>
              </Space>
            </Form>
          </Card>
        </Col>
      </Row>
    </>
  );
}
