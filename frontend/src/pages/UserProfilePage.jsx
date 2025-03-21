import { useState, useEffect } from "react";
import { useForm } from "react-hook-form";
import axios from "axios";
import {
    Button, Card, CardContent, TextField, Avatar, Typography, Box, Dialog, DialogTitle,
    DialogContent, DialogActions, IconButton, Grid, Divider, InputAdornment
} from "@mui/material";
import CloseIcon from "@mui/icons-material/Close";
import AttachFileIcon from "@mui/icons-material/AttachFile";
import CheckCircleIcon from "@mui/icons-material/CheckCircle";
import { Visibility, VisibilityOff } from "@mui/icons-material";
import { useAtom } from "jotai";
import { authAtom } from "../state/authAtom";
import { useNavigate } from "react-router-dom";
import Tooltip from "@mui/material/Tooltip";

const UserProfilePage = () => {
    const [auth] = useAtom(authAtom);
    const navigate = useNavigate();

    useEffect(() => {
        // 移除 body 預設 margin
        document.body.style.margin = "0";

        // 在元件移除時還原 margin，避免影響其他頁面
        return () => {
            document.body.style.margin = "";
        };
    }, []);

    const { register, handleSubmit, reset } = useForm();
    const [avatar, setAvatar] = useState(null);
    const [open, setOpen] = useState(false);
    const [user, setUser] = useState({
        name: "",
        position: "",
        gender: "",
        avatar: "",
        punch_records: { punch_in: null, punch_out: null },
        recent_leaves: []
    });

    //當畫面載入時，從 localStorage 讀取 user 資料
    useEffect(() => {
        const storedAuthData = localStorage.getItem("auth");
        // console.log("從 localStorage 讀取 user 資料:", storedUserData);

        if (storedAuthData) {
            try {
                const parsedData = JSON.parse(storedAuthData);
                // console.log("解析的 user 資料:", parsedData.user); 
                setUser({
                    name: parsedData.user?.name || "",
                    position: parsedData.user?.position || "",
                    gender: parsedData.user?.gender || "",
                    avatar: parsedData.user?.avatar || "",
                    punch_records: parsedData.punch_records || { punch_in: null, punch_out: null },
                    recent_leaves: parsedData.recent_leaves || []
                });
            } catch (error) {
                console.error("解析 user 資料失敗:", error);
            }
        }
        else {
            console.warn("localStorage 內沒有 auth 資料");
        }
    }, [auth]);

    // 開啟 & 關閉 Modal
    const handleOpen = () => setOpen(true);
    const handleClose = () => {
        setOpen(false);
        reset();
        setAvatar(null);
    };

    // 處理圖片上傳
    const handleAvatarChange = (e) => {
        const file = e.target.files[0];
        // setAvatar(file);
        if (file) {
            setAvatar(file);
        }
    };

    // 處理個人資料更新
    const onSubmit = async (data) => {
        try {
            // const authState = store.get(authAtom);
            const token = auth?.access_token || localStorage.getItem("access_token");
            const formData = new FormData();
            if (data.old_password) formData.append("old_password", data.old_password);
            if (data.new_password) formData.append("new_password", data.new_password);
            if (data.new_password_confirmation)
                formData.append("new_password_confirmation", data.new_password_confirmation);
            if (avatar) formData.append("avatar", avatar);

            // await axios.post("user/update/profile", formData, {
            const response = await axios.post("http://localhost:8000/api/user/update/profile", formData, {
                headers: {
                    "Content-Type": "multipart/form-data",
                    "Authorization": `Bearer ${token}`
                },
            });

            // 確保 response 正確回傳 avatar
            const updatedAvatar = response.data?.avatar || user.avatar;
            // console.log("更新後的新 avatar URL:", updatedAvatar);

            // 更新前端狀態
            const updatedUser = { ...user, avatar: updatedAvatar };
            setUser(updatedUser);

            // 更新 LocalStorage
            const storedAuthData = JSON.parse(localStorage.getItem("auth")) || {};
            if (storedAuthData.user) {
                storedAuthData.user.avatar = updatedAvatar;
                localStorage.setItem("auth", JSON.stringify(storedAuthData));
            }
            alert("個人資料已更新");
            handleClose();
        } catch (error) {
            console.error("錯誤詳情:", error.response?.data || error.message);
            alert(error.response?.data?.message || "更新失敗");
        }
    };

    // 加入 useState 來控制密碼顯示or隱藏
    const [showOldPassword, setShowOldPassword] = useState(false);
    const [showNewPassword, setShowNewPassword] = useState(false);
    const [showConfirmPassword, setShowConfirmPassword] = useState(false);

    const toggleOldPassword = () => setShowOldPassword(!showOldPassword);
    const toggleNewPassword = () => setShowNewPassword(!showNewPassword);
    const toggleConfirmPassword = () => setShowConfirmPassword(!showConfirmPassword);

    return (
        <Box display="flex" flexDirection="column" minHeight="100vh" padding={0} margin={0}>

            {/* 個人資訊區塊*/}
            <Box
                bgcolor="#bbdefb"
                padding={4}
                top={0}
                left={0}
                m={0}
                display="flex"
                alignItems="center"
                justifyContent="space-between"
                width="100vw"
                boxSizing="border-box"
                // border="2px solid red"
                position="relative"
            >
                <Box display="flex" alignItems="center">
                    <Avatar
                        src={user?.avatar ? `http://localhost:8000${user.avatar}` : "/default-avatar.png"}
                        // src={`http://localhost:8000${user?.avatar}`}
                        onError={(e) => { e.target.src = "/default-avatar.png"; }}
                        alt=""
                        sx={{ width: 100, height: 100, marginRight: 2 }}
                    />
                    {/* 個人資訊區塊（左側） */}
                    <Box display="flex" flexDirection="column" spacing={1}>
                        <Typography variant="h5" fontWeight="bold">{user.name}</Typography>
                        <Typography color="textSecondary" sx={{ backgroundColor: "#ddd", padding: "2px 6px", borderRadius: "4px", display: "inline-block", mt: 1 }}>
                            {user.position}
                        </Typography>
                        <Typography color="textSecondary" sx={{ mt: 1 }}> {user.gender === "male" ? "男" : "女"}</Typography>
                    </Box>
                </Box>
                <Button variant="contained" onClick={handleOpen} sx={{ height: 40, backgroundColor: "black", "&:hover": { backgroundColor: "#333" } }}>
                    修改資料
                </Button>
            </Box>

            {/*  下方區塊（打卡、請假） */}
            <Grid container spacing={2} padding={4}>
                {/* 今日打卡（左半部） */}
                <Grid item xs={12} md={6}>
                    <Card sx={{ height: "100%" }}>
                        <CardContent sx={{ display: "flex", flexDirection: "column", height: "100%" }}>
                            <Typography variant="h6" fontWeight="bold">今日打卡</Typography>
                            <Divider sx={{ my: 1 }} />

                            {/* 上班時間 & 下班時間 */}
                            {["上班時間", "下班時間"].map((label, index) => (
                                <Box key={index} sx={{ display: "flex", alignItems: "center", width: "100%", mb: 1 }}>
                                    {/* 左側：上班、下班 */}
                                    <Typography sx={{
                                        flex: "none",
                                        marginRight: 2,
                                        display: "inline-block",
                                        minWidth: "60px",
                                        fontWeight: "bold",
                                    }}>
                                        {label}
                                    </Typography>

                                    {/* 時間顯示 */}
                                    <Typography sx={{
                                        flex: 1,
                                        textAlign: "left",
                                        whiteSpace: "nowrap"
                                    }}>
                                        {index === 0
                                            ? (user.punch_records.punch_in ? new Date(user.punch_records.punch_in).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false }) : "--")
                                            : (user.punch_records.punch_out ? new Date(user.punch_records.punch_out).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false }) : "--")
                                        }
                                    </Typography>
                                </Box>
                            ))}
                            {/* 右側：鬧鐘圖片*/}
                            {/* <Box pr={2}>
                                    <img src="/clock.png" alt="Clock" style={{ width: 80, height: 80 }} />
                                </Box> */}
                        </CardContent>
                    </Card>
                </Grid>

                {/* 右半部：近期請假 */}
                <Grid item xs={12} md={6} container direction="column" spacing={2}>
                    <Grid item>
                        <Card sx={{ height: "100%" }}>
                            <CardContent>
                                <Typography variant="h6" fontWeight="bold">近期請假 (顯示五筆)</Typography>
                                <Divider sx={{ my: 1 }} />
                                {user.recent_leaves.length > 0 ? (
                                    user.recent_leaves.map((leave, index) => (
                                        <Box key={index}
                                            //  sx={{ display: "flex", justifyContent: "space-between",mb: 1 ,gap: 0,}}>
                                            sx={{ display: "flex", alignItems: "center", mb: 1 }}>

                                            {/* 請假類型 */}
                                            <Tooltip title={leave.leave_type?.description.includes("喪假") ? leave.leave_type.description : ""}
                                                placement="top-start" arrow
                                            >
                                                <Typography variant="subtitle1"
                                                    sx={{
                                                        // border: "1px solid red",
                                                        flex: "none",
                                                        display: "inline-block",
                                                        minWidth: "60px",
                                                        fontWeight: "bold",
                                                        marginRight: 0  // 設定與時間的間距
                                                    }}>
                                                    {leave.leave_type?.description.includes("喪假") ? "喪假" : leave.leave_type?.description || "未知類型"}
                                                </Typography>
                                            </Tooltip>
                                            {/* 請假時間 */}
                                            <Typography variant="body2" sx={{ flex: 1, textAlign: "left", whiteSpace: "nowrap" }}>
                                                {new Date(leave.start_time).toLocaleString("zh-TW", {
                                                    year: "numeric", month: "2-digit", day: "2-digit",
                                                    hour: "2-digit", minute: "2-digit", second: "2-digit", hour12: false
                                                })}
                                                {" ~ "}
                                                {new Date(leave.end_time).toLocaleString("zh-TW", {
                                                    year: "numeric", month: "2-digit", day: "2-digit",
                                                    hour: "2-digit", minute: "2-digit", second: "2-digit", hour12: false
                                                })}
                                            </Typography>
                                        </Box>
                                    ))
                                ) : (
                                    <Typography variant="body2" color="textSecondary">無近期請假</Typography>
                                )}
                            </CardContent>
                        </Card>
                    </Grid>

                </Grid>
            </Grid>

            {/* 彈跳視窗 (Modal) */}
            <Dialog
                open={open}
                onClose={handleClose}
                maxWidth="xs"
                fullWidth
                sx={{
                    "& .MuiDialog-paper": {
                        maxWidth: "340px",
                        backgroundColor: "#d0e4fd",
                        borderRadius: "16px",
                        padding: "20px",
                        height: "auto"
                    }
                }}
            >
                {/* <DialogTitle sx={{ color: "#000", fontWeight: "bold", fontSize: "18px" }}>
                    修改資料
                </DialogTitle> */}

                <DialogContent>
                    <form onSubmit={handleSubmit(onSubmit)}
                        style={{ display: "flex", flexDirection: "column", gap: "8px", overflow: "hidden" }}>

                        {/* 上傳大頭照 */}
                        <Typography fontWeight="bold">大頭貼</Typography>
                        <Box
                            sx={{
                                display: "flex",
                                alignItems: "center",
                                backgroundColor: "white",
                                borderRadius: "8px",
                                padding: "6px",
                                //   border: "1px solid #ccc",
                            }}
                        >
                            <input type="file" onChange={handleAvatarChange} accept="image/*" style={{ flex: 1, border: "none", outline: "none" }} />
                            <IconButton>
                                <AttachFileIcon />
                            </IconButton>
                        </Box>

                        {/* 密碼輸入框 */}
                        <Typography fontWeight="bold">舊密碼</Typography>
                        <TextField
                            placeholder="請輸入舊密碼"
                            type={showOldPassword ? "text" : "password"}
                            {...register("old_password")}
                            fullWidth
                            sx={{
                                backgroundColor: "white", borderRadius: "8px",
                                "& .MuiInputBase-root": { height: "50px" }
                            }}
                            InputProps={{
                                endAdornment: (
                                    <IconButton onClick={toggleOldPassword}>
                                        {showOldPassword ? <VisibilityOff /> : <Visibility />}
                                    </IconButton>
                                ),
                            }}
                        />

                        <Typography fontWeight="bold">新密碼</Typography>
                        <TextField
                            placeholder="請輸入新密碼"
                            type={showNewPassword ? "text" : "password"}
                            {...register("new_password")}
                            fullWidth
                            sx={{
                                backgroundColor: "white", borderRadius: "8px",
                                "& .MuiInputBase-root": { height: "50px" },
                                mt: 0
                            }}
                            InputProps={{
                                endAdornment: (
                                    <IconButton onClick={toggleNewPassword}>
                                        {showNewPassword ? <VisibilityOff /> : <Visibility />}
                                    </IconButton>
                                ),
                            }}
                        />

                        <Typography fontWeight="bold">確認密碼</Typography>
                        <TextField
                            placeholder="請再次輸入新密碼"
                            type={showConfirmPassword ? "text" : "password"}
                            {...register("new_password_confirmation")}
                            fullWidth
                            sx={{
                                backgroundColor: "white", borderRadius: "8px",
                                "& .MuiInputBase-root": { height: "50px" }
                            }}
                            InputProps={{
                                endAdornment: (
                                    <IconButton onClick={toggleConfirmPassword}>
                                        {showConfirmPassword ? <VisibilityOff /> : <Visibility />}
                                    </IconButton>
                                ),
                            }}

                        />

                        {/* 按鈕 */}
                        <DialogActions sx={{ justifyContent: "center" }}>
                            <Button
                                type="submit"
                                variant="contained"
                                sx={{
                                    backgroundColor: "#a1887f",
                                    color: "white",
                                    borderRadius: "25px",
                                    padding: "10px 30px",
                                    fontSize: "16px",
                                    display: "flex",
                                    alignItems: "center",
                                    "&:hover": { backgroundColor: "#8d6e63" },
                                }}
                            >
                                <CheckCircleIcon sx={{ marginRight: "8px" }} />
                                儲存
                            </Button>
                        </DialogActions>
                    </form>
                </DialogContent>
            </Dialog>
        </Box >
    );
};

export default UserProfilePage;
