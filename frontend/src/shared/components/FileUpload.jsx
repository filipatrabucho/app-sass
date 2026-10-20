import { Button, Upload } from 'antd';
import { UploadOutlined } from '@ant-design/icons';
import { useTranslation } from 'react-i18next';

/**
 * Seleção de ficheiro sem upload automático: entrega o File ao `onSelect`, que despacha a
 * action correspondente (o envio é feito pela saga através do api gateway).
 */
export default function FileUpload({ onSelect, accept, loading, label, maxSizeMb = 10, onTooLarge }) {
  const { t } = useTranslation();

  return (
    <Upload
      accept={accept}
      showUploadList={false}
      beforeUpload={(file) => {
        if (file.size > maxSizeMb * 1024 * 1024) onTooLarge?.(file);
        else onSelect(file);
        return Upload.LIST_IGNORE;
      }}
    >
      <Button icon={<UploadOutlined />} loading={loading}>
        {label || t('shared.upload')}
      </Button>
    </Upload>
  );
}
