import { useEffect } from 'react';
import {
  Alert,
  Button,
  Col,
  DatePicker,
  Divider,
  Drawer,
  Form,
  Input,
  Row,
  Select,
  Space,
  Switch,
} from 'antd';
import { useDispatch, useSelector } from 'react-redux';
import { useTranslation } from 'react-i18next';
import dayjs from 'dayjs';
import { EmployeeSelect } from '@/shared/components';
import useSaveFeedback from '@/shared/hooks/useSaveFeedback';
import usePermission from '../../permissions/usePermission';
import { departments, employees, positions } from '../resources';

const ROLES = ['employee', 'manager', 'hr', 'admin'];
const DATE_FIELDS = ['birth_date', 'hire_date'];

const toForm = (employee) => ({
  ...employee,
  ...Object.fromEntries(DATE_FIELDS.map((f) => [f, employee?.[f] ? dayjs(employee[f]) : null])),
  role: employee?.user?.roles?.[0],
  active: employee?.user?.active ?? true,
  address: employee?.address || {},
});

const fromForm = (values) => ({
  ...values,
  ...Object.fromEntries(DATE_FIELDS.map((f) => [f, values[f] ? values[f].format('YYYY-MM-DD') : null])),
});

/**
 * Criar/editar colaborador (RH). `employee` = null cria um novo.
 */
export default function EmployeeFormDrawer({ open, employee, onClose }) {
  const { t } = useTranslation();
  const dispatch = useDispatch();
  const can = usePermission();
  const [form] = Form.useForm();
  const save = useSelector((state) => state.employees.save);
  const departmentList = useSelector((state) => state.departments.list.items);
  const positionList = useSelector((state) => state.positions.list.items);
  const createAccount = Form.useWatch('create_account', form);
  const hasAccount = Boolean(employee?.user);

  useEffect(() => {
    if (!open) return;
    dispatch(departments.actions.fetchList());
    dispatch(positions.actions.fetchList());
    form.resetFields();
    form.setFieldsValue(
      employee
        ? toForm(employee)
        : { status: 'active', create_account: true, role: 'employee', address: { country: 'Portugal' } },
    );
  }, [open, employee, dispatch, form]);

  useSaveFeedback(save, {
    form,
    successMessage: employee ? t('employees.updated') : t('employees.created'),
    onSuccess: () => onClose(true),
    reset: () => dispatch(employees.actions.resetSave()),
  });

  const roles = ROLES.filter((r) => r !== 'admin' || can('core.users.manage'));
  const required = [{ required: true, message: t('validation.required') }];

  return (
    <Drawer
      open={open}
      size="large"
      title={employee ? t('employees.edit') : t('employees.new')}
      onClose={() => onClose(false)}
      destroyOnHidden
      extra={
        <Space>
          <Button onClick={() => onClose(false)}>{t('shared.cancel')}</Button>
          <Button type="primary" loading={save.pending} onClick={() => form.submit()}>
            {t('shared.save')}
          </Button>
        </Space>
      }
    >
      <Form
        form={form}
        layout="vertical"
        onFinish={(values) =>
          dispatch(employees.actions.save({ id: employee?.id, values: fromForm(values) }))
        }
      >
        <Divider titlePlacement="start">{t('employees.sections.personal')}</Divider>
        <Row gutter={16}>
          <Col xs={24} md={12}>
            <Form.Item name="first_name" label={t('employees.fields.first_name')} rules={required}>
              <Input />
            </Form.Item>
          </Col>
          <Col xs={24} md={12}>
            <Form.Item name="last_name" label={t('employees.fields.last_name')} rules={required}>
              <Input />
            </Form.Item>
          </Col>
          <Col xs={24} md={12}>
            <Form.Item
              name="email"
              label={t('employees.fields.email')}
              rules={[{ type: 'email', message: t('validation.email') }]}
            >
              <Input />
            </Form.Item>
          </Col>
          <Col xs={24} md={12}>
            <Form.Item name="phone" label={t('employees.fields.phone')}>
              <Input />
            </Form.Item>
          </Col>
          <Col xs={12} md={8}>
            <Form.Item name="nif" label={t('employees.fields.nif')}>
              <Input />
            </Form.Item>
          </Col>
          <Col xs={12} md={8}>
            <Form.Item name="birth_date" label={t('employees.fields.birth_date')}>
              <DatePicker format="DD/MM/YYYY" className="w-full" />
            </Form.Item>
          </Col>
          <Col xs={24} md={8}>
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
        </Row>

        <Divider titlePlacement="start">{t('employees.sections.organization')}</Divider>
        <Row gutter={16}>
          <Col xs={12} md={8}>
            <Form.Item name="employee_number" label={t('employees.fields.employee_number')}>
              <Input />
            </Form.Item>
          </Col>
          <Col xs={12} md={8}>
            <Form.Item name="hire_date" label={t('employees.fields.hire_date')}>
              <DatePicker format="DD/MM/YYYY" className="w-full" />
            </Form.Item>
          </Col>
          <Col xs={24} md={8}>
            <Form.Item name="status" label={t('employees.fields.status')}>
              <Select options={['active', 'inactive'].map((s) => ({ value: s, label: t(`status.${s}`) }))} />
            </Form.Item>
          </Col>
          <Col xs={24} md={12}>
            <Form.Item name="department_id" label={t('employees.fields.department')}>
              <Select allowClear options={departmentList.map((d) => ({ value: d.id, label: d.name }))} />
            </Form.Item>
          </Col>
          <Col xs={24} md={12}>
            <Form.Item name="position_id" label={t('employees.fields.position')}>
              <Select
                allowClear
                showSearch={{ optionFilterProp: 'label' }}
                options={positionList.map((p) => ({ value: p.id, label: p.name }))}
              />
            </Form.Item>
          </Col>
          <Col xs={24}>
            <Form.Item name="manager_id" label={t('employees.fields.manager')}>
              <EmployeeSelect initialOption={employee?.manager} exclude={employee ? [employee.id] : []} />
            </Form.Item>
          </Col>
        </Row>

        <Divider titlePlacement="start">{t('employees.sections.access')}</Divider>
        {!hasAccount && (
          <Form.Item
            name="create_account"
            label={t('employees.fields.create_account')}
            valuePropName="checked"
            extra={t('employees.createAccountHelp')}
          >
            <Switch />
          </Form.Item>
        )}
        {(hasAccount || createAccount) && (
          <Row gutter={16}>
            <Col xs={24} md={12}>
              <Form.Item name="role" label={t('employees.fields.role')}>
                <Select options={roles.map((r) => ({ value: r, label: t(`employees.roles.${r}`) }))} />
              </Form.Item>
            </Col>
            {hasAccount && (
              <Col xs={24} md={12}>
                <Form.Item name="active" label={t('employees.fields.account_active')} valuePropName="checked">
                  <Switch />
                </Form.Item>
              </Col>
            )}
          </Row>
        )}
        {save.error && !save.error.errors && <Alert type="error" showIcon title={save.error.message} />}
      </Form>
    </Drawer>
  );
}
