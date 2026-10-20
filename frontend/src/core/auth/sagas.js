import { all, call, put, takeLatest } from 'redux-saga/effects';
import { callApi } from '../api/callApi';
import { loadActiveModules } from '../modules/sagas';
import { authApi } from './api';
import * as actions from './actions';

const toError = (error) => ({ message: error.message, code: error.code, errors: error.errors });

function* fetchMe() {
  const me = yield call(callApi, authApi.me);
  // Carregar os módulos antes de marcar como autenticado evita "piscar" o menu/rotas.
  yield call(loadActiveModules, me.active_modules);
  yield put(actions.meSuccess(me));
}

function* bootstrap() {
  try {
    yield call(fetchMe);
  } catch {
    yield put(actions.meFailure());
  }
}

function* refreshMe() {
  try {
    yield call(fetchMe);
  } catch {
    // 401 já tratado globalmente por callApi.
  }
}

function* login({ payload }) {
  try {
    yield call(authApi.login, payload);
    yield call(fetchMe);
  } catch (error) {
    yield put(actions.loginFailure(toError(error)));
  }
}

function* logout() {
  try {
    yield call(authApi.logout);
  } finally {
    yield put(actions.loggedOut());
  }
}

function* forgotPassword({ payload }) {
  try {
    yield call(authApi.forgotPassword, payload.email);
    yield put(actions.forgotPasswordDone());
  } catch (error) {
    yield put(actions.passwordFlowFailure(toError(error)));
  }
}

function* resetPassword({ payload }) {
  try {
    yield call(authApi.resetPassword, payload);
    yield put(actions.resetPasswordDone());
  } catch (error) {
    yield put(actions.passwordFlowFailure(toError(error)));
  }
}

export default function* authSaga() {
  yield all([
    takeLatest(actions.bootstrapRequest.type, bootstrap),
    takeLatest(actions.meRequest.type, refreshMe),
    takeLatest(actions.loginRequest.type, login),
    takeLatest(actions.logoutRequest.type, logout),
    takeLatest(actions.forgotPasswordRequest.type, forgotPassword),
    takeLatest(actions.resetPasswordRequest.type, resetPassword),
  ]);
}
