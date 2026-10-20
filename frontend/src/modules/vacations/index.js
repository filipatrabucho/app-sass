import reducer from './store/reducer';
import saga from './store/sagas';
import routes from './routes';
import menu from './menu';
import ptPT from './locales/pt-PT.json';

/** Manifesto do módulo Férias (carregado só se "vacations" estiver em active_modules). */
export default {
  key: 'vacations',
  reducer,
  saga,
  routes,
  menu,
  locales: { 'pt-PT': ptPT },
};
