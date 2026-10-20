import { createAction, createReducer } from '@reduxjs/toolkit';
import { all, call, put, select, takeLatest, takeEvery } from 'redux-saga/effects';
import { callApi } from '../api/callApi';

/** Ações que limpam todos os recursos (fim de sessão). */
export const RESET_ON = ['auth/loggedOut', 'api/unauthorized'];

const toError = (error) => ({
  message: error.message,
  code: error.code,
  errors: error.errors,
  status: error.status,
});

/**
 * Fábrica de "recurso" CRUD: actions + reducer + saga para uma API REST standard.
 *
 *   const employees = createResource('employees', {
 *     list: (params) => gateway.get('/employees', { query: params }),
 *     get: (id) => gateway.get(`/employees/${id}`),
 *     create: (values) => gateway.post('/employees', values),
 *     update: (id, values) => gateway.put(`/employees/${id}`, values),
 *     remove: (id) => gateway.delete(`/employees/${id}`),
 *   });
 *
 * Estado: { list: { items, meta, params, loading }, current: { data, loading, error },
 *           save: { pending, error, success }, removing }
 * Fluxo: action -> saga -> api (gateway) -> reducer.
 */
export function createResource(name, api, { selectState = (state) => state[name] } = {}) {
  const a = (type) => createAction(`${name}/${type}`);
  const actions = {
    fetchList: a('fetchList'),
    fetchListSuccess: a('fetchListSuccess'),
    fetchListFailure: a('fetchListFailure'),
    fetchOne: a('fetchOne'),
    fetchOneSuccess: a('fetchOneSuccess'),
    fetchOneFailure: a('fetchOneFailure'),
    // payload: { id?, values } — sem id cria, com id atualiza
    save: a('save'),
    saveSuccess: a('saveSuccess'),
    saveFailure: a('saveFailure'),
    resetSave: a('resetSave'),
    remove: a('remove'),
    removeSuccess: a('removeSuccess'),
    removeFailure: a('removeFailure'),
  };

  const initialState = {
    list: { items: [], meta: null, params: {}, loading: false, loaded: false },
    current: { data: null, loading: false, error: null },
    save: { pending: false, error: null, success: null },
    removing: null,
  };

  const reducer = createReducer(initialState, (builder) => {
    builder
      .addCase(actions.fetchList, (state, { payload }) => {
        state.list.loading = true;
        state.list.params = payload || {};
      })
      .addCase(actions.fetchListSuccess, (state, { payload }) => {
        state.list = {
          ...state.list,
          items: payload.data,
          meta: payload.meta || null,
          loading: false,
          loaded: true,
        };
      })
      .addCase(actions.fetchListFailure, (state) => {
        state.list.loading = false;
      })
      .addCase(actions.fetchOne, (state) => {
        state.current = { data: null, loading: true, error: null };
      })
      .addCase(actions.fetchOneSuccess, (state, { payload }) => {
        state.current = { data: payload, loading: false, error: null };
      })
      .addCase(actions.fetchOneFailure, (state, { payload }) => {
        state.current = { data: null, loading: false, error: payload };
      })
      .addCase(actions.save, (state) => {
        state.save = { pending: true, error: null, success: null };
      })
      .addCase(actions.saveSuccess, (state, { payload }) => {
        state.save = { pending: false, error: null, success: payload.created ? 'created' : 'updated' };
        if (state.current.data && payload.data && state.current.data.id === payload.data.id) {
          state.current.data = payload.data;
        }
      })
      .addCase(actions.saveFailure, (state, { payload }) => {
        state.save = { pending: false, error: payload, success: null };
      })
      .addCase(actions.resetSave, (state) => {
        state.save = initialState.save;
      })
      .addCase(actions.remove, (state, { payload }) => {
        state.removing = payload;
      })
      .addCase(actions.removeSuccess, (state, { payload }) => {
        state.removing = null;
        state.list.items = state.list.items.filter((item) => item.id !== payload);
      })
      .addCase(actions.removeFailure, (state) => {
        state.removing = null;
      })
      // Ao terminar a sessão, nada do utilizador anterior fica em memória.
      .addMatcher(
        (action) => RESET_ON.includes(action.type),
        () => initialState,
      );
  });

  function* refreshList() {
    const { list } = yield select(selectState);
    if (list.loaded) yield put(actions.fetchList(list.params));
  }

  function* fetchList({ payload }) {
    try {
      const response = yield call(callApi, api.list, payload || {});
      yield put(actions.fetchListSuccess(response));
    } catch (error) {
      yield put(actions.fetchListFailure(toError(error)));
    }
  }

  function* fetchOne({ payload: id }) {
    try {
      const { data } = yield call(callApi, api.get, id);
      yield put(actions.fetchOneSuccess(data));
    } catch (error) {
      yield put(actions.fetchOneFailure(toError(error)));
    }
  }

  function* save({ payload: { id, values } }) {
    try {
      const response = id
        ? yield call(callApi, api.update, id, values)
        : yield call(callApi, api.create, values);
      yield put(actions.saveSuccess({ data: response?.data, created: !id }));
      yield call(refreshList);
    } catch (error) {
      yield put(actions.saveFailure(toError(error)));
    }
  }

  function* remove({ payload: id }) {
    try {
      yield call(callApi, api.remove, id);
      yield put(actions.removeSuccess(id));
      yield call(refreshList);
    } catch (error) {
      yield put(actions.removeFailure(toError(error)));
      yield put(actions.saveFailure(toError(error)));
    }
  }

  function* saga() {
    yield all([
      takeLatest(actions.fetchList.type, fetchList),
      takeLatest(actions.fetchOne.type, fetchOne),
      takeLatest(actions.save.type, save),
      takeEvery(actions.remove.type, remove),
    ]);
  }

  return { name, actions, reducer, saga, selectState };
}
