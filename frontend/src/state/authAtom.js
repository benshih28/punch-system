import { atom } from "jotai";
import { atomWithStorage } from "jotai/utils";

/**
 * 初始化認證狀態。
 * 從 localStorage 中獲取存儲的認證資訊。
 * 如果存儲的數據無效或不存在，則回傳一個預設的認證狀態對象。
 *
 * @returns {Object} 認證狀態對象。
 * @property {string|null} access_token - 訪問令牌，若無則為 null。
 * @property {Object|null} user - 用戶對象，若無則為 null。
 */
const initialAuth = () => {
  try {
    return JSON.parse(localStorage.getItem("auth")) || { access_token: null, user: null };
  } catch {
    return { access_token: null, user: null };
  }
};

// ✅ 使用 `atomWithStorage` 來初始化 `authAtom`，並將其存儲於 localStorage
export const authAtom = atomWithStorage("auth", initialAuth());

/**
 * `isAuthenticatedAtom`：基於 `authAtom` 的 `access_token` 來判斷用戶是否已登入。
 *
 * @returns {boolean} 如果 `access_token` 存在則回傳 `true`，否則回傳 `false`。
 */
export const isAuthenticatedAtom = atom(
  (get) => !!get(authAtom).access_token // 若有 Token，則視為已登入
);

/**
 * `logoutAtom`：全域登出功能，清除 `authAtom` 及 `localStorage`
 */
export const logoutAtom = atom(null, (get, set) => {
  set(authAtom, { access_token: null, user: null });
  localStorage.removeItem("auth"); // ✅ 確保 localStorage 也清除
});