import { atom } from "jotai";

// 存儲錯誤訊息的 Atom
export const errorAtom = atom(null);

// 重置錯誤訊息的 Atom（方便清除錯誤）
export const resetErrorAtom = atom(null, (get, set) => {
  set(errorAtom, null);
});
