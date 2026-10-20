import { createAction } from '@reduxjs/toolkit';

export const bootstrapRequest = createAction('auth/bootstrapRequest');
export const meRequest = createAction('auth/meRequest');
export const meSuccess = createAction('auth/meSuccess');
export const meFailure = createAction('auth/meFailure');

export const loginRequest = createAction('auth/loginRequest');
export const loginFailure = createAction('auth/loginFailure');

export const logoutRequest = createAction('auth/logoutRequest');
export const loggedOut = createAction('auth/loggedOut');

export const forgotPasswordRequest = createAction('auth/forgotPasswordRequest');
export const forgotPasswordDone = createAction('auth/forgotPasswordDone');
export const resetPasswordRequest = createAction('auth/resetPasswordRequest');
export const resetPasswordDone = createAction('auth/resetPasswordDone');
export const passwordFlowFailure = createAction('auth/passwordFlowFailure');
