import { Navigate } from "react-router-dom"; // 引入 `Navigate`，用於未認證時跳轉至登入頁面
import { useAtom } from "jotai"; // 從 Jotai 狀態管理庫引入 `useAtom`，用來讀取 `authAtom` 的狀態
import { authAtom } from "../state/authAtom"; // 引入 `authAtom`，用於管理使用者的認證狀態

// `ProtectedRoute` 是一個高階元件 (HOC)，用來保護內部的子元件
const ProtectedRoute = ({ children }) => {
  // 透過 `useAtom` 取得 `authAtom` 內的認證狀態
  const [auth] = useAtom(authAtom);

  // 檢查是否已認證：
  // - 如果 `auth.access_token` 存在，代表使用者已登入
  // - 如果 `localStorage` 中有 `auth`，則視為已登入（確保即使重新整理也能保持登入狀態）
  const isAuthenticated = !!auth?.access_token || !!localStorage.getItem("auth");

  // 如果已認證，則顯示 `children`（即受保護的頁面）
  // 如果未認證，則導向至 `/login` 登入頁面
  return isAuthenticated ? children : <Navigate to="/login" replace />;
};

export default ProtectedRoute; // 匯出 `ProtectedRoute` 供其他元件使用
