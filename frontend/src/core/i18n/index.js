import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import ptPT from './locales/pt-PT.json';

export const DEFAULT_LOCALE = 'pt-PT';

/**
 * i18n com namespaces: "core" (partilhado), um por módulo (ex.: "vacations") e "central".
 * Nunca escrever strings de interface diretamente nos componentes: usar t('ns:chave').
 */
i18n.use(initReactI18next).init({
  lng: DEFAULT_LOCALE,
  fallbackLng: DEFAULT_LOCALE,
  defaultNS: 'core',
  ns: ['core'],
  resources: { [DEFAULT_LOCALE]: { core: ptPT } },
  interpolation: { escapeValue: false },
});

/** Regista as traduções de um módulo/app ({ 'pt-PT': {...} }) no namespace indicado. */
export function addLocales(namespace, locales = {}) {
  Object.entries(locales).forEach(([lng, resources]) => {
    if (!i18n.hasResourceBundle(lng, namespace))
      i18n.addResourceBundle(lng, namespace, resources, true, true);
  });
}

export default i18n;
