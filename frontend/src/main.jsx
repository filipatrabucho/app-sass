import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { Provider } from 'react-redux';
import dayjs from 'dayjs';
import 'dayjs/locale/pt';
import '@/core/i18n';
import store from '@/core/store';
import '@/shared/styles/global.css';
import App from './App';

dayjs.locale('pt');

createRoot(document.getElementById('root')).render(
  <StrictMode>
    <Provider store={store}>
      <App />
    </Provider>
  </StrictMode>,
);
