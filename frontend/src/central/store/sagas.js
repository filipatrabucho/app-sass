import { all, call, put, select, takeLatest } from 'redux-saga/effects';
import { callApi } from '@/core/api/callApi';
import { centralApi } from '../api/centralApi';
import * as actions from './actions';

const toError = (error) => ({ message: error.message, code: error.code, errors: error.errors });

function* bootstrap() {
  try {
    const { admin } = yield call(centralApi.me);
    yield put(actions.authenticated(admin));
  } catch {
    yield put(actions.guest());
  }
}

function* login({ payload }) {
  try {
    const { admin } = yield call(centralApi.login, payload);
    yield put(actions.authenticated(admin));
  } catch (error) {
    yield put(actions.loginFailure(toError(error)));
  }
}

function* logout() {
  try {
    yield call(centralApi.logout);
  } finally {
    yield put(actions.guest());
  }
}

function* fetchModules() {
  try {
    const { data } = yield call(callApi, centralApi.listModules);
    yield put(actions.fetchModulesSuccess(data));
  } catch {
    // tratado globalmente
  }
}

function* fetchTenants({ payload }) {
  try {
    const response = yield call(callApi, centralApi.listTenants, payload);
    yield put(actions.fetchTenantsSuccess(response));
  } catch (error) {
    yield put(actions.fetchTenantsFailure(toError(error)));
  }
}

function* fetchTenant({ payload: id }) {
  try {
    const { data } = yield call(callApi, centralApi.getTenant, id);
    yield put(actions.fetchTenantSuccess(data));
  } catch (error) {
    yield put(actions.fetchTenantFailure(toError(error)));
  }
}

function* createTenant({ payload }) {
  try {
    const { data } = yield call(callApi, centralApi.createTenant, payload);
    yield put(actions.createTenantSuccess(data));
    const params = yield select((state) => state.central.tenants.params);
    yield put(actions.fetchTenantsRequest(params));
  } catch (error) {
    yield put(actions.saveFailure(toError(error)));
  }
}

function* updateTenant({ payload: { id, values } }) {
  try {
    const { data } = yield call(callApi, centralApi.updateTenant, id, values);
    yield put(actions.tenantSaved(data));
  } catch (error) {
    yield put(actions.saveFailure(toError(error)));
  }
}

function* setTenantStatus({ payload: { id, status } }) {
  try {
    const fn = status === 'suspended' ? centralApi.suspendTenant : centralApi.activateTenant;
    const { data } = yield call(callApi, fn, id);
    yield put(actions.tenantSaved(data));
  } catch (error) {
    yield put(actions.saveFailure(toError(error)));
  }
}

function* updateTenantModules({ payload: { id, modules } }) {
  try {
    yield call(callApi, centralApi.updateTenantModules, id, modules);
    const { data } = yield call(callApi, centralApi.getTenant, id);
    yield put(actions.tenantSaved(data));
  } catch (error) {
    yield put(actions.saveFailure(toError(error)));
  }
}

export default function* centralSaga() {
  yield all([
    takeLatest(actions.bootstrapRequest.type, bootstrap),
    takeLatest(actions.loginRequest.type, login),
    takeLatest(actions.logoutRequest.type, logout),
    takeLatest(actions.fetchModulesRequest.type, fetchModules),
    takeLatest(actions.fetchTenantsRequest.type, fetchTenants),
    takeLatest(actions.fetchTenantRequest.type, fetchTenant),
    takeLatest(actions.createTenantRequest.type, createTenant),
    takeLatest(actions.updateTenantRequest.type, updateTenant),
    takeLatest(actions.setTenantStatusRequest.type, setTenantStatus),
    takeLatest(actions.updateTenantModulesRequest.type, updateTenantModules),
  ]);
}
