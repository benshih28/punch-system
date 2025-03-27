import { useEffect, useState, useRef } from "react";
import {
  Avatar,
  Popover,
  Box,
  Typography,
  Badge,
  List,
  ListItem,
  Button,
  ToggleButton,
  ToggleButtonGroup,
  Pagination,
  Checkbox,
} from "@mui/material";
import { useAtomValue } from "jotai";
import { authAtom } from "../state/authAtom";
import { useNavigate } from "react-router-dom";
import { io } from "socket.io-client";
import api from "../api/axios";
import { formatDistanceToNow } from "date-fns";

function NotificationPanel() {
  const [notifications, setNotifications] = useState([]);
  const [showNotificationBox, setShowNotificationBox] = useState(false);
  const [filter, setFilter] = useState("all");
  const [page, setPage] = useState(1);
  const [selectedIds, setSelectedIds] = useState([]);
  const itemsPerPage = 5;
  const avatarRef = useRef(null);
  const navigate = useNavigate();

  const auth = useAtomValue(authAtom);
  const token = auth?.access_token;
  const userId = auth?.user?.id;
  const avatarUrl = auth?.user?.avatar
    ? `http://127.0.0.1:8000${auth.user.avatar}`
    : "/handshot.png";

  const hasNotification = notifications.some((n) => !n.read);

  useEffect(() => {
    if (!token || !userId) return;

    api.get("/notifications").then((res) => {
      const history = res.data
        .filter((n) => !n.archived)
        .map((n) => ({
          id: n.id,
          title: n.title,
          message: n.message,
          link: n.link || "/",
          read: n.read,
          created_at: n.created_at,
        }));
      setNotifications(history);
    });

    const socket = io("http://localhost:6001", {
      auth: { token },
    });

    socket.on("notification", (data) => {
      setNotifications((prev) => [
        {
          id: data.id,
          title: data.title,
          message: data.message,
          link: data.link || "/",
          read: false,
          created_at: new Date().toISOString(),
        },
        ...prev,
      ]);
    });

    return () => socket.disconnect();
  }, [token, userId]);

  const handleNotificationClick = (id, link) => {
    if (!id) return;
    api.post(`/notifications/${id}/read`).catch(() => {});
    setNotifications((prev) =>
      prev.map((n) => (n.id === id ? { ...n, read: true } : n))
    );
    setShowNotificationBox(false);
    navigate(link);
  };

  const archiveNotifications = (ids) => {
    api.post("/notifications/archive", { ids }).then(() => {
      setNotifications((prev) => prev.filter((n) => !ids.includes(n.id)));
    });
  };

  const clearPageNotifications = () => {
    const idsToClear = paginatedNotifications.map((n) => n.id);
    archiveNotifications(idsToClear);
  };

  const deleteSelectedNotifications = () => {
    archiveNotifications(selectedIds);
    setSelectedIds([]);
  };

  const handleCheckboxToggle = (id) => {
    setSelectedIds((prev) =>
      prev.includes(id) ? prev.filter((i) => i !== id) : [...prev, id]
    );
  };

  const handleClose = () => {
    setShowNotificationBox(false);
    setTimeout(() => {
      avatarRef.current?.focus();
    }, 0);
  };

  const filteredNotifications = notifications.filter((n) => {
    if (filter === "unread") return !n.read;
    if (filter === "read") return n.read;
    return true;
  });

  const pageCount = Math.ceil(filteredNotifications.length / itemsPerPage);
  const paginatedNotifications = filteredNotifications.slice(
    (page - 1) * itemsPerPage,
    page * itemsPerPage
  );

  if (!token || !userId) return null;

  return (
    <>
      <Box sx={{ position: "relative", display: "inline-block" }}>
        <Badge
          color="error"
          variant={hasNotification ? "dot" : "standard"}
          overlap="circular"
          sx={{ "& .MuiBadge-badge": { right: 5, top: 5 } }}
        >
          <Avatar
            ref={avatarRef}
            src={avatarUrl}
            sx={{ width: 40, height: 40, cursor: "pointer" }}
            onClick={() => setShowNotificationBox((prev) => !prev)}
            tabIndex={0}
          />
        </Badge>
      </Box>

      <Popover
        open={showNotificationBox}
        anchorEl={avatarRef.current}
        onClose={handleClose}
        anchorOrigin={{ vertical: "bottom", horizontal: "left" }}
        transformOrigin={{ vertical: "top", horizontal: "left" }}
        sx={{ marginTop: "5px" }}
        disableAutoFocus
        disableEnforceFocus
        disableRestoreFocus
        slotProps={{ paper: { tabIndex: -1 } }}
      >
        <Box
          sx={{
            padding: 2,
            minWidth: 300,
            borderRadius: "8px",
            bgcolor: "white",
            boxShadow: 3,
            maxHeight: 400,
            overflowY: "auto",
          }}
        >
          <Box sx={{ display: "flex", justifyContent: "space-between", mb: 1 }}>
            <Typography variant="h6">通知中心</Typography>
            <Button size="small" color="error" onClick={clearPageNotifications}>
              清除本頁
            </Button>
          </Box>

          <ToggleButtonGroup
            value={filter}
            exclusive
            onChange={(e, newFilter) => newFilter && setFilter(newFilter)}
            size="small"
            sx={{ mb: 1 }}
          >
            <ToggleButton value="all">全部</ToggleButton>
            <ToggleButton value="unread">未讀</ToggleButton>
            <ToggleButton value="read">已讀</ToggleButton>
          </ToggleButtonGroup>

          {paginatedNotifications.length > 0 ? (
            <>
              <List dense>
                {paginatedNotifications.map((n) => (
                  <ListItem
                    key={n.id}
                    sx={{
                      display: "flex",
                      alignItems: "flex-start",
                      cursor: "pointer",
                      backgroundColor: n.read ? "#f9f9f9" : "#fff",
                      borderRadius: 1,
                      mb: 0.5,
                      transition: "all 0.3s ease",
                      "&:hover": { backgroundColor: "#e6f0ff" },
                    }}
                  >
                    <Checkbox
                      checked={selectedIds.includes(n.id)}
                      onClick={(e) => e.stopPropagation()}
                      onChange={() => handleCheckboxToggle(n.id)}
                      size="small"
                      sx={{ mt: 0.5 }}
                    />
                    <Box onClick={() => handleNotificationClick(n.id, n.link)}>
                      <Typography
                        variant="body1"
                        sx={{
                          color: n.read ? "gray" : "black",
                          fontWeight: n.read ? "normal" : "bold",
                        }}
                      >
                        🔔 {n.title}
                      </Typography>
                      <Typography
                        variant="body2"
                        sx={{ color: n.read ? "gray" : "black" }}
                      >
                        {n.message}
                      </Typography>
                      <Typography
                        variant="caption"
                        sx={{ color: "gray", display: "block", mt: 0.5 }}
                      >
                        {formatDistanceToNow(new Date(n.created_at))} 前
                      </Typography>
                    </Box>
                  </ListItem>
                ))}
              </List>
              <Button
                fullWidth
                variant="outlined"
                color="error"
                size="small"
                onClick={deleteSelectedNotifications}
                sx={{ mt: 1 }}
                disabled={selectedIds.length === 0}
              >
                刪除勾選
              </Button>
            </>
          ) : (
            <Typography variant="body2" sx={{ color: "gray" }}>
              尚無通知
            </Typography>
          )}

          {pageCount > 1 && (
            <Pagination
              count={pageCount}
              page={page}
              onChange={(e, value) => setPage(value)}
              size="small"
              sx={{ mt: 2, display: "flex", justifyContent: "center" }}
            />
          )}
        </Box>
      </Popover>
    </>
  );
}

export default NotificationPanel;
