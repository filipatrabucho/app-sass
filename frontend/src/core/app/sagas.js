import { put, takeLatest } from 'redux-saga/effects';
import { apiModuleNotLicensed } from '../api/apiActions';
import { meRequest } from '../auth/actions';

// Quando o backend recusa um módulo, atualizamos /me para refletir o menu real.
function* onModuleNotLicensed() {
  yield put(meRequest());
}

export default function* appSaga() {
  yield takeLatest(apiModuleNotLicensed.type, onModuleNotLicensed);
}
