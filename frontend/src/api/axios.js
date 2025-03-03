import axios from "axios";
import { atom, getDefaultStore } from "jotai";
import { authAtom } from "../state/authAtom";

const store = getDefaultStore(); // 取得 Jotai 全局 Store

const api = axios.create({
  baseURL: "http://127.0.0.1:8000/api",
  withCredentials: true, // **允許攜帶 Cookie**
  headers: {
    "Content-Type": "application/json",
  },
});

// **請求攔截器：每次請求前附加最新的 Token**
api.interceptors.request.use(
  (config) => {
    const auth = store.get(authAtom); // 取得最新的 `authAtom`
    if (auth.access_token) {
      config.headers.Authorization = `Bearer ${auth.access_token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// **回應攔截器：處理 Token 過期**
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response?.status === 401) {
      console.warn("🔴 Token 過期或未授權，登出使用者...");
      store.set(authAtom, { access_token: null, user: null }); // 清空 Token
      window.location.href = "/login"; // 重新導向到登入頁
    }
    return Promise.reject(error);
  }
);

export default api;
