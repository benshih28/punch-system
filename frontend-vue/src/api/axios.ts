// src/api/axios.ts
import axios, { AxiosError } from "axios";
import type { InternalAxiosRequestConfig } from "axios";
import router from "@/router";
import { showToast } from "@/utils/toast";
import { mapMessageToCode } from "../utils/errorMapper";
import type { AppErrorCode } from "../utils/errorMapper";

export interface AppError extends AxiosError {
  appCode?: AppErrorCode;
}

const API = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || "http://localhost:8000/api",
  timeout: 10_000,
});

// request：帶 JWT
API.interceptors.request.use((cfg: InternalAxiosRequestConfig) => {
  const t = localStorage.getItem("access_token");
  if (t) cfg.headers.Authorization = `Bearer ${t}`;
  return cfg;
});

// response：全域錯誤 + appCode
API.interceptors.response.use(
  (r) => r,
  (err: AppError) => {
    const data = err.response?.data as { message?: string };
    err.appCode = mapMessageToCode(data?.message);
    const status = err.response?.status;

    const handler: Record<number, () => void> = {
      401() {
        localStorage.clear();
        router.push("/login");
        showToast("登入逾時，請重新登入");
      },
      403() {
        showToast("沒有權限");
      },
      500() {
        showToast("系統繁忙，請稍後再試");
      },
    };

    if (status && handler[status]) {
      handler[status]();
      return; // 已處理完
    }
    return Promise.reject(err); // 其餘交給呼叫端
  }
);

export default API;