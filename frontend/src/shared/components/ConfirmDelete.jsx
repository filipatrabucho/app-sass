import { Button, Popconfirm } from 'antd';
import { DeleteOutlined } from '@ant-design/icons';
import { useTranslation } from 'react-i18next';

export default function ConfirmDelete({ onConfirm, loading, title, iconOnly = true }) {
  const { t } = useTranslation();

  return (
    <Popconfirm
      title={title || t('shared.confirmDelete')}
      okText={t('shared.delete')}
      cancelText={t('shared.cancel')}
      onConfirm={onConfirm}
    >
      <Button danger type="text" icon={<DeleteOutlined />} loading={loading} aria-label={t('shared.delete')}>
        {!iconOnly && t('shared.delete')}
      </Button>
    </Popconfirm>
  );
}
