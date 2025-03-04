import axios from "axios"; // 引入 Axios 用於發送 HTTP 請求
import { getDefaultStore } from "jotai"; // 從 Jotai 引入 getDefaultStore
import { authAtom } from "../state/authAtom"; // 引入 authAtom 用於存儲身份驗證狀態

// 取得 Jotai 全局 Store，讓我們可以在全域存取狀態
const store = getDefaultStore();

// 建立 Axios 實例，設定 API 基本 URL 和請求標頭
const api = axios.create({
  baseURL: "http://127.0.0.1:8000/api", // API 的基本 URL
  headers: {
    "Content-Type": "application/json", // 設定請求的內容類型為 JSON
  },
});

// **請求攔截器：每次發送請求前，檢查並附加最新的 Token**
api.interceptors.request.use(
  (config) => {
    const auth = store.get(authAtom); // 取得最新的 `authAtom`
    
    // 檢查是否已經設置 Authorization，避免重複設置
    if (auth.access_token && !config.headers.Authorization) {
      config.headers.Authorization = `Bearer ${auth.access_token}`;
    }

    return config;
  },
  (error) => Promise.reject(error) // 如果請求攔截器發生錯誤，則直接拒絕該請求
);

// **回應攔截器：處理 Token 過期或授權錯誤**
api.interceptors.response.use(
  (response) => response, // 正常回應時，直接返回回應結果
  async (error) => {
    if (error.response?.status === 401) {
      console.warn("Token 過期或未授權，清除 Token...");

      const currentAuth = store.get(authAtom);
      // 只有當 `authAtom` 內的 `access_token` 不是 null 時，才執行清除
      if (currentAuth.access_token) {
        store.set(authAtom, { access_token: null, user: null });
      }

      // 添加錯誤訊息
      error.customMessage = "登入時間超時，請重新登入";
    }

    return Promise.reject(error); // 直接返回錯誤
  }
);

export default api;
