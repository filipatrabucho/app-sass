import { createAppStore } from '@/core/store/createStore';
import reducer from './reducer';
import saga from './sagas';

/** Store do painel central (estado em state.central). */
const store = createAppStore({ reducers: { central: reducer }, rootSaga: saga });

export default store;
