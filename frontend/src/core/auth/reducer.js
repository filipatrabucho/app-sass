import { createReducer } from '@reduxjs/toolkit';
import { apiUnauthorized } from '../api/apiActions';
import * as actions from './actions';

const initialState = {
  // idle -> checking -> authenticated | guest
  status: 'idle',
  user: null,
  employee: null,
  roles: [],
  permissions: [],
  activeModules: [],
  tenant: null,
  sessionExpired: false,
  login: { pending: false, error: null },
  password: { pending: false, done: false, error: null },
};

const reducer = createReducer(initialState, (builder) => {
  builder
    .addCase(actions.bootstrapRequest, (state) => {
      state.status = 'checking';
    })
    .addCase(actions.loginRequest, (state) => {
      state.login = { pending: true, error: null };
    })
    .addCase(actions.loginFailure, (state, { payload }) => {
      state.login = { pending: false, error: payload };
    })
    .addCase(actions.meSuccess, (state, { payload }) => {
      state.status = 'authenticated';
      state.user = payload.user;
      state.employee = payload.employee;
      state.roles = payload.roles;
      state.permissions = payload.permissions;
      state.activeModules = payload.active_modules;
      state.tenant = payload.tenant;
      state.sessionExpired = false;
      state.login = { pending: false, error: null };
    })
    .addCase(actions.meFailure, (state) => {
      state.status = 'guest';
    })
    .addCase(apiUnauthorized, (state) => ({
      ...initialState,
      status: 'guest',
      sessionExpired: state.status === 'authenticated',
    }))
    .addCase(actions.loggedOut, () => ({ ...initialState, status: 'guest' }))
    .addCase(actions.forgotPasswordRequest, (state) => {
      state.password = { pending: true, done: false, error: null };
    })
    .addCase(actions.resetPasswordRequest, (state) => {
      state.password = { pending: true, done: false, error: null };
    })
    .addCase(actions.forgotPasswordDone, (state) => {
      state.password = { pending: false, done: true, error: null };
    })
    .addCase(actions.resetPasswordDone, (state) => {
      state.password = { pending: false, done: true, error: null };
    })
    .addCase(actions.passwordFlowFailure, (state, { payload }) => {
      state.password = { pending: false, done: false, error: payload };
    });
});

export default reducer;
