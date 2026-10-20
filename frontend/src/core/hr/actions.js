import { createAction } from '@reduxjs/toolkit';

// Pesquisa de colaboradores para selects
export const searchEmployees = createAction('hr/searchEmployees');
export const searchEmployeesSuccess = createAction('hr/searchEmployeesSuccess');

// Anexos da ficha do colaborador
export const fetchAttachments = createAction('hr/fetchAttachments');
export const fetchAttachmentsSuccess = createAction('hr/fetchAttachmentsSuccess');
export const uploadAttachment = createAction('hr/uploadAttachment');
export const removeAttachment = createAction('hr/removeAttachment');
export const attachmentsFailure = createAction('hr/attachmentsFailure');

// Feriados: importar nacionais
export const importHolidays = createAction('hr/importHolidays');
export const importHolidaysDone = createAction('hr/importHolidaysDone');

// Notificações
export const fetchNotifications = createAction('hr/fetchNotifications');
export const fetchNotificationsSuccess = createAction('hr/fetchNotificationsSuccess');
export const markNotificationRead = createAction('hr/markNotificationRead');
export const markAllNotificationsRead = createAction('hr/markAllNotificationsRead');
export const unreadCountChanged = createAction('hr/unreadCountChanged');

// Perfil do próprio
export const updateProfile = createAction('hr/updateProfile');
export const uploadAvatar = createAction('hr/uploadAvatar');
export const profileSaved = createAction('hr/profileSaved');
export const profileFailure = createAction('hr/profileFailure');
export const resetProfileState = createAction('hr/resetProfileState');
