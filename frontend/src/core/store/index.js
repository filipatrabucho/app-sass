import { all } from 'redux-saga/effects';
import authReducer from '../auth/reducer';
import authSaga from '../auth/sagas';
import appReducer from '../app/reducer';
import appSaga from '../app/sagas';
import hrReducer from '../hr/reducer';
import hrSaga from '../hr/sagas';
import { hrResources } from '../hr/resources';
import { createAppStore } from './createStore';

function* rootSaga() {
  yield all([authSaga(), appSaga(), hrSaga()]);
}

const store = createAppStore({
  reducers: {
    auth: authReducer,
    app: appReducer,
    hr: hrReducer,
    ...Object.fromEntries(hrResources.map((r) => [r.name, r.reducer])),
  },
  rootSaga,
});

export default store;
