import { useEffect } from 'react';
import { Button, Form, Input } from 'antd';
import { useDispatch, useSelector } from 'react-redux';
import { useTranslation } from 'react-i18next';
import { toFormFields } from '@/core/api/formErrors';
import AuthCard from '@/core/auth/pages/AuthCard';
import { loginRequest } from '../store/actions';

export default function LoginPage() {
  const { t } = useTranslation();
  const dispatch = useDispatch();
  const [form] = Form.useForm();
  const login = useSelector((state) => state.central.auth.login);

  useEffect(() => {
    if (login.error?.errors) form.setFields(toFormFields(login.error.errors));
  }, [login.error, form]);

  return (
    <AuthCard title={t('central:login.title')}>
      <Form form={form} layout="vertical" requiredMark={false} onFinish={(v) => dispatch(loginRequest(v))}>
        <Form.Item
          name="email"
          label={t('auth.login.email')}
          rules={[
            { required: true, message: t('validation.required') },
            { type: 'email', message: t('validation.email') },
          ]}
        >
          <Input autoComplete="username" autoFocus />
        </Form.Item>
        <Form.Item
          name="password"
          label={t('auth.login.password')}
          rules={[{ required: true, message: t('validation.required') }]}
        >
          <Input.Password autoComplete="current-password" />
        </Form.Item>
        <Button type="primary" htmlType="submit" block loading={login.pending}>
          {t('auth.login.submit')}
        </Button>
      </Form>
    </AuthCard>
  );
}
