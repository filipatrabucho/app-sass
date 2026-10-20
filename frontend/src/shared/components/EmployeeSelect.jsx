import { useEffect, useMemo } from 'react';
import { Select } from 'antd';
import { useDispatch, useSelector } from 'react-redux';
import { useTranslation } from 'react-i18next';
import { searchEmployees } from '@/core/hr/actions';

/**
 * Select de colaboradores com pesquisa no servidor (via saga -> GET /employees/lookup).
 * `initialOption` ({ id, full_name }) garante que o valor atual tem etiqueta antes de pesquisar.
 */
export default function EmployeeSelect({ value, onChange, initialOption, exclude = [], ...rest }) {
  const { t } = useTranslation();
  const dispatch = useDispatch();
  const { items, loading } = useSelector((state) => state.hr.employeeOptions);

  useEffect(() => {
    dispatch(searchEmployees({}));
  }, [dispatch]);

  const options = useMemo(() => {
    const list = [...items];
    if (initialOption && !list.some((e) => e.id === initialOption.id)) list.unshift(initialOption);
    return list
      .filter((e) => !exclude.includes(e.id))
      .map((e) => ({
        value: e.id,
        label: e.department ? `${e.full_name} · ${e.department}` : e.full_name,
      }));
  }, [items, initialOption, exclude]);

  return (
    <Select
      showSearch={{ filterOption: false, onSearch: (search) => dispatch(searchEmployees({ search })) }}
      allowClear
      value={value}
      onChange={(v) => onChange?.(v ?? null)}
      loading={loading}
      options={options}
      placeholder={t('shared.employeeSelect.placeholder')}
      notFoundContent={t('shared.employeeSelect.empty')}
      {...rest}
    />
  );
}
