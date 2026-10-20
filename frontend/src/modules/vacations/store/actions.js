import { createAction } from '@reduxjs/toolkit';

export const fetchMyVacationsRequest = createAction('vacations/fetchMyRequest');
export const fetchMyVacationsSuccess = createAction('vacations/fetchMySuccess');
export const fetchMyVacationsFailure = createAction('vacations/fetchMyFailure');
