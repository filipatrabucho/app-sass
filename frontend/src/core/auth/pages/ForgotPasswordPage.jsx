import { Alert, Button, Form, Input, Typography } from 'antd';
import { useDispatch, useSelector } from 'react-redux';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { forgotPasswordRequest } from '../actions';
import { selectAuth } from '../selectors';
import AuthCard from './AuthCard';

export default function ForgotPasswordPage() {
  const { t } = useTranslation();
  const dispatch = useDispatch();
  const { password } = useSelector(selectAuth);

  return (
    <AuthCard title={t('auth.forgot.title')}>
      <Typography.Paragraph type="secondary">{t('auth.forgot.intro')}</Typography.Paragraph>
      {password.done && (
        <Alert type="success" showIcon className="page-section" title={t('auth.forgot.sent')} />
      )}
      {password.error && (
        <Alert type="error" showIcon className="page-section" title={password.error.message} />
      )}
      <Form
        layout="vertical"
        requiredMark={false}
        onFinish={(values) => dispatch(forgotPasswordRequest(values))}
      >
        <Form.Item
          name="email"
          label={t('auth.login.email')}
          rules={[
            { required: true, message: t('validation.required') },
            { type: 'email', message: t('validation.email') },
          ]}
        >
          <Input autoComplete="email" autoFocus />
        </Form.Item>
        <Button type="primary" htmlType="submit" block loading={password.pending}>
          {t('auth.forgot.submit')}
        </Button>
        <div className="text-right">
          <Link to="/login">{t('auth.forgot.back')}</Link>
        </div>
      </Form>
    </AuthCard>
  );
}
