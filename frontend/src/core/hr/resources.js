import { createResource } from '../store/createResource';
import { auditApi, departmentsApi, employeesApi, holidaysApi, positionsApi } from './api';

/** Recursos CRUD do Core de RH (reducers em state.<nome>). */
export const employees = createResource('employees', employeesApi);
export const departments = createResource('departments', departmentsApi);
export const positions = createResource('positions', positionsApi);
export const holidays = createResource('holidays', holidaysApi);
export const audit = createResource('audit', auditApi);

export const hrResources = [employees, departments, positions, holidays, audit];
