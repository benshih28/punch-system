import { atom } from 'jotai';
import { atomWithStorage } from 'jotai/utils';

// 用於存儲多個 API 回應
export const apiResponsesAtom = atom({});

// 更新特定 API 回應的輔助函數
export const updateApiResponse = (apiId, response) => (prev) => ({
  ...prev,
  [apiId]: response,
});

// 身份驗證狀態，存儲於 localStorage
export const authAtom = atomWithStorage('auth', {
  access_token: null,
  user: null,
});

// 判斷是否已登入
export const isAuthenticatedAtom = atom(
  (get) => !!get(authAtom).access_token
);