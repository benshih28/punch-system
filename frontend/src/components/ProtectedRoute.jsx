import { Navigate, Outlet } from "react-router-dom";
import { useAtom } from "jotai";
import { authAtom } from "../state/authAtom";

const ProtectedRoute = ({ children }) => {
  const [auth] = useAtom(authAtom);
  const isAuthenticated = auth?.isAuthenticated || !!localStorage.getItem("token");

  return isAuthenticated ? children : <Navigate to="/login" replace />;
};

export default ProtectedRoute;
