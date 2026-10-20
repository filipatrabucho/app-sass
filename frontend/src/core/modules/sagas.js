import { call, getContext, put } from 'redux-saga/effects';
import { addLocales } from '../i18n';
import { modulesLoaded } from '../app/actions';
import { loadModuleManifest } from './registry';

/**
 * Carrega os manifestos dos módulos ativos e injeta reducers, sagas e traduções.
 * Módulos desconhecidos no frontend são ignorados.
 */
export function* loadActiveModules(activeKeys = []) {
  const store = yield getContext('store');
  const keys = [];

  for (const key of activeKeys) {
    const manifest = yield call(loadModuleManifest, key);
    if (!manifest) continue;
    store.injectReducer(manifest.key, manifest.reducer);
    store.injectSaga(manifest.key, manifest.saga);
    addLocales(manifest.key, manifest.locales);
    keys.push(manifest.key);
  }

  yield put(modulesLoaded(keys));
}
