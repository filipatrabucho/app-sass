import { combineReducers, configureStore, isPlain } from '@reduxjs/toolkit';
import createSagaMiddleware from 'redux-saga';

/**
 * Store com injeção dinâmica de reducers e sagas (usada pelos módulos carregados em lazy).
 */
export function createAppStore({ reducers, rootSaga }) {
  const staticReducers = { ...reducers };
  const asyncReducers = {};
  const runningSagas = new Set();
  const sagaMiddleware = createSagaMiddleware();

  const store = configureStore({
    reducer: combineReducers(staticReducers),
    middleware: (getDefault) =>
      getDefault({
        thunk: false,
        // Uploads: o File segue na action até à saga (nunca é guardado no estado).
        serializableCheck: { isSerializable: (value) => isPlain(value) || value instanceof Blob },
      }).concat(sagaMiddleware),
  });

  // As sagas acedem à store (injeção de módulos) via getContext('store').
  sagaMiddleware.setContext({ store });

  store.injectReducer = (key, reducer) => {
    if (!reducer || asyncReducers[key]) return;
    asyncReducers[key] = reducer;
    store.replaceReducer(combineReducers({ ...staticReducers, ...asyncReducers }));
  };

  store.injectSaga = (key, saga) => {
    if (!saga || runningSagas.has(key)) return;
    runningSagas.add(key);
    sagaMiddleware.run(saga);
  };

  if (rootSaga) sagaMiddleware.run(rootSaga);
  return store;
}
