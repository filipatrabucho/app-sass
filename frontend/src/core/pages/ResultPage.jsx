import { Button, Result } from 'antd';
import { useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';

export default function ResultPage({ status, i18nKey }) {
  const { t } = useTranslation();
  const navigate = useNavigate();

  return (
    <Result
      status={status}
      title={t(`${i18nKey}.title`)}
      subTitle={t(`${i18nKey}.subtitle`)}
      extra={
        <Button type="primary" onClick={() => navigate('/')}>
          {t('errors.backHome')}
        </Button>
      }
    />
  );
}
