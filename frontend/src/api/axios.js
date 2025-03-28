import axios from "axios"; // 引入 Axios，用於發送 HTTP 請求
import { getDefaultStore } from "jotai"; // 從 Jotai 引入 getDefaultStore，以便獲取全局狀態
import { authAtom,logoutAtom } from "../state/authAtom"; // 引入 authAtom，用於存儲身份驗證狀態
import { errorAtom } from "../state/errorAtom";
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
    const status = error.response?.status;
    const data = error.response?.data;

    // **使用 Map 簡化錯誤處理**
    const errorMessages = new Map([
      [400, data?.message || "請求格式錯誤，請檢查輸入"],
      [401, "未授權，請重新登入"],
      [403, "權限不足，無法執行此操作"],
      [404, "找不到請求的資源"],
      [422, data?.message || "請求驗證失敗，請檢查輸入"],
      [500, "伺服器錯誤，請稍後再試"],
    ]);

    let errorMessage = errorMessages.get(status) || "發生未知錯誤，請稍後再試";

    if (status === 401) {
      store.set(logoutAtom);
    }

    console.error(`API 錯誤 (${status}):`, data);
    store.set(errorAtom, errorMessage); // ✅ 存入錯誤狀態

    return Promise.reject(error); // 拒絕錯誤回應，讓前端可以進一步處理
  }
);

export default api; // 匯出建立好的 Axios 實例，供其他模組使用
