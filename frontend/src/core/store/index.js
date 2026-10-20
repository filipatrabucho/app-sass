import { all } from 'redux-saga/effects';
import authReducer from '../auth/reducer';
import authSaga from '../auth/sagas';
import appReducer from '../app/reducer';
import appSaga from '../app/sagas';
import { createAppStore } from './createStore';

function* rootSaga() {
  yield all([authSaga(), appSaga()]);
}

const store = createAppStore({
  reducers: { auth: authReducer, app: appReducer },
  rootSaga,
});

export default store;
