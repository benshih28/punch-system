import axios from 'axios';
import { getDefaultStore } from 'jotai';
import { authAtom } from './atoms';

const store = getDefaultStore();

const api = axios.create({
  baseURL: 'http://127.0.0.1:8000/api', // 根據 Swagger 文件
  headers: {
    'Content-Type': 'application/json',
  },
});

// 請求攔截器：附加 JWT token
api.interceptors.request.use(
  (config) => {
    const authState = store.get(authAtom);
    const access_token = authState?.access_token;
    if (access_token) {
      config.headers.Authorization = `Bearer ${access_token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// 回應攔截器：處理 401 錯誤
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      console.warn('Token 過期或未授權');
      store.set(authAtom, { access_token: null, user: null });
      error.customMessage = '登入時間超時，請重新登入';
    }
    return Promise.reject(error);
  }
);

export default api;