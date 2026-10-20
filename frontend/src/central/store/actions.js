import { createAction } from '@reduxjs/toolkit';

// Autenticação
export const bootstrapRequest = createAction('central/bootstrapRequest');
export const authenticated = createAction('central/authenticated');
export const guest = createAction('central/guest');
export const loginRequest = createAction('central/loginRequest');
export const loginFailure = createAction('central/loginFailure');
export const logoutRequest = createAction('central/logoutRequest');

// Catálogo de módulos
export const fetchModulesRequest = createAction('central/fetchModulesRequest');
export const fetchModulesSuccess = createAction('central/fetchModulesSuccess');

// Tenants
export const fetchTenantsRequest = createAction('central/fetchTenantsRequest');
export const fetchTenantsSuccess = createAction('central/fetchTenantsSuccess');
export const fetchTenantsFailure = createAction('central/fetchTenantsFailure');

export const fetchTenantRequest = createAction('central/fetchTenantRequest');
export const fetchTenantSuccess = createAction('central/fetchTenantSuccess');
export const fetchTenantFailure = createAction('central/fetchTenantFailure');

export const createTenantRequest = createAction('central/createTenantRequest');
export const createTenantSuccess = createAction('central/createTenantSuccess');
export const updateTenantRequest = createAction('central/updateTenantRequest');
export const setTenantStatusRequest = createAction('central/setTenantStatusRequest');
export const updateTenantModulesRequest = createAction('central/updateTenantModulesRequest');
export const tenantSaved = createAction('central/tenantSaved');
export const saveFailure = createAction('central/saveFailure');
export const clearSaveState = createAction('central/clearSaveState');
