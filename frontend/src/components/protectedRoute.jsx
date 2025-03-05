import { Navigate } from "react-router-dom"; // 用來做頁面導向 (重定向)
import { useAtom } from "jotai"; // 從 Jotai 引入 `useAtom`，用來讀取 `authAtom`
import { authAtom } from "../state/authAtom"; // 引入 `authAtom`，用來獲取登入狀態

/**
 * `ProtectedRoute` 是一個保護頁面的元件
 * 只有當使用者已登入時，才能訪問 `children` (受保護的內容)
 * 否則，會被重定向到 `/login`
 *
 * @param {Object} props - React 組件的屬性
 * @param {React.ReactNode} props.children - 受保護的子元件 (例如 Dashboard)
 * @returns {JSX.Element} - 若已登入，顯示 `children`，否則跳轉到 `/login`
 */
const ProtectedRoute = ({ children }) => {
  
  // 透過 `useAtom` 讀取 `authAtom`，獲取當前的登入資訊
  const [auth] = useAtom(authAtom);

  /**
   * 判斷使用者是否已經登入
   * 1. `auth?.access_token`：如果 `authAtom` 裡面有 `access_token`，代表已登入
   * 2. `localStorage.getItem("auth")`：如果 `localStorage` 有存 `auth`，代表登入狀態持久化
   * 3. `!!` 用來轉換為布林值，確保 `true` / `false`
   */
  const isAuthenticated = !!auth?.access_token || !!localStorage.getItem("auth");
  // console.log(isAuthenticated);

  /**
   * 根據登入狀態決定要顯示什麼：
   * - ✅ 如果已登入 (isAuthenticated 為 true)，顯示 `children` (受保護頁面)
   * - ❌ 如果未登入 (isAuthenticated 為 false)，導向 `/login` (透過 `<Navigate to="/login" replace />`)
   */
  return isAuthenticated ? children : <Navigate to="/login" replace />;
};

export default ProtectedRoute; // 匯出元件，讓其他頁面使用
