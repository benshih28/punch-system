import { useState, useRef } from "react";
import { Avatar, Popover, Box, Typography, Badge } from "@mui/material";

function NotificationPanel() {
  const [hasNotification] = useState(true); // 控制紅點顯示
  const [showNotificationBox, setShowNotificationBox] = useState(false);
  const avatarRef = useRef(null); // 確保通知框對齊頭像

  return (
    <>
      {/* 頭像 + 紅點 */}
      <Box sx={{ position: "relative", display: "inline-block" }}>
        <Badge
          color="error"
          variant={hasNotification ? "dot" : "standard"} // 控制紅點顯示
          overlap="circular"
          sx={{
            "& .MuiBadge-badge": {
              right: 5, //調整紅點水平位置
              top: 5, //調整紅點垂直位置
            },
          }}
        >
          <Avatar
            ref={avatarRef}
            src="/handshot.png"
            sx={{
              width: 40,
              height: 40,
              cursor: "pointer",
            }}
            onClick={() => setShowNotificationBox(!showNotificationBox)}
          />
        </Badge>
      </Box>

      {/* 通知欄，確保出現在頭像左下角 */}
      <Popover
        open={showNotificationBox}
        anchorEl={avatarRef.current} // 讓通知框對齊頭像
        onClose={() => setShowNotificationBox(false)}
        anchorOrigin={{ vertical: "bottom", horizontal: "left" }} // 設定通知框對齊頭像左下角
        transformOrigin={{ vertical: "top", horizontal: "left" }} // 讓通知框的頂部對齊頭像
        sx={{ marginTop: "5px" }} // 微調位置，讓通知框更貼近頭像
      >
        <Box
          sx={{
            padding: 2,
            minWidth: 250,
            borderRadius: "8px",
            bgcolor: "white",
            boxShadow: 3,
          }}
        >
          <Typography
            variant="body1"
            sx={{ display: "flex", alignItems: "center", gap: 1 }}
          >
            🔔 有一筆請假審核未通過
          </Typography>
          <Typography variant="body2" sx={{ marginTop: 1, color: "gray" }}>
            請假審核已批示
          </Typography>
        </Box>
      </Popover>
    </>
  );
}

export default NotificationPanel;
