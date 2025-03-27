import { useState } from "react"; // React Hook 用於管理元件的內部狀態
import { useNavigate, Link } from "react-router-dom"; // React Router 用於導航
import { useForm } from "react-hook-form"; // React Hook Form 用於表單管理
import { useAtom } from "jotai"; // Jotai 狀態管理
import { authAtom } from "../state/authAtom"; // Jotai Atom 用於存儲身份驗證狀態
import API from "../api/axios"; // Axios 實例，用於發送 API 請求
import { GoogleLogin } from '@react-oauth/google'; // Google 登入元件

// **Material UI 元件**
import {
  Box, // 佈局容器 (類似 div)
  Paper, // 卡片式 UI 容器
  TextField, // 輸入框
  Button, // 按鈕
  IconButton, // 圖示按鈕
  Typography, // 文字標題
  InputAdornment, // 輸入框內部圖示
  CircularProgress, // 旋轉加載動畫
} from "@mui/material";
import { Visibility, VisibilityOff, Email, Lock } from "@mui/icons-material"; // 圖示元件

function LoginPage() {
  // **React Hook Form - 表單管理**
  const {
    register, // 註冊輸入欄位
    handleSubmit, // 處理表單提交
    setError, // 設定表單錯誤
    formState: { errors }, // 表單錯誤狀態
  } = useForm();

  // **Jotai - 全局狀態管理**
  const [, setAuth] = useAtom(authAtom); // 設定全局身份驗證狀態
  const [showPassword, setShowPassword] = useState(false); // 控制密碼可見性
  const [loading, setLoading] = useState(false); // 控制登入按鈕的 loading 狀態
  const navigate = useNavigate(); // React Router 的導航 Hook

  // **表單提交處理函式**
  const onSubmit = async (data) => {
    setLoading(true); // 啟用 loading 狀態

    try {
      // 發送登入請求
      const response = await API.post("/login", data);
      const token = response.data.access_token; // 使用 "access_token" 而非 "token"
      const user = response.data.user; // 存儲使用者資訊

      if (!token) throw new Error("Token 未提供");



      // **獲取完整的使用者資訊**
      const userDetailsResponse = await API.get("/user/details", {
        headers: { Authorization: `Bearer ${token}` },
      });

      const userDetails = userDetailsResponse.data;

      // **更新 Jotai（這會自動存入 localStorage）**
      setAuth({
        access_token: token,
        user: userDetails.user,
        punch_records: userDetails.punch_records,
        roles_permissions: userDetails.roles_permissions,
        recent_leaves: userDetails.recent_leaves,
      });

      // **導航到打卡頁面**
      navigate("/punchin");
    } catch (error) {
      const status = error.response?.status;
      let errorMessage = "無法連線至伺服器，請稍後再試";
      if (status === 401) {
        errorMessage = error.response?.data?.error || "信箱或密碼錯誤";
      } else if (status === 500) {
        errorMessage = error.response?.data?.error || "伺服器錯誤";
      }
      setError("email", { message: errorMessage });
    } finally {
      setLoading(false); // **請求完成後關閉 loading**
    }
  };

  return (
    <Box
      sx={{
        width: "100vw", // 佔滿整個視口寬度
        height: "100vh", // 佔滿整個視口高度
        display: "flex", // 啟用 Flexbox
        alignItems: "center", // 垂直置中
        justifyContent: "center", // 水平置中
        backgroundColor: "#ffffff", // 背景顏色
      }}
    >
      <Paper
        elevation={0} // 無陰影
        sx={{
          maxWidth: 350, // 最大寬度
          width: "100%", // 充滿容器
          textAlign: "center", // 文字置中
          padding: "30px", // 內邊距
          borderRadius: "10px", // 圓角
        }}
      >
        {/* **應用程式 Logo** */}
        <img
          src="/logo.png"
          alt="Dacall Logo"
          style={{ width: 140, display: "block", margin: "0 auto 20px" }} // Logo 設定
        />

        {/* **登入標題** */}
        <Typography variant="h5" fontWeight="bold" sx={{ mb: 2 }}>
          Sign in with Email
        </Typography>

        {/* **登入表單** */}
        <form onSubmit={handleSubmit(onSubmit)}>
          {/* **Email 輸入框** */}
          <TextField
            fullWidth
            margin="normal"
            label="請輸入 Email"
            variant="outlined"
            {...register("email", {
              required: "Email 為必填",
              pattern: { value: /^\S+@\S+$/i, message: "Email 格式錯誤" },
            })}
            error={!!errors.email}
            helperText={errors.email?.message}
            InputProps={{
              startAdornment: (
                <InputAdornment position="start">
                  <Email sx={{ color: "#757575" }} />
                </InputAdornment>
              ),
            }}
          />

          {/* **密碼輸入框** */}
          <TextField
            fullWidth
            margin="normal"
            label="請輸入密碼"
            variant="outlined"
            type={showPassword ? "text" : "password"}
            {...register("password", { required: "密碼為必填" })}
            error={!!errors.password}
            helperText={errors.password?.message || errors.email?.message}
            InputProps={{
              startAdornment: (
                <InputAdornment position="start">
                  <Lock sx={{ color: "#757575" }} />
                </InputAdornment>
              ),
              endAdornment: (
                <InputAdornment position="end">
                  <IconButton
                    onClick={() => setShowPassword(!showPassword)}
                    edge="end"
                  >
                    {showPassword ? <VisibilityOff /> : <Visibility />}
                  </IconButton>
                </InputAdornment>
              ),
            }}
          />

          {/* **忘記密碼連結** */}
          <Box textAlign="right" sx={{ mb: 2 }}>
            <Link
              to="/forgot/password" // 導航到忘記密碼頁面
              style={{ fontSize: "14px", color: "#757575" }}
            >
              忘記密碼
            </Link>
          </Box>

          {/* **登入按鈕** */}
          <Button
            type="submit"
            fullWidth
            disabled={loading} // 禁用按鈕防止多次點擊
            sx={{
              backgroundColor: loading ? "#E0E0E0" : "#C3E6CB",
              color: "#000",
              fontWeight: "bold",
              padding: "12px",
              borderRadius: "20px",
              mb: 1,
              "&:hover": { backgroundColor: loading ? "#E0E0E0" : "#A5D6A7" },
              "&:active": { backgroundColor: loading ? "#E0E0E0" : "#81C784" },
            }}
          >
            {loading ? <CircularProgress size={24} /> : "登入"}
          </Button>

          {/* **註冊按鈕** */}
          <Button
            component={Link}
            to="/Register"
            fullWidth
            sx={{
              backgroundColor: "#E0E0E0",
              color: "#000",
              fontWeight: "bold",
              padding: "12px",
              borderRadius: "20px",
              "&:hover": { backgroundColor: "#BDBDBD" },
              "&:active": { backgroundColor: "#9E9E9E" },
            }}
          >
            註冊
          </Button>
        </form>


        <Typography variant="h6" sx={{ mt: 2 }}>
          或使用 Google 登入
        </Typography>

        <GoogleLogin
          onSuccess={async (credentialResponse) => {
            try {
              const googleToken = credentialResponse.credential;

              const res = await API.post("/login/google", {
                access_token: googleToken,
              });

              const token = res.data.token;
              const user = res.data.user;

              if (!token) throw new Error("未收到 token");

              // 🔄 取得使用者詳細資料（如果你有 /user/details API）
              const userDetailsResponse = await API.get("/user/details", {
                headers: { Authorization: `Bearer ${token}` },
              });

              const userDetails = userDetailsResponse.data;

              setAuth({
                access_token: token,
                user: userDetails.user,
                punch_records: userDetails.punch_records,
                roles_permissions: userDetails.roles_permissions,
                recent_leaves: userDetails.recent_leaves,
              });

              navigate("/punchin");
            } catch (error) {
              console.error("Google 登入失敗", error);
              setError("email", { message: "Google 登入失敗，請再試一次" });
            }
          }}
          onError={() => {
            console.log("Google 登入失敗");
            setError("email", { message: "Google 登入失敗，請再試一次" });
          }}
        />

      </Paper>
    </Box>
  );
}

export default LoginPage;
