import { all, call, put, takeLatest } from 'redux-saga/effects';
import { callApi } from '@/core/api/callApi';
import { vacationsApi } from '../api/vacationsApi';
import * as actions from './actions';

function* fetchMine() {
  try {
    const response = yield call(callApi, vacationsApi.listMine);
    yield put(actions.fetchMyVacationsSuccess(response.data));
  } catch (error) {
    yield put(actions.fetchMyVacationsFailure({ message: error.message, code: error.code }));
  }
}

export default function* vacationsSaga() {
  yield all([takeLatest(actions.fetchMyVacationsRequest.type, fetchMine)]);
}
