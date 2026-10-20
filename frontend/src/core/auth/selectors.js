export const selectAuth = (state) => state.auth;
export const selectAuthStatus = (state) => state.auth.status;
export const selectUser = (state) => state.auth.user;
export const selectTenant = (state) => state.auth.tenant;
export const selectActiveModules = (state) => state.auth.activeModules;
export const selectPermissions = (state) => state.auth.permissions;
