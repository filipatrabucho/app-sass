import { useEffect } from 'react';
import { Alert, Button, Form, Input } from 'antd';
import { useDispatch, useSelector } from 'react-redux';
import { Link, useSearchParams } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { toFormFields } from '../../api/formErrors';
import { resetPasswordRequest } from '../actions';
import { selectAuth } from '../selectors';
import AuthCard from './AuthCard';

export default function ResetPasswordPage() {
  const { t } = useTranslation();
  const dispatch = useDispatch();
  const [form] = Form.useForm();
  const [params] = useSearchParams();
  const { password } = useSelector(selectAuth);

  useEffect(() => {
    if (password.error?.errors) form.setFields(toFormFields(password.error.errors));
  }, [password.error, form]);

  const submit = (values) =>
    dispatch(resetPasswordRequest({ ...values, token: params.get('token'), email: params.get('email') }));

  return (
    <AuthCard title={t('auth.reset.title')}>
      {password.done ? (
        <Alert
          type="success"
          showIcon
          title={t('auth.reset.success')}
          action={<Link to="/login">{t('auth.login.submit')}</Link>}
        />
      ) : (
        <Form form={form} layout="vertical" requiredMark={false} onFinish={submit}>
          {password.error && !password.error.errors && (
            <Alert type="error" showIcon className="page-section" title={password.error.message} />
          )}
          <Form.Item
            name="password"
            label={t('auth.reset.password')}
            rules={[{ required: true, message: t('validation.required') }]}
          >
            <Input.Password autoComplete="new-password" />
          </Form.Item>
          <Form.Item
            name="password_confirmation"
            label={t('auth.reset.confirm')}
            dependencies={['password']}
            rules={[
              { required: true, message: t('validation.required') },
              ({ getFieldValue }) => ({
                validator: (_, value) =>
                  !value || getFieldValue('password') === value
                    ? Promise.resolve()
                    : Promise.reject(new Error(t('auth.reset.mismatch'))),
              }),
            ]}
          >
            <Input.Password autoComplete="new-password" />
          </Form.Item>
          <Button type="primary" htmlType="submit" block loading={password.pending}>
            {t('auth.reset.submit')}
          </Button>
        </Form>
      )}
    </AuthCard>
  );
}
