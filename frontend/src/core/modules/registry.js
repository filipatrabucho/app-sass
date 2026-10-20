import moduleCatalog from '@/modules';

/**
 * Manifestos dos módulos já carregados (fora da store: contêm componentes e funções).
 */
const loaded = new Map();

export async function loadModuleManifest(key) {
  if (loaded.has(key)) return loaded.get(key);
  const entry = moduleCatalog[key];
  if (!entry) return null;
  const { default: manifest } = await entry.load();
  loaded.set(key, manifest);
  return manifest;
}

/** Manifestos carregados e ativos, pela ordem de active_modules. */
export function getModules(activeKeys = []) {
  return activeKeys.map((key) => loaded.get(key)).filter(Boolean);
}

/** Prefixos de rota dos módulos conhecidos que NÃO estão ativos. */
export function getInactiveModulePaths(activeKeys = []) {
  return Object.entries(moduleCatalog)
    .filter(([key]) => !activeKeys.includes(key))
    .map(([, entry]) => entry.basePath)
    .filter(Boolean);
}
