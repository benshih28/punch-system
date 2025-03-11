import { useNavigate } from "react-router-dom";
import { useAtomValue } from "jotai";
import { authAtom } from "../state/authAtom";
import { Button, Box, Typography } from "@mui/material";
import { useState } from "react";
import AccessTimeIcon from "@mui/icons-material/AccessTime";
import HomeIcon from "@mui/icons-material/Home";

function PunchIn() {
  const auth = useAtomValue(authAtom); // 儲存使用者資訊
  const navigate = useNavigate();
  const [workTime, setWorkTime] = useState(null);
  const [offWorkTime, setOffWorkTime] = useState(null);
  const [shakeWork, setShakeWork] = useState(false);
  const [shakeOffWork, setShakeOffWork] = useState(false);

  // 處理上班打卡
  const handleWorkPunch = () => {
    setWorkTime(new Date().toLocaleTimeString());
    setShakeWork(true);
    setTimeout(() => setShakeWork(false), 500);
  };

  // 處理下班打卡
  const handleOffWorkPunch = () => {
    setOffWorkTime(new Date().toLocaleTimeString());
    setShakeOffWork(true);
    setTimeout(() => setShakeOffWork(false), 500);
  };

  return (
    <>
      <style>
        {`
          @keyframes shake {
            0% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            50% { transform: translateX(5px); }
            75% { transform: translateX(-5px); }
            100% { transform: translateX(0); }
          }
        `}
      </style>
      <Box
        sx={{
          display: "flex",
          alignItems: "center",
          justifyContent: "center",
          height: "80vh",
          padding: "20px",
          gap: 10,
        }}
      >
        {/* 左側 LOGO & 打卡按鈕區塊 */}
        <Box
          sx={{
            display: "flex",
            flexDirection: "column",
            alignItems: "center",
            textAlign: "center",
          }}
        >
          <img
            src="/logo.png"
            alt="Dacall Logo"
            style={{ width: "220px", marginBottom: "20px" }}
          />
          {["上班打卡", "下班打卡", "補打卡", "查詢打卡紀錄"].map(
            (text, index) => (
              <Button
                key={index}
                variant="contained"
                sx={{
                  display: "block",
                  mb: 2,
                  backgroundColor: "#625D5D",
                  width: "180px",
                  transition: "background-color 0.3s, transform 0.1s",
                  "&:hover": { backgroundColor: "#504A4A" },
                  "&:active": { transform: "scale(0.95)" },
                }}
                onClick={() => {
                  if (text === "上班打卡") handleWorkPunch();
                  else if (text === "下班打卡") handleOffWorkPunch();
                  else if (text === "補打卡")
                    navigate("/clock-reissue-history");
                  else if (text === "查詢打卡紀錄") navigate("/clock-history");
                }}
              >
                {text}
              </Button>
            )
          )}
        </Box>

        {/* 右側 時間顯示區塊 */}
        <Box sx={{ display: "flex", flexDirection: "column", gap: 3 }}>
          <Box
            sx={{
              backgroundColor: "#dbeafe",
              borderRadius: 4,
              padding: "20px",
              width: "220px",
              boxShadow: "3px 3px 10px rgba(0, 0, 0, 0.1)",
              display: "flex",
              alignItems: "center",
              gap: 2,
            }}
          >
            <Box
              sx={{
                display: "inline-block",
                animation: shakeWork ? "shake 0.5s" : "none",
                textShadow: "2px 2px 5px rgba(0,0,0,0.2)",
                filter: "drop-shadow(2px 2px 5px rgba(0,0,0,0.2))",
              }}
            >
              <AccessTimeIcon sx={{ fontSize: 50, color: "#FAFAFA" }} />
            </Box>
            <Box>
              <Typography variant="h6" fontWeight="bold" color="#333">
                上班
              </Typography>
              <Typography variant="h6" fontWeight="bold" color="#000">
                {workTime || "--:--:--"}
              </Typography>
            </Box>
          </Box>
          <Box
            sx={{
              backgroundColor: "#dbeafe",
              borderRadius: 4,
              padding: "20px",
              width: "220px",
              boxShadow: "3px 3px 10px rgba(0, 0, 0, 0.1)",
              display: "flex",
              alignItems: "center",
              gap: 2,
            }}
          >
            <Box
              sx={{
                display: "inline-block",
                animation: shakeOffWork ? "shake 0.5s" : "none",
                textShadow: "2px 2px 5px rgba(0,0,0,0.2)",
                filter: "drop-shadow(2px 2px 5px rgba(0,0,0,0.2))",
              }}
            >
              <HomeIcon sx={{ fontSize: 50, color: "#FAFAFA" }} />
            </Box>
            <Box>
              <Typography variant="h6" fontWeight="bold" color="#333">
                下班
              </Typography>
              <Typography variant="h6" fontWeight="bold" color="#000">
                {offWorkTime || "--:--:--"}
              </Typography>
            </Box>
          </Box>
        </Box>
      </Box>
    </>
  );
}

export default PunchIn;
