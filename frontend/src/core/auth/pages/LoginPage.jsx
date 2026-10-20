import { useEffect } from 'react';
import { Alert, Button, Checkbox, Form, Input } from 'antd';
import { useDispatch, useSelector } from 'react-redux';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { toFormFields } from '../../api/formErrors';
import { loginRequest } from '../actions';
import { selectAuth } from '../selectors';
import AuthCard from './AuthCard';

export default function LoginPage() {
  const { t } = useTranslation();
  const dispatch = useDispatch();
  const [form] = Form.useForm();
  const { login, sessionExpired, tenant } = useSelector(selectAuth);

  useEffect(() => {
    if (login.error?.errors) form.setFields(toFormFields(login.error.errors));
  }, [login.error, form]);

  return (
    <AuthCard title={tenant?.name || t('auth.login.title')}>
      {sessionExpired && (
        <Alert type="warning" showIcon className="page-section" title={t('auth.sessionExpired')} />
      )}
      <Form
        form={form}
        layout="vertical"
        requiredMark={false}
        initialValues={{ remember: true }}
        onFinish={(values) => dispatch(loginRequest(values))}
      >
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
        <Form.Item name="remember" valuePropName="checked">
          <Checkbox>{t('auth.login.remember')}</Checkbox>
        </Form.Item>
        <Button type="primary" htmlType="submit" block loading={login.pending}>
          {t('auth.login.submit')}
        </Button>
        <div className="text-right">
          <Link to="/recuperar-password">{t('auth.login.forgot')}</Link>
        </div>
      </Form>
    </AuthCard>
  );
}
