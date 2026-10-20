import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { Provider } from 'react-redux';
import dayjs from 'dayjs';
import 'dayjs/locale/pt';
import { addLocales } from '@/core/i18n';
import '@/shared/styles/global.css';
import store from './store';
import CentralApp from './CentralApp';
import ptPT from './locales/pt-PT.json';

dayjs.locale('pt');
addLocales('central', { 'pt-PT': ptPT });

createRoot(document.getElementById('root')).render(
  <StrictMode>
    <Provider store={store}>
      <CentralApp />
    </Provider>
  </StrictMode>,
);
