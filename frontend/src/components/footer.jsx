import { useState } from "react"; // 使用 React 的 useState 來管理狀態
import { useForm } from "react-hook-form"; // 引入 react-hook-form 來處理表單驗證
import {
  Box,
  Typography,
  Modal,
  Fade,
  TextField,
  Button,
  Select,
  MenuItem,
  FormControl,
  InputLabel,
  Backdrop,
} from "@mui/material"; // 引入 Material UI 的元件
import CheckCircleIcon from "@mui/icons-material/CheckCircle"; // 引入 CheckCircleIcon 圖示
import api from "../api/axios";

function Footer() {
  const [open, setOpen] = useState(false); // 用於控制聯絡我們 Modal 開關的狀態

  // 使用 react-hook-form 來管理表單
  const {
    register, // 用於表單輸入框的註冊
    handleSubmit, // 用於處理表單提交
    reset, // 重置表單數據
    watch, // 監聽表單數據
    formState: { errors }, // 獲取表單錯誤狀態
  } = useForm({
    defaultValues: {
      issueType: "", // 設定預設值為空字串，確保不會是 undefined
    },
  });

  // 處理發送 Email 的函式
  const sendEmail = async (data) => {
    try {
      const response = await api.post("/send-email", data);

      if (response.data.success) {
        alert(response.data.message); // 顯示成功訊息
        setOpen(false); // 關閉 Modal
        reset(); // 重置表單
      } else {
        throw new Error(response.data.message);
      }
    } catch (error) {
      console.error("發送郵件失敗:", error.response?.data || error.message);
      alert(error.response?.data?.message || "發送失敗，請稍後再試！");
    }
  };

  return (
    <>
      {/* 頁腳區塊 */}
      <Box
        component="footer"
        sx={{
          height: "20px",
          width: "100%",
          padding: "10px",
          backgroundColor: "white",
          textAlign: "center",
          position: "fixed",
          bottom: 0,
          borderTop: "1px solid #e0e0e0",
          zIndex: 1000,
        }}
      >
        {/* 點擊後打開聯絡我們 Modal */}
        <Typography
          onClick={() => setOpen(true)}
          sx={{
            color: "#333",
            cursor: "pointer",
            fontWeight: "bold",
            "&:hover": { textDecoration: "underline" },
          }}
        >
          聯絡我們
        </Typography>
      </Box>

      {/* 聯絡我們 Modal 彈出視窗 */}
      <Modal
        open={open} // 控制 Modal 是否開啟
        onClose={() => setOpen(false)} // 點擊外部時關閉
        closeAfterTransition // 使 Modal 具有淡入淡出的過渡效果
        slots={{ backdrop: Backdrop }} // 設置背景遮罩
        slotProps={{ backdrop: { timeout: 500 } }} // 設定遮罩的淡入淡出時間
      >
        <Fade in={open}>
          <Box
            sx={{
              position: "absolute",
              top: "50%",
              left: "50%",
              transform: "translate(-50%, -50%)",
              width: 400,
              bgcolor: "#cfe2f3",
              borderRadius: "12px",
              boxShadow: 24,
              p: 4,
            }}
          >
            {/* Modal 標題 */}
            <Typography
              variant="h5"
              sx={{ fontWeight: "bold", textAlign: "center", mb: 3 }}
            >
              聯絡我們
            </Typography>

            {/* 聯絡我們表單 */}
            <form onSubmit={handleSubmit(sendEmail)}>
              {/* 姓名輸入框 */}
              <TextField
                label="姓名"
                fullWidth
                {...register("name", { required: "姓名為必填" })} // 註冊表單欄位，並設定必填驗證
                error={!!errors.name} // 顯示錯誤狀態
                helperText={errors.name?.message} // 錯誤提示訊息
                sx={{
                  mb: 2,
                  borderRadius: "8px",
                  "& .MuiInputBase-root": {
                    backgroundColor: "white", // 只影響輸入框
                    borderRadius: "8px",
                  },
                }}
              />

              {/* 聯絡方式輸入框 */}
              <TextField
                label="聯絡方式"
                type="email"
                fullWidth
                {...register("email", {
                  required: "請輸入電子郵件",
                  pattern: {
                    value: /^[^\s@]+@[^\s@]+\.[^\s@]+$/, // Email 格式驗證
                    message: "請輸入有效的電子郵件",
                  },
                })}
                error={!!errors.email}
                helperText={errors.email?.message}
                sx={{
                  mb: 2,
                  borderRadius: "8px",
                  "& .MuiInputBase-root": {
                    backgroundColor: "white", // 只影響輸入框
                    borderRadius: "8px",
                  },
                }}
              />

              {/* 問題類型選擇框 */}
              <FormControl
                fullWidth
                error={!!errors.issueType} // 讓錯誤時顯示紅色邊框
                sx={{
                  mb: 2,
                  "& .MuiInputBase-root": {
                    backgroundColor: "white", // 只讓 Select 本身有白色背景
                    borderRadius: "8px",
                  },
                }}
              >
                <InputLabel>問題類型</InputLabel>
                <Select
                  value={watch("issueType") || ""} // 確保 value 不為 undefined
                  {...register("issueType", { required: "請選擇問題類型" })}
                >
                  <MenuItem value="帳號問題">帳號問題</MenuItem>
                  <MenuItem value="系統錯誤">系統錯誤</MenuItem>
                  <MenuItem value="其他問題">其他問題</MenuItem>
                </Select>
                {/* 顯示錯誤提示 */}
                {errors.issueType && (
                  <Typography
                    color="error"
                    variant="caption"
                    sx={{
                      backgroundColor: "transparent",
                      display: "block",
                      paddingLeft: "14px", // 避免錯誤訊息太靠邊
                    }}
                  >
                    {errors.issueType.message}
                  </Typography>
                )}
              </FormControl>

              {/* 詳細描述輸入框 */}
              <TextField
                label="詳細描述"
                multiline
                rows={3}
                fullWidth
                {...register("message", { required: "請填寫詳細描述" })}
                error={!!errors.message}
                helperText={errors.message?.message}
                sx={{
                  mb: 2,
                  borderRadius: "8px",
                  "& .MuiInputBase-root": {
                    backgroundColor: "white", // 只影響輸入框
                    borderRadius: "8px",
                  },
                }}
              />

              {/* 送出按鈕 */}
              <Button
                type="submit"
                variant="contained"
                fullWidth
                sx={{
                  bgcolor: "#a58d7f",
                  color: "white",
                  borderRadius: "25px",
                  py: 1.5,
                  fontSize: "16px",
                  fontWeight: "bold",
                  "&:hover": { bgcolor: "#8d7667" },
                  display: "flex",
                  alignItems: "center",
                  justifyContent: "center",
                }}
              >
                <CheckCircleIcon sx={{ mr: 1 }} /> {/* 送出按鈕圖示 */}
                送出
              </Button>
            </form>
          </Box>
        </Fade>
      </Modal>
    </>
  );
}

export default Footer;
