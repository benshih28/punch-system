import { Navigate, Outlet } from "react-router-dom"; // 引入 `Navigate` 來進行頁面導向，`Outlet` 用於嵌套路由
import { useAtom } from "jotai"; // 從 Jotai 狀態管理庫引入 `useAtom`
import { authAtom } from "../state/authAtom"; // 引入 `authAtom`，存放認證狀態

// 受保護路由 (ProtectedRoute) 組件
const ProtectedRoute = ({ children }) => {
  // 從 `authAtom` 取得 `auth` 狀態
  const [auth] = useAtom(authAtom);

  // 檢查使用者是否已登入
  // 1. 如果 `auth` 存在且 `auth.isAuthenticated` 為 true，代表已登入
  // 2. 如果 `auth` 為 `null`，則從 `localStorage` 檢查是否有 `token` (這是為了處理頁面重新整理時的登入狀態)
  const isAuthenticated = auth?.isAuthenticated || !!localStorage.getItem("token");

  // 如果已登入，則顯示 `children` (即受保護的內容)
  // 如果未登入，則導向 `/login` 頁面，並使用 `replace` 屬性防止回退到受保護頁面
  return isAuthenticated ? children : <Navigate to="/login" replace />;
};

export default ProtectedRoute; // 導出 `ProtectedRoute` 讓其他組件使用
