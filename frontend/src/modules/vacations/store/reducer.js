import { createReducer } from '@reduxjs/toolkit';
import * as actions from './actions';

const initialState = {
  my: { items: [], loading: false, error: null },
};

export default createReducer(initialState, (builder) => {
  builder
    .addCase(actions.fetchMyVacationsRequest, (state) => {
      state.my.loading = true;
      state.my.error = null;
    })
    .addCase(actions.fetchMyVacationsSuccess, (state, { payload }) => {
      state.my = { items: payload, loading: false, error: null };
    })
    .addCase(actions.fetchMyVacationsFailure, (state, { payload }) => {
      state.my.loading = false;
      state.my.error = payload;
    });
});
