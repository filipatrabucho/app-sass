import { all, call, delay, fork, put, select, takeLatest, takeEvery } from 'redux-saga/effects';
import { callApi } from '../api/callApi';
import { meSuccess, loggedOut } from '../auth/actions';
import { employeesApi, holidaysApi, notificationsApi, profileApi } from './api';
import { holidays, hrResources } from './resources';
import * as actions from './actions';
import { authApi } from '../auth/api';

const toError = (error) => ({ message: error.message, code: error.code, errors: error.errors });
const NOTIFICATIONS_POLL_MS = 60000;

function* searchEmployees({ payload }) {
  yield delay(250); // debounce (takeLatest cancela pesquisas anteriores)
  try {
    const { data } = yield call(callApi, employeesApi.lookup, payload || {});
    yield put(actions.searchEmployeesSuccess(data));
  } catch {
    yield put(actions.searchEmployeesSuccess([]));
  }
}

function* fetchAttachments({ payload: employeeId }) {
  try {
    const { data } = yield call(callApi, employeesApi.attachments, employeeId);
    yield put(actions.fetchAttachmentsSuccess(data));
  } catch (error) {
    yield put(actions.attachmentsFailure(toError(error)));
  }
}

function* uploadAttachment({ payload: { employeeId, file } }) {
  try {
    yield call(callApi, employeesApi.uploadAttachment, employeeId, file);
    yield call(fetchAttachments, { payload: employeeId });
  } catch (error) {
    yield put(actions.attachmentsFailure(toError(error)));
  }
}

function* removeAttachment({ payload: { employeeId, attachmentId } }) {
  try {
    yield call(callApi, employeesApi.removeAttachment, employeeId, attachmentId);
    yield call(fetchAttachments, { payload: employeeId });
  } catch (error) {
    yield put(actions.attachmentsFailure(toError(error)));
  }
}

function* importHolidays({ payload: year }) {
  try {
    const { message } = yield call(callApi, holidaysApi.importNational, year);
    yield put(actions.importHolidaysDone(message));
    yield put(holidays.actions.fetchList({ year }));
  } catch (error) {
    yield put(actions.importHolidaysDone(error.message));
  }
}

function* fetchNotifications({ payload }) {
  try {
    const response = yield call(callApi, notificationsApi.list, payload || {});
    yield put(actions.fetchNotificationsSuccess(response));
  } catch {
    // 401 tratado globalmente
  }
}

function* markRead({ payload: id }) {
  try {
    const { unread_count: count } = yield call(callApi, notificationsApi.read, id);
    yield put(actions.unreadCountChanged({ id, count }));
  } catch {
    // ignorar
  }
}

function* markAllRead() {
  try {
    yield call(callApi, notificationsApi.readAll);
    yield put(actions.unreadCountChanged({ count: 0 }));
  } catch {
    // ignorar
  }
}

/** Atualiza as notificações (sino do cabeçalho) enquanto houver sessão. */
function* pollNotifications() {
  while (true) {
    const status = yield select((state) => state.auth.status);
    if (status !== 'authenticated') return;
    yield call(fetchNotifications, { payload: { per_page: 10 } });
    yield delay(NOTIFICATIONS_POLL_MS);
  }
}

function* refreshMe() {
  const me = yield call(callApi, authApi.me);
  yield put(meSuccess(me));
}

function* updateProfile({ payload }) {
  try {
    const me = yield call(callApi, profileApi.update, payload);
    yield put(meSuccess(me));
    yield put(actions.profileSaved());
  } catch (error) {
    yield put(actions.profileFailure(toError(error)));
  }
}

function* uploadAvatar({ payload: file }) {
  try {
    yield call(callApi, profileApi.avatar, file);
    yield call(refreshMe);
    yield put(actions.profileSaved());
  } catch (error) {
    yield put(actions.profileFailure(toError(error)));
  }
}

export default function* hrSaga() {
  yield all([
    ...hrResources.map((r) => fork(r.saga)),
    takeLatest(actions.searchEmployees.type, searchEmployees),
    takeLatest(actions.fetchAttachments.type, fetchAttachments),
    takeEvery(actions.uploadAttachment.type, uploadAttachment),
    takeEvery(actions.removeAttachment.type, removeAttachment),
    takeLatest(actions.importHolidays.type, importHolidays),
    takeLatest(actions.fetchNotifications.type, fetchNotifications),
    takeEvery(actions.markNotificationRead.type, markRead),
    takeLatest(actions.markAllNotificationsRead.type, markAllRead),
    takeLatest([meSuccess.type, loggedOut.type], pollNotifications),
    takeLatest(actions.updateProfile.type, updateProfile),
    takeLatest(actions.uploadAvatar.type, uploadAvatar),
  ]);
}
