import { defineConfig, loadEnv } from 'vite';
import react from '@vitejs/plugin-react';
import { fileURLToPath, URL } from 'node:url';

/**
 * Em dev, o mesmo servidor Vite serve as duas apps:
 *  - admin.nossaapp.localhost  -> central.html (painel central)
 *  - *.nossaapp.localhost      -> index.html   (app do tenant)
 */
function centralHostRewrite(centralHosts) {
  return {
    name: 'central-host-rewrite',
    configureServer(server) {
      server.middlewares.use((req, _res, next) => {
        const host = (req.headers.host || '').split(':')[0];
        const isNavigation = (req.headers.accept || '').includes('text/html');
        if (centralHosts.includes(host) && isNavigation) {
          req.url = '/central.html';
        }
        next();
      });
    },
  };
}

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '');
  const centralHosts = (env.VITE_CENTRAL_HOSTS || 'admin.nossaapp.localhost').split(',').map((h) => h.trim());
  // Sem Docker: aponta para `php artisan serve`. O Host é preservado para a identificação do tenant.
  const backend = env.VITE_BACKEND_URL;
  const proxy = backend
    ? Object.fromEntries(
        ['/api', '/sanctum', '/horizon', '/vendor'].map((p) => [p, { target: backend, changeOrigin: false }]),
      )
    : undefined;

  return {
    plugins: [react(), centralHostRewrite(centralHosts)],
    resolve: {
      alias: { '@': fileURLToPath(new URL('./src', import.meta.url)) },
    },
    server: {
      host: true,
      port: 5173,
      allowedHosts: true,
      proxy,
      hmr: env.VITE_HMR_CLIENT_PORT ? { clientPort: Number(env.VITE_HMR_CLIENT_PORT) } : undefined,
    },
    build: {
      chunkSizeWarningLimit: 1500,
      rollupOptions: {
        input: {
          main: fileURLToPath(new URL('./index.html', import.meta.url)),
          central: fileURLToPath(new URL('./central.html', import.meta.url)),
        },
      },
    },
  };
});
