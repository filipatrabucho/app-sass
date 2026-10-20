/**
 * Catálogo de módulos do frontend.
 *  - load: carrega (lazy) o manifesto { key, reducer, saga, routes, menu, locales }
 *  - basePath: prefixo das rotas; um URL de um módulo inativo mostra "módulo não disponível".
 * O core só carrega os módulos presentes em active_modules (GET /me).
 * Para adicionar um módulo: criar src/modules/<nome>/index.js e registá-lo aqui.
 */
const moduleCatalog = {
  vacations: { load: () => import('./vacations'), basePath: '/ferias' },
};

export default moduleCatalog;
