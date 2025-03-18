import { useForm } from "react-hook-form";
import { useState } from "react";
import { useNavigate } from "react-router-dom";
import api from "../api/axios";
import {
  TextField,
  Button,
  Select,
  MenuItem,
  InputLabel,
  FormControl,
  Container,
  Typography,
  Box,
  CircularProgress,
  IconButton,
  InputAdornment,
} from "@mui/material";
import Visibility from "@mui/icons-material/Visibility";
import VisibilityOff from "@mui/icons-material/VisibilityOff";

function Register() {
  const navigate = useNavigate();

  const {
    register,
    handleSubmit,
    watch,
    formState: { errors },
  } = useForm({
    defaultValues: { gender: "" },
  });

  const [loading, setLoading] = useState(false);
  const [apiError, setApiError] = useState(null);
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);

  const handleTogglePassword = () => setShowPassword(!showPassword);
  const handleToggleConfirmPassword = () =>
    setShowConfirmPassword(!showConfirmPassword);

  const onSubmit = async (data) => {
    setLoading(true);
    setApiError(null);
    // 註冊完畢後會導致登入畫面做登入，所以不會有token
    try {
      await api.post("/register", {
        name: data.name,
        email: data.email,
        gender: data.gender,
        password: data.password,
        password_confirmation: data.confirmPassword,
      });
      alert("註冊成功！請重新登入。");
      navigate("/login");
    } catch (error) {
      // console.error("錯誤詳情:", error.response?.data);

      // 確保錯誤結構符合 Laravel 回傳的格式
      if (error.response?.data?.errors?.email) {
        setApiError(error.response.data.errors.email[0]);
        alert("信箱已被註冊");
      } else if (error.response?.data?.message) {
        setApiError(error.response.data.message); // 通用錯誤
      } else {
        setApiError("註冊失敗，請稍後再試");
        alert("註冊失敗，请稍後再試");
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <Container
      sx={{
        display: "flex",
        justifyContent: "center",
        alignItems: "center",
        height: "100vh",
      }}
    >
      <Box
        sx={{
          width: 380,
          background: "linear-gradient(to bottom, #dbeafe, #ffffff)",
          borderRadius: 4,
          boxShadow: 3,
          p: 4,
          textAlign: "center",
          border: "1px solid #e0e0e0",
        }}
      >
        <img
          src="/logo.png"
          alt="Dacall Logo"
          style={{
            width: 120,
            marginBottom: -10,
            position: "absolute",
            top: 20,
            left: 20,
          }}
        />
        <Typography variant="h5" fontWeight="bold" textAlign="center" mb={2}>
          建立帳號
        </Typography>
        {apiError && (
          <Typography color="error" mb={2}>
            {apiError}
          </Typography>
        )}
        <form onSubmit={handleSubmit(onSubmit)}>
          <TextField
            fullWidth
            label="人員姓名"
            variant="filled"
            {...register("name", { required: "姓名為必填項目" })}
            error={!!errors.name}
            helperText={errors.name?.message}
            margin="normal"
          />
          <TextField
            fullWidth
            label="Email"
            type="email"
            variant="filled"
            {...register("email", {
              required: "Email 為必填項目",
              pattern: {
                value: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
                message: "請輸入有效的 Email",
              },
            })}
            error={!!errors.email}
            helperText={errors.email?.message}
            margin="normal"
          />
          <FormControl
            fullWidth
            margin="normal"
            variant="filled"
            error={!!errors.gender}
          >
            <InputLabel>性別</InputLabel>
            <Select
              {...register("gender", { required: "請選擇性別" })}
              defaultValue=""
            >
              <MenuItem value="">請選擇</MenuItem>
              <MenuItem value="male">男</MenuItem>
              <MenuItem value="female">女</MenuItem>
            </Select>
            <Typography color="error" variant="caption">
              {errors.gender?.message}
            </Typography>
          </FormControl>
          {/* 密碼欄位 */}
          <TextField
            fullWidth
            label="密碼"
            type={showPassword ? "text" : "password"}
            variant="filled"
            {...register("password", {
              required: "密碼為必填項目",
              pattern: {
                value:
                  /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/,
                message: "密碼需包含大小寫字母、數字和特殊符號，至少8位",
              },
            })}
            error={!!errors.password}
            helperText={errors.password?.message}
            margin="normal"
            slotProps={{
              input: {
                endAdornment: (
                  <InputAdornment position="end">
                    <IconButton onClick={handleTogglePassword} edge="end">
                      {showPassword ? <VisibilityOff /> : <Visibility />}
                    </IconButton>
                  </InputAdornment>
                ),
              },
            }}
          />

          {/* 確認密碼欄位 */}
          <TextField
            fullWidth
            label="確認密碼"
            type={showConfirmPassword ? "text" : "password"}
            variant="filled"
            {...register("confirmPassword", {
              required: "請再次輸入密碼",
              validate: (value) =>
                value === watch("password") || "密碼與確認密碼不匹配",
            })}
            error={!!errors.confirmPassword}
            helperText={errors.confirmPassword?.message}
            margin="normal"
            slotProps={{
              input: {
                endAdornment: (
                  <InputAdornment position="end">
                    <IconButton
                      onClick={handleToggleConfirmPassword}
                      edge="end"
                    >
                      {showConfirmPassword ? <VisibilityOff /> : <Visibility />}
                    </IconButton>
                  </InputAdornment>
                ),
              },
            }}
          />
          {/* 註冊按鈕 */}
          <Button
            type="submit"
            variant="contained"
            fullWidth
            sx={{
              mt: 3,
              backgroundColor: "#8B5E3B",
              borderRadius: 999,
              padding: "10px 0",
              "&:hover": { backgroundColor: "#6E4A2F" },
            }}
            disabled={loading}
          >
            {loading ? (
              <CircularProgress size={24} color="inherit" />
            ) : (
              "建立帳號"
            )}
          </Button>
          {/* 返回登入按鈕 */}
          <Button
            fullWidth
            variant="outlined"
            sx={{ mt: 2, borderRadius: 999, padding: "10px 0" }}
            onClick={() => navigate("/login")}
          >
            返回登入
          </Button>
        </form>
      </Box>
    </Container>
  );
}

export default Register;
