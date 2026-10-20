import { createReducer } from '@reduxjs/toolkit';
import { RESET_ON } from '../store/createResource';
import * as actions from './actions';

const initialState = {
  employeeOptions: { items: [], loading: false },
  attachments: { items: [], loading: false, uploading: false },
  holidaysImport: { pending: false, message: null },
  notifications: { items: [], meta: null, unreadCount: 0, loading: false },
  profile: { pending: false, error: null, success: false, uploading: false },
};

/** Estado auxiliar do Core de RH (state.hr). Os CRUD vivem nos recursos (core/hr/resources.js). */
export default createReducer(initialState, (builder) => {
  builder
    .addCase(actions.searchEmployees, (state) => {
      state.employeeOptions.loading = true;
    })
    .addCase(actions.searchEmployeesSuccess, (state, { payload }) => {
      state.employeeOptions = { items: payload, loading: false };
    })
    .addCase(actions.fetchAttachments, (state) => {
      state.attachments.loading = true;
    })
    .addCase(actions.uploadAttachment, (state) => {
      state.attachments.uploading = true;
    })
    .addCase(actions.fetchAttachmentsSuccess, (state, { payload }) => {
      state.attachments = { items: payload, loading: false, uploading: false };
    })
    .addCase(actions.attachmentsFailure, (state) => {
      state.attachments.loading = false;
      state.attachments.uploading = false;
    })
    .addCase(actions.importHolidays, (state) => {
      state.holidaysImport = { pending: true, message: null };
    })
    .addCase(actions.importHolidaysDone, (state, { payload }) => {
      state.holidaysImport = { pending: false, message: payload };
    })
    .addCase(actions.fetchNotifications, (state) => {
      state.notifications.loading = true;
    })
    .addCase(actions.fetchNotificationsSuccess, (state, { payload }) => {
      state.notifications = {
        items: payload.data,
        meta: payload.meta,
        unreadCount: payload.meta.unread_count,
        loading: false,
      };
    })
    .addCase(actions.unreadCountChanged, (state, { payload }) => {
      state.notifications.unreadCount = payload.count;
      const now = new Date().toISOString();
      state.notifications.items.forEach((n) => {
        if (payload.id === undefined || n.id === payload.id) n.read_at = n.read_at || now;
      });
    })
    .addCase(actions.updateProfile, (state) => {
      state.profile = { ...state.profile, pending: true, error: null, success: false };
    })
    .addCase(actions.uploadAvatar, (state) => {
      state.profile = { ...state.profile, uploading: true, error: null };
    })
    .addCase(actions.profileSaved, (state) => {
      state.profile = { pending: false, uploading: false, error: null, success: true };
    })
    .addCase(actions.profileFailure, (state, { payload }) => {
      state.profile = { pending: false, uploading: false, error: payload, success: false };
    })
    .addCase(actions.resetProfileState, (state) => {
      state.profile = initialState.profile;
    })
    .addMatcher(
      (action) => RESET_ON.includes(action.type),
      () => initialState,
    );
});
