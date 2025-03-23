import { useSetAtom, useAtomValue } from "jotai";
import { authAtom } from "../state/authAtom";
import { useNavigate } from "react-router-dom";
import { Button, Box, Typography } from "@mui/material";
import { useEffect, useState } from "react";
import AccessTimeIcon from "@mui/icons-material/AccessTime";
import HomeIcon from "@mui/icons-material/Home";
import api from "../api/axios";

// 時間格式化函式：轉成 Asia/Taipei 的 HH:mm:ss
function formatToTaiwanTime(utcTime) {
  if (!utcTime) return null;
  const date = new Date(utcTime);

  return date.toLocaleString("zh-TW", {
    timeZone: "Asia/Taipei",
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
    hour12: false,
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
  });
}

function PunchIn() {
  const auth = useAtomValue(authAtom);
  // 新增這行：用來更新 authAtom
  const setAuth = useSetAtom(authAtom);
  const navigate = useNavigate();
  const [workTime, setWorkTime] = useState(null);
  const [offWorkTime, setOffWorkTime] = useState(null);
  const [shakeType, setShakeType] = useState("");

  // 當 authAtom 更新時，更新 workTime 和 offWorkTime
  useEffect(() => {
    if (auth?.punch_records?.punch_in) {
      setWorkTime(auth.punch_records.punch_in);
    }

    if (auth?.punch_records?.punch_out) {
      setOffWorkTime(auth.punch_records.punch_out);
    }
  }, [auth]);

  const triggerShake = (type) => {
    setShakeType(type);
    setTimeout(() => setShakeType(""), 500);
  };

  const handlePunch = async (type) => {
    try {
      triggerShake(type);
      const apiUrl = type === "work" ? "/punch/in" : "/punch/out";
      const response = await api.post(apiUrl);

      if (response.status === 201) {
        const time =
          response.data[type === "work" ? "punch_in" : "punch_out"].timestamp;

        if (type === "work") {
          setWorkTime(time);
          // ✅ 更新 authAtom.punch_records
          setAuth((prev) => ({
            ...prev,
            punch_records: {
              ...prev.punch_records,
              punch_in: time,
            },
          }));
        } else {
          setOffWorkTime(time);
          // ✅ 更新 authAtom.punch_records
          setAuth((prev) => ({
            ...prev,
            punch_records: {
              ...prev.punch_records,
              punch_out: time,
            },
          }));
        }

        alert(response.data.message || "打卡成功");
      }
    } catch (error) {
      handleError(error);
    }
  };

  const handleError = (error) => {
    if (!error.response) {
      alert("無法連接伺服器，請檢查網路或稍後再試！");
      return;
    }

    const { status, data } = error.response;
    const apiMessage = data?.message || "打卡失敗，請稍後再試！";

    if (status === 401) {
      alert(apiMessage);
      navigate("/login");
    } else {
      alert(apiMessage);
    }
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
          flexDirection: { xs: "column", md: "row" },
        }}
      >
        {/* 左側 LOGO & 按鈕區 */}
        <Box
          sx={{
            display: "flex",
            flexDirection: "column",
            alignItems: "center",
            width: { xs: "100%", md: "auto" },
          }}
        >
          <img
            src="/logo.png"
            alt="Dacall Logo"
            style={{ width: "250px", marginBottom: "20px" }}
          />

          {auth?.user?.name && (
            <Typography
              variant="h6"
              sx={{
                mb: 2,
                fontWeight: "bold",
                color: "#555",
                textAlign: "center",
              }}
            >
              歡迎, {auth.user.name} 👋
            </Typography>
          )}

          {/* 打卡按鈕 */}
          {[
            { text: "上班打卡", action: () => handlePunch("work") },
            { text: "下班打卡", action: () => handlePunch("offWork") },
            {
              text: "補打卡",
              action: () => navigate("/clock/reissue/history"),
            },
            { text: "查詢打卡紀錄", action: () => navigate("/clock/history") },
          ].map(({ text, action }, index) => (
            <Button
              key={index}
              variant="contained"
              sx={{
                display: "block",
                mb: 2,
                backgroundColor: "#625D5D",
                width: { xs: "90%", sm: "250px" },
                "&:hover": { backgroundColor: "#504A4A" },
              }}
              onClick={action}
            >
              {text}
            </Button>
          ))}
        </Box>

        {/* 右側 時間顯示區 */}
        <Box sx={{ display: "flex", flexDirection: "column", gap: 3 }}>
          {[
            {
              label: "上班",
              time: workTime,
              icon: <AccessTimeIcon />,
              shake: shakeType === "work",
            },
            {
              label: "下班",
              time: offWorkTime,
              icon: <HomeIcon />,
              shake: shakeType === "offWork",
            },
          ].map(({ label, time, shake }, index) => (
            <Box
              key={index}
              sx={{
                backgroundColor: "#dbeafe",
                borderRadius: 4,
                padding: "20px",
                width: { xs: "90%", sm: "250px" },
                boxShadow: "3px 3px 10px rgba(0, 0, 0, 0.1)",
                display: "flex",
                alignItems: "center",
                gap: 2,
              }}
            >
              <Box
                sx={{
                  animation: shake ? "shake 0.5s" : "none",
                  textShadow: "2px 2px 5px rgba(0,0,0,0.2)",
                  filter: "drop-shadow(2px 2px 5px rgba(0,0,0,0.2))",
                }}
              >
                {label === "上班" ? (
                  <AccessTimeIcon sx={{ fontSize: 50, color: "#FAFAFA" }} />
                ) : (
                  <HomeIcon sx={{ fontSize: 50, color: "#FAFAFA" }} />
                )}
              </Box>
              <Box>
                <Typography variant="h6" fontWeight="bold" color="#333">
                  {label}
                </Typography>
                <Typography variant="h6" fontWeight="bold" color="#000">
                  {formatToTaiwanTime(time) || "--:--:--"}
                </Typography>
              </Box>
            </Box>
          ))}
        </Box>
      </Box>
    </>
  );
}

export default PunchIn;
