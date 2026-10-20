import { createReducer } from '@reduxjs/toolkit';
import { apiModuleNotLicensed } from '../api/apiActions';
import { modulesLoaded, moduleUnavailableHandled } from './actions';

const initialState = {
  loadedModules: [],
  // Chave do módulo recusado pelo backend (403 MODULE_NOT_LICENSED), à espera de redirecionamento.
  moduleUnavailable: null,
};

export default createReducer(initialState, (builder) => {
  builder
    .addCase(modulesLoaded, (state, { payload }) => {
      state.loadedModules = payload;
    })
    .addCase(apiModuleNotLicensed, (state, { payload }) => {
      state.moduleUnavailable = payload || 'unknown';
    })
    .addCase(moduleUnavailableHandled, (state) => {
      state.moduleUnavailable = null;
    });
});
