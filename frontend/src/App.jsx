import { BrowserRouter as Router, Routes, Route, Navigate } from "react-router-dom";
import ProtectedRoute from "./components/ProtectedRoute"; // 移動 ProtectedRoute 至 components
import LoginPage from "./pages/LoginPage";
import PunchIn from "./pages/PunchIn";

function App() {
  return (
    <Router>
      <Routes>
        {/* 預設導向 login */}
        <Route path="/" element={<Navigate replace to="/login" />} />
        <Route path="/login" element={<LoginPage />} />

        {/* 受保護的路由：登入後才能進入 punchin */}
        <Route element={<ProtectedRoute />}>
          <Route path="/punchin" element={<PunchIn />} />
        </Route>
      </Routes>
    </Router>
  );
}

export default App;
