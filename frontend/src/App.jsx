import {
  BrowserRouter as Router,
  Route,
  Routes,
  Navigate,
} from "react-router-dom";
import { useAtom } from "jotai";
import { authAtom } from "./state/authAtom";
import Header from "./components/header";
import LoginPage from "./pages/LoginPage";
import Punchin from "./pages/Punchin";
import ProtectedRoute from "./components/ProtectedRoute";

// 先預留這些路由
const ProfilePage = () => <div>個人帳戶管理頁面 (尚未建立)</div>;
const ClockHistoryPage = () => <div>查詢打卡紀錄頁面 (尚未建立)</div>;
const ClockReissueHistoryPage = () => <div>查詢補打卡紀錄頁面 (尚未建立)</div>;
const LeaveRecordsPage = () => <div>請假及查詢紀錄頁面 (尚未建立)</div>;
const ApproveLeavePage = () => <div>假單審核頁面 (尚未建立)</div>;
const ApproveClockReissuePage = () => <div>補打卡審核頁面 (尚未建立)</div>;
const UserManagementPage = () => <div>人員管理頁面 (尚未建立)</div>;
const RolePermissionsPage = () => <div>權限修改頁面 (尚未建立)</div>;

function App() {
  const [auth] = useAtom(authAtom);
  const isAuthenticated =
    auth?.isAuthenticated || !!localStorage.getItem("token");

  return (
    <Router>
      <Routes>
        <Route path="/login" element={<LoginPage />} />

        {/* 🔹 這些路由都需要登入後才能進入 */}
        <Route
          path="/punchin"
          element={
            <ProtectedRoute>
              <Header />
              <Punchin />
            </ProtectedRoute>
          }
        />
        <Route
          path="/profile"
          element={
            <ProtectedRoute>
              <Header />
              <ProfilePage />
            </ProtectedRoute>
          }
        />
        <Route
          path="/clock-history"
          element={
            <ProtectedRoute>
              <Header />
              <ClockHistoryPage />
            </ProtectedRoute>
          }
        />
        <Route
          path="/clock-reissue-history"
          element={
            <ProtectedRoute>
              <Header />
              <ClockReissueHistoryPage />
            </ProtectedRoute>
          }
        />
        <Route
          path="/leave-and-inquiry-records"
          element={
            <ProtectedRoute>
              <Header />
              <LeaveRecordsPage />
            </ProtectedRoute>
          }
        />
        <Route
          path="/approve-leave"
          element={
            <ProtectedRoute>
              <Header />
              <ApproveLeavePage />
            </ProtectedRoute>
          }
        />
        <Route
          path="/approve-clock-reissue"
          element={
            <ProtectedRoute>
              <Header />
              <ApproveClockReissuePage />
            </ProtectedRoute>
          }
        />
        <Route
          path="/user-management"
          element={
            <ProtectedRoute>
              <Header />
              <UserManagementPage />
            </ProtectedRoute>
          }
        />
        <Route
          path="/role-permissions"
          element={
            <ProtectedRoute>
              <Header />
              <RolePermissionsPage />
            </ProtectedRoute>
          }
        />

        {/* 🔹 未登入時導向登入頁，已登入則導向打卡頁 */}
        <Route
          path="*"
          element={<Navigate to={isAuthenticated ? "/punchin" : "/login"} />}
        />
      </Routes>
    </Router>
  );
}

export default App;
