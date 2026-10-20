import { createReducer } from '@reduxjs/toolkit';
import { apiUnauthorized } from '@/core/api/apiActions';
import * as actions from './actions';

const initialState = {
  auth: { status: 'idle', admin: null, login: { pending: false, error: null } },
  modules: [],
  tenants: { items: [], meta: null, loading: false, params: {} },
  tenant: { data: null, loading: false, error: null },
  // Estado de gravação partilhado pelos formulários (criar/editar/módulos).
  save: { pending: false, error: null, success: null },
};

export default createReducer(initialState, (builder) => {
  builder
    .addCase(actions.bootstrapRequest, (state) => {
      state.auth.status = 'checking';
    })
    .addCase(actions.authenticated, (state, { payload }) => {
      state.auth = { status: 'authenticated', admin: payload, login: { pending: false, error: null } };
    })
    .addCase(actions.guest, (state) => {
      state.auth.status = 'guest';
      state.auth.admin = null;
    })
    .addCase(apiUnauthorized, () => ({ ...initialState, auth: { ...initialState.auth, status: 'guest' } }))
    .addCase(actions.loginRequest, (state) => {
      state.auth.login = { pending: true, error: null };
    })
    .addCase(actions.loginFailure, (state, { payload }) => {
      state.auth.login = { pending: false, error: payload };
    })
    .addCase(actions.fetchModulesSuccess, (state, { payload }) => {
      state.modules = payload;
    })
    .addCase(actions.fetchTenantsRequest, (state, { payload }) => {
      state.tenants.loading = true;
      state.tenants.params = payload || {};
    })
    .addCase(actions.fetchTenantsSuccess, (state, { payload }) => {
      state.tenants = { ...state.tenants, items: payload.data, meta: payload.meta, loading: false };
    })
    .addCase(actions.fetchTenantsFailure, (state) => {
      state.tenants.loading = false;
    })
    .addCase(actions.fetchTenantRequest, (state) => {
      state.tenant = { data: null, loading: true, error: null };
    })
    .addCase(actions.fetchTenantSuccess, (state, { payload }) => {
      state.tenant = { data: payload, loading: false, error: null };
    })
    .addCase(actions.fetchTenantFailure, (state, { payload }) => {
      state.tenant = { data: null, loading: false, error: payload };
    })
    .addCase(actions.createTenantSuccess, (state, { payload }) => {
      state.save = { pending: false, error: null, success: 'created' };
      state.tenant.data = payload;
    })
    .addCase(actions.tenantSaved, (state, { payload }) => {
      state.save = { pending: false, error: null, success: 'saved' };
      state.tenant.data = payload;
    })
    .addCase(actions.saveFailure, (state, { payload }) => {
      state.save = { pending: false, error: payload, success: null };
    })
    .addCase(actions.clearSaveState, (state) => {
      state.save = initialState.save;
    })
    .addMatcher(
      (action) =>
        [
          actions.createTenantRequest.type,
          actions.updateTenantRequest.type,
          actions.setTenantStatusRequest.type,
          actions.updateTenantModulesRequest.type,
        ].includes(action.type),
      (state) => {
        state.save = { pending: true, error: null, success: null };
      },
    );
});
