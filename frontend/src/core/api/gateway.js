import ApiError from './ApiError';

/**
 * API gateway baseado em fetch. É a ÚNICA forma de fazer pedidos HTTP no frontend
 * e só deve ser chamado a partir de sagas (action -> saga -> gateway -> reducer).
 *
 * - Cookies de sessão Sanctum (credentials: 'include') + header X-XSRF-TOKEN.
 * - Respostas de erro convertidas em ApiError ({ status, message, code, errors }).
 * - 419 (CSRF expirado): renova o cookie e repete o pedido uma vez.
 * O tratamento global de 401/403 é feito pela saga `callApi` (core/api/callApi.js).
 */

const XSRF_COOKIE = 'XSRF-TOKEN';
const MUTATING = new Set(['POST', 'PUT', 'PATCH', 'DELETE']);

function readCookie(name) {
  const match = document.cookie.split('; ').find((row) => row.startsWith(`${name}=`));
  return match ? decodeURIComponent(match.split('=').slice(1).join('=')) : null;
}

function buildQuery(query) {
  if (!query) return '';
  const params = new URLSearchParams();
  Object.entries(query).forEach(([key, value]) => {
    if (value === undefined || value === null || value === '') return;
    if (Array.isArray(value)) value.forEach((v) => params.append(`${key}[]`, v));
    else params.append(key, value);
  });
  const qs = params.toString();
  return qs ? `?${qs}` : '';
}

async function parseBody(response) {
  if (response.status === 204) return null;
  const type = response.headers.get('content-type') || '';
  return type.includes('application/json') ? response.json() : response.text();
}

export function createApiGateway({ baseUrl = '/api', csrfUrl = '/sanctum/csrf-cookie' } = {}) {
  async function ensureCsrfCookie(force = false) {
    if (!force && readCookie(XSRF_COOKIE)) return;
    await fetch(csrfUrl, { credentials: 'include', headers: { Accept: 'application/json' } });
  }

  async function request(method, path, { query, body, headers = {}, retryOnCsrf = true } = {}) {
    const upper = method.toUpperCase();
    if (MUTATING.has(upper)) await ensureCsrfCookie();

    const isForm = typeof FormData !== 'undefined' && body instanceof FormData;
    const init = {
      method: upper,
      credentials: 'include',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(body !== undefined && !isForm ? { 'Content-Type': 'application/json' } : {}),
        ...headers,
      },
    };
    const xsrf = readCookie(XSRF_COOKIE);
    if (xsrf) init.headers['X-XSRF-TOKEN'] = xsrf;
    if (body !== undefined) init.body = isForm ? body : JSON.stringify(body);

    let response;
    try {
      response = await fetch(`${baseUrl}${path}${buildQuery(query)}`, init);
    } catch {
      throw new ApiError({ status: 0, code: 'NETWORK_ERROR', message: 'Sem ligação ao servidor.' });
    }

    if (response.status === 419 && retryOnCsrf) {
      await ensureCsrfCookie(true);
      return request(method, path, { query, body, headers, retryOnCsrf: false });
    }

    const data = await parseBody(response);
    if (!response.ok) {
      const payload = data && typeof data === 'object' ? data : {};
      throw new ApiError({ status: response.status, ...payload });
    }
    return data;
  }

  return {
    get: (path, options) => request('GET', path, options),
    post: (path, body, options) => request('POST', path, { ...options, body }),
    put: (path, body, options) => request('PUT', path, { ...options, body }),
    patch: (path, body, options) => request('PATCH', path, { ...options, body }),
    delete: (path, options) => request('DELETE', path, options),
    request,
  };
}

/** Gateway da app de tenant (/api). */
const gateway = createApiGateway({ baseUrl: '/api' });
export default gateway;
