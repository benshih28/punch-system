import axios from "axios"; // 引入 Axios，用於發送 HTTP 請求
import { getDefaultStore } from "jotai"; // 從 Jotai 引入 getDefaultStore，以便獲取全局狀態
import { authAtom } from "../state/authAtom"; // 引入 authAtom，用於存儲身份驗證狀態

// 取得 Jotai 的全局 Store，讓我們可以在全域範圍內存取和管理狀態
const store = getDefaultStore();

// **建立 Axios 實例**
// 設定 API 的基本 URL 與預設請求標頭，確保請求的內容類型為 JSON
const api = axios.create({
  baseURL: "http://127.0.0.1:8000/api", // API 伺服器的基本 URL
  headers: {
    "Content-Type": "application/json", // 設定請求的內容類型為 JSON
  },
});

// **請求攔截器：在每次發送請求前，自動附加最新的 Token**
api.interceptors.request.use(
  (config) => {
    // 從 Jotai Store 取得當前的身份驗證狀態
    const authState = store.get(authAtom);
    const access_token = authState?.access_token || null;

    // 如果存在有效的 access_token，則將其加入請求標頭
    if (access_token) {
      config.headers.Authorization = `Bearer ${access_token}`;
    }

    return config; // 返回更新後的請求配置
  },
  (error) => Promise.reject(error) // 如果攔截器發生錯誤，則直接拒絕請求
);

// **回應攔截器：處理 Token 過期或授權錯誤**
api.interceptors.response.use(
  (response) => response, // 正常回應時，直接返回回應結果
  async (error) => {
    // 檢查回應狀態碼是否為 401（未授權或 Token 過期）
    if (error.response?.status === 401) {
      console.warn("Token 過期或未授權，清除 Token...");

      const currentAuth = store.get(authAtom);
      // 只有當 `authAtom` 內的 `access_token` 不是 null 時，才執行清除，避免重複操作
      if (currentAuth.access_token) {
        store.set(authAtom, { access_token: null, user: null });
      }

      // 附加自訂錯誤訊息，方便前端處理
      error.customMessage = "登入時間超時，請重新登入";
    }

    return Promise.reject(error); // 拒絕錯誤回應，讓前端可以進一步處理
  }
);

export default api; // 匯出建立好的 Axios 實例，供其他模組使用
