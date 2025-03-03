import { atom } from "jotai";
import { atomWithStorage } from "jotai/utils";

// 管理登入狀態 (儲存在 localStorage)
export const authAtom = atomWithStorage("auth", {
  access_token: null, // JWT Token
  user: null,
});

// ✅ 讓 `isAuthenticatedAtom` 依賴 `authAtom` 的 `access_token`
export const isAuthenticatedAtom = atom(
  (get) => !!get(authAtom).access_token // 有 Token 就視為已登入
);
