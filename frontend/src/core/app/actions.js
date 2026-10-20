import { createAction } from '@reduxjs/toolkit';

export const modulesLoaded = createAction('app/modulesLoaded');
export const moduleUnavailableHandled = createAction('app/moduleUnavailableHandled');
