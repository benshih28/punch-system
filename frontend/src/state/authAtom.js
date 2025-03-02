import { atomWithStorage } from "jotai/utils";

// 管理登入狀態 (儲存在 localStorage)
export const authAtom = atomWithStorage("auth", {
  isAuthenticated: false,
  user: null,
});
