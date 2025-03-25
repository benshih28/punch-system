import {
  BrowserRouter as Router,
  Route,
  Routes,
  Navigate,
} from "react-router-dom";
import { useAtomValue } from "jotai";
import { isAuthenticatedAtom } from "./state/authAtom";
import Header from "./components/header";
import PropTypes from "prop-types";
import Footer from "./components/footer";
import LoginPage from "./pages/LoginPage";
import RegisterPage from "./pages/RegisterPage";
import PunchinPage from "./pages/PunchInPage";
import ApproveLeavePage from "./pages/ApproveLeavePage";
import ProtectedRoute from "./components/protectedRoute";
import UserProfilePage from "./pages/UserProfilePage";
import ForgotPassword from "./pages/ForgotPasswordPage";
import ApproveClockReissuePage from "./pages/ApproveClockReissuePage";
import ClockReissueHistoryPage from "./pages/ClockReissueHistoryPage";
import LeavePolicy from "./components/LeavePolicy";
import DepartmentManagementPage from "./pages/DepartmentManagementPage";
import PositionManagementPage from "./pages/PositionManagementPage";
import ClockHistoryPage from "./pages/ClockHistoryPage";
import RolePermissionsPage from "./pages/RolePermissionsPage";
import LeaveRecordsPage from "./pages/LeaveRecordsPage";
import UserManagementPage from "./pages/UserManagementPage";

/**
 * 受保護頁面的 Layout（包含 Header & Footer）
 */
const ProtectedLayout = ({ children }) => (
  <>
    <Header />
    <main>{children}</main>
    <Footer />
  </>
);

function App() {
  // ✅ 透過 Jotai 讀取 `isAuthenticatedAtom`，用於判斷使用者是否已登入
  const isAuthenticated = useAtomValue(isAuthenticatedAtom);

  return (
    <Router>
      <Routes>
        {/* ✅ 未登入時顯示 LoginPage，並包含 Footer */}
        <Route
          path="/login"
          element={
            <>
              <LoginPage />
              <Footer />
            </>
          }
        />

        {/* ✅ 註冊頁面，不需要登入即可訪問 */}
        <Route
          path="/register"
          element={
            <>
              <RegisterPage />
              <Footer />
            </>
          }
        />

        {/* 忘記密碼頁面，不需要登入 */}
        <Route path="/forgot/password" element={<ForgotPassword />} />

        {/* 此頁面為請假規則頁面，會放在請假彈出框的裡面，由內部連結跳轉頁面 (此路由會刪掉) */}
        <Route path="/leave/policy" element={<LeavePolicy />} />

        {/* ✅ 已登入後的所有頁面（確保 Header 只出現在登入後的頁面） */}
        <Route
          path="*"
          element={
            <ProtectedRoute>
              <ProtectedLayout>
                <Routes>
                  <Route path="/punchin" element={<PunchinPage />} />
                  <Route
                    path="/user/update/profile"
                    element={<UserProfilePage />}
                  />
                  <Route path="/clock/history" element={<ClockHistoryPage />} />
                  <Route
                    path="/clock/reissue/history"
                    element={<ClockReissueHistoryPage />}
                  />
                  <Route
                    path="/leave/and/inquiry/records"
                    element={<LeaveRecordsPage />}
                  />
                  <Route path="/approve/leave" element={<ApproveLeavePage />} />
                  <Route
                    path="/approve/clock/reissue"
                    element={<ApproveClockReissuePage />}
                  />
                  <Route
                    path="/department/management"
                    element={<DepartmentManagementPage />}
                  />
                  <Route
                    path="/position/management"
                    element={<PositionManagementPage />}
                  />
                  <Route
                    path="/user/management"
                    element={<UserManagementPage />}
                  />
                  <Route
                    path="/role/permissions"
                    element={<RolePermissionsPage />}
                  />
                  <Route path="*" element={<Navigate to="/punchin" />} />
                </Routes>
              </ProtectedLayout>
            </ProtectedRoute>
          }
        />

        {/* 未登入時的默認跳轉 */}
        <Route
          path="*"
          element={!isAuthenticated && <Navigate to="/login" replace />}
        />
      </Routes>
    </Router>
  );
}
// ✅ 使用 PropTypes 規範受保護頁面的 Layout
ProtectedLayout.propTypes = {
  children: PropTypes.node.isRequired,
};

export default App;
