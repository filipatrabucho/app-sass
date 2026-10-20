import { useEffect } from 'react';
import { App } from 'antd';
import { useTranslation } from 'react-i18next';
import { toFormFields } from '@/core/api/formErrors';

/**
 * Liga o estado `save` de um recurso a um formulário AntD:
 * 422 -> erros nos campos; outros erros -> mensagem; sucesso -> mensagem + onSuccess.
 */
export default function useSaveFeedback(save, { form, onSuccess, reset, successMessage } = {}) {
  const { message } = App.useApp();
  const { t } = useTranslation();

  useEffect(() => {
    if (save.success) {
      message.success(successMessage || t('shared.saved'));
      onSuccess?.(save.success);
      reset?.();
    } else if (save.error) {
      if (save.error.errors && form && save.error.code === 'VALIDATION_ERROR') {
        form.setFields(toFormFields(save.error.errors));
      } else {
        message.error(save.error.message || t('errors.generic'));
      }
      reset?.();
    }
  }, [save.success, save.error]); // eslint-disable-line react-hooks/exhaustive-deps
}
