import { call, put } from 'redux-saga/effects';
import { apiUnauthorized, apiModuleNotLicensed } from './apiActions';

/**
 * Efeito saga para chamar o gateway com tratamento global de erros:
 *  - 401 -> apiUnauthorized (a app faz logout local e mostra o login)
 *  - 403 MODULE_NOT_LICENSED -> apiModuleNotLicensed (página "módulo não disponível")
 * O erro é sempre relançado para a saga chamadora tratar (ex.: 422 em formulários).
 *
 *   const data = yield call(callApi, vacationsApi.listMine, params);
 */
export function* callApi(fn, ...args) {
  try {
    return yield call(fn, ...args);
  } catch (error) {
    if (error?.isUnauthorized) yield put(apiUnauthorized());
    else if (error?.isModuleNotLicensed) yield put(apiModuleNotLicensed(error.errors?.module));
    throw error;
  }
}
