import { Navigate, Outlet } from "react-router-dom";
import { useAtom } from "jotai";
import { authAtom } from "../state/authAtom";

const ProtectedRoute = () => {
  const [auth] = useAtom(authAtom);
  const isAuthenticated = auth?.isAuthenticated || !!localStorage.getItem("token");

  return isAuthenticated ? <Outlet /> : <Navigate to="/login" replace />;
};

export default ProtectedRoute;
