import { useState } from "react"; // React Hook 用於管理元件的內部狀態
import { useEffect } from "react";
import { useAtom } from "jotai"; // 從 Jotai 引入 `useAtom`，用來讀取 `authAtom`
import { authAtom } from "../state/authAtom"; // Jotai Atom 用於存儲身份驗證狀態
import API from "../api/axios"; // Axios 實例，用於發送 API 請求

// **Material UI 元件**
import {
  Box, // 佈局容器 (類似 div)
  Paper, // 用於包裝內容，提供陰影與邊框效果
  Button, // 按鈕
  Typography, // 文字標題
  InputAdornment,
  Table, // 表格
  TableBody, // 表格內容
  TableCell,
  TableContainer, // 包裹table，允許內容滾動
  TableHead, // 表頭
  TablePagination, // 負責分頁內容
  TableRow,
  Dialog,
  DialogActions,
  DialogContent,
  FormControlLabel,
  Radio,
  RadioGroup,
  TextField,
  Fab,
} from "@mui/material";
import ManageSearchIcon from "@mui/icons-material/ManageSearch"; // 放大鏡圖示
import CalendarTodayIcon from "@mui/icons-material/CalendarToday"; // 📅 日期圖示
import AddIcon from "@mui/icons-material/Add"; // ➕加號按鈕
import {
  DatePicker,
  LocalizationProvider,
  TimePicker,
} from "@mui/x-date-pickers";
import { AdapterDateFns } from "@mui/x-date-pickers/AdapterDateFns";

// 表格欄位
const columns = [
  { id: "id", label: "ID", minwidth: 10 },
  { id: "user_name", label: "申請人", minwidth: 100 },
  { id: "date", label: "日期", minwidth: 100 },
  { id: "time", label: "時間", minWidth: 100 },
  { id: "correction_type", label: "班別", minWidth: 100, align: "center" },
  { id: "reason", label: "原因", minWidth: 150, align: "center" },
  { id: "created_at", label: "申請日期", minWidth: 100 },
  { id: "status", label: "申請狀態", minWidth: 150 },
  { id: "actions", label: "申請選項", minWidth: 150 },
];

function ClockReissueHistoryPage() {
  // **React Hook Form - 表單管理**

  // **Jotai - 全局狀態管理**
  // const [, setAuth] = useAtom(authAtom); // setAuth 更新 Jotai 全局狀態 (authAtom)

  // 設定起始 & 結束日期
  const [startDate, setStartDate] = useState(new Date());
  const [isInitialLoad, setIsInitialLoad] = useState(true);
  const [endDate, setEndDate] = useState(new Date());
  const [rows, setRows] = useState([]);
  const [page, setPage] = useState(0);
  const [rowsPerPage, setRowsPerPage] = useState(10);
  const [totalRecords, setTotalRecords] = useState(0); // API 回傳總筆數

  const [loading, setLoading] = useState(false); // 是否在載入中
  const [error, setError] = useState(null); // 儲存錯誤訊息

  // 存放當前選中的資料
  const [selectedRow, setSelectedRow] = useState(null);
  // 開啟 & 關閉 Dialog
  const [openDetailsDialog, setOpenDetailsDialog] = useState(false); // 申請詳情視窗
  const [openAddDialog, setOpenAddDialog] = useState(false); // 新增申請視窗
  // 用於控制「修改」視窗的開關
  const [openEditDialog, setOpenEditDialog] = useState(false);
  // 用於存放正在編輯的那一列數據
  const [editRow, setEditRow] = useState(null);

  // 控制 Dialog 開關
  const [date, setDate] = useState(null);
  const [time, setTime] = useState(null);
  const [shift, setShift] = useState("上班"); // 預設為 "上班"
  const [reason, setReason] = useState("忘記打卡");

  // 使用 useEffect 在畫面載入時請求 API
  // useEffect是React Hook，初次渲染時自動執行一次
  // 取得使用者資訊
  useEffect(() => {
    const fetchUserInfo = async () => {
      try {
        const response = await API.get("/user/details");
      } catch (error) {
        console.error("載入資料失敗:", error);
      }
    };
    fetchUserInfo();
  }, []);

  // 依照查詢條件篩選
  const handleSearch = async (
    newPage = page,
    newRowsPerPage = rowsPerPage,
    resetPage = false
  ) => {
    if (resetPage) {
      setPage(0); // 先重設頁碼
      await new Promise((resolve) => setTimeout(resolve, 0)); // 🛠 強制等待 React 更新 state
    }

    // 設定 `isInitialLoad` 為 `false`，避免總是取 `2025-01-01`
    setIsInitialLoad(false);

    const pageNum = resetPage ? 0 : isNaN(newPage) ? 0 : Number(newPage);
    const rowsPerPageNum = isNaN(newRowsPerPage) ? 10 : Number(newRowsPerPage);

    setLoading(true);
    setError(null);

    try {
      // 格式化 `startDate` & `endDate` 為 `YYYY-MM-DD`
      const formattedStartDate = isInitialLoad
        ? "2025-01-01"
        : startDate.toISOString().split("T")[0];

      const formattedEndDate = endDate.toISOString().split("T")[0];

      let query = `/punch/correction?
          start_date=${formattedStartDate}&
          end_date=${formattedEndDate}&
          page=${pageNum + 1}&
          per_page=${rowsPerPageNum}`;

      const response = await API.get(query);
      console.log("URL", query);

      const corrections = response.data?.data?.data || [];

      const total = response.data.data.data[0].total_records || 0; // 取得總筆數

      if (!Array.isArray(corrections))
        throw new Error("API 回應的 data.data 不是陣列");

      // **處理 API 回應資料**
      const formattedCorrections = corrections
        .filter((item) => {
          const punchDate = item.punch_time.split(" ")[0]; // 取出 punch_time 的日期
          return (
            punchDate >= formattedStartDate && punchDate <= formattedEndDate
          );
        })
        .map((item) => {
          return {
            ...item,
            date: item.punch_time.split(" ")[0],
            time: item.punch_time.split(" ")[1],
            created_at: item.created_at.split(" ")[0],
            correction_type:
              item.correction_type === "punch_in" ? "上班打卡" : "下班打卡",
            status:
              item.status === "approved"
                ? "審核通過"
                : item.status === "rejected"
                ? "審核未通過"
                : "待審核",
            review_message: item.review_message || "",
          };
        });

      setRows(formattedCorrections);
      setTotalRecords(total); // 設定總筆數
    } catch (error) {
      setRows([]);
      setTotalRecords(0); // 避免 totalRecords 遺留錯誤值

      console.error("錯誤詳情:", error.response?.data || error.message);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    handleSearch(page, rowsPerPage);
  }, [page, rowsPerPage]);

  // 新增申請
  const handleAddRecord = async () => {
    if (!date) {
      alert("請選擇日期！");
      return;
    }

    if (!time) {
      alert("請選擇時間！");
      return;
    }

    if (!reason.trim()) {
      alert("請輸入原因！");
      return;
    }

    // 組合 punch_time（日期 + 時間）
    const padZero = (num) => String(num).padStart(2, "0");
    const punchDate = `${date.getFullYear()}-${padZero(
      date.getMonth() + 1
    )}-${padZero(date.getDate())}`;
    const punchTime = time.toLocaleTimeString("en-GB", {
      hour: "2-digit",
      minute: "2-digit",
      hour12: false,
    }); // HH:mm:ss
    const punchDateTime = `${punchDate} ${punchTime}:00`;

    // 對應到後端 API 所需欄位格式
    const payload = {
      correction_type: shift === "上班" ? "punch_in" : "punch_out",
      punch_time: punchDateTime,
      reason: reason,
    };

    try {
      // 發送 POST 請求新增資料
      await API.post("/punch/correction", payload);

      alert("新增成功！");
      setOpenAddDialog(false); // 關閉 Dialog

      // 清空欄位
      setDate(null);
      setTime(null);
      setShift("上班");
      setReason("");

      // 重新查詢以更新列表
      handleSearch(0, rowsPerPage, true);
    } catch (error) {
      console.error("新增失敗：", error.response?.data || error.message);
    }
  };

  // 換頁
  const handleChangePage = (event, newPage) => {
    setPage(newPage); // 更新當前頁面索引
  };

  // 更改每頁顯示筆數
  const handleChangeRowsPerPage = (event) => {
    setRowsPerPage(parseInt(event.target.value, 10)); // 解析數字並更新
    setPage(0); // 回到第一頁，避免超出頁碼範圍
  };

  // 處理載入與錯誤
  if (loading) return <Typography>Loading...</Typography>;
  if (error) return <Typography color="error">{error}</Typography>;

  return (
    <Box
      sx={{
        width: "100%", // 佔滿整個視口寬度
        height: "100%", // 佔滿整個視口高度
        display: "flex", // 啟用 Flexbox
        flexDirection: "column", // 讓內容垂直排列
        alignItems: "center",
        backgroundColor: "#ffffff", // 背景顏色
      }}
    >
      <Paper
        elevation={0} // 無陰影
        sx={{
          width: "90%",
          flex: "1",
          display: "flex",
          flexDirection: "column", // 讓內部元素垂直排列
          alignItems: "center", // 讓內部內容水平置中
          padding: "20px",
        }}
      >
        {/* **登入標題** */}
        <Typography
          variant="h4"
          fontWeight={900}
          textAlign="center"
          sx={{ mb: 1 }}
        >
          查詢補打卡紀錄
        </Typography>

        <Box
          sx={{
            backgroundColor: "#D2E4F0", // 淺藍色背景
            width: "90%",
            padding: "10px",
            borderRadius: "8px", // 圓角邊框
            display: "flex",
            alignItems: "center", // 垂直置中
            textAlign: "center", // 文字置中
            justifyContent: "center", // 水平置中
            gap: 2, // 設定元素之間的間距
          }}
        >
          {/* 文字 */}
          <Typography variant="body1">選擇日期區間</Typography>
          <LocalizationProvider dateAdapter={AdapterDateFns}>
            {/* 起始日期 */}
            <DatePicker
              value={startDate}
              onChange={(newValue) => setStartDate(newValue)}
              maxDate={new Date()} // 不能選擇未來日期
              format="yyyy/MM/dd" // 確保格式正確
              slotProps={{
                textField: {
                  variant: "outlined",
                  size: "small",
                  placeholder: "請選擇日期",
                  sx: { backgroundColor: "white" }, // ✅ 確保輸入框為白色
                },
                input: {
                  endAdornment: (
                    <InputAdornment position="end">
                      <CalendarTodayIcon sx={{ fontSize: "18px" }} />
                    </InputAdornment>
                  ),
                },
              }}
            />

            {/* 分隔符號「~」 */}
            <Typography variant="body1">~</Typography>

            {/* 結束日期 */}
            <DatePicker
              value={endDate}
              onChange={(newValue) => setEndDate(newValue)}
              maxDate={new Date()} // 不能選擇未來日期
              format="yyyy/MM/dd"
              slotProps={{
                textField: {
                  variant: "outlined",
                  size: "small",
                  placeholder: "請選擇日期",
                  sx: { backgroundColor: "white" }, // ✅ 確保輸入框為白色
                },
                input: {
                  endAdornment: (
                    <InputAdornment position="end">
                      <CalendarTodayIcon sx={{ fontSize: "18px" }} />
                    </InputAdornment>
                  ),
                },
              }}
            />
          </LocalizationProvider>
        </Box>

        {/* **查詢按鈕** */}
        <Button
          variant="contained" // 使用實心樣式
          sx={{
            backgroundColor: "#AB9681",
            color: "white",
            fontWeight: "bold",
            fontSize: "18px",
            borderRadius: "20px",
            padding: "2px 40px",
            justifyContent: "flex-start", // 讓圖示靠左
            marginTop: "15px",
          }}
          startIcon={<ManageSearchIcon />} //讓放大鏡圖是在左邊
          onClick={handleSearch} // ✅ 點選後篩選日期範圍內的資料
        >
          查詢
        </Button>

        {/* overflow: "hidden" 防止滾動條溢出 */}
        <Paper
          sx={{
            height: "100%",
            width: "100%",
            overflow: "hidden",
            borderRadius: "8px",
            margin: "20px 0 0",
            display: "flex",
            flexDirection: "column",
          }}
        >
          {/* 表格 */}
          <TableContainer sx={{ flex: 1, overflow: "auto" }}>
            {/* stickyHeader 讓表頭固定，不受滾動影響 */}
            <Table stickyHeader>
              <TableHead>
                <TableRow>
                  {columns.map((column) => (
                    <TableCell
                      key={column.id}
                      align={column.align || "left"}
                      sx={{
                        minWidth: column.minWidth,
                        backgroundColor: "#f5f5f5",
                        fontWeight: "bold",
                        textAlign: "center",
                      }}
                    >
                      {column.label}
                    </TableCell>
                  ))}
                </TableRow>
              </TableHead>
              {/* 表格內容 */}
              <TableBody>
                {rows.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={columns.length} align="center">
                      無符合條件的資料
                    </TableCell>
                  </TableRow>
                ) : (
                  rows.map((row) => (
                    <TableRow key={row.id} hover>
                      {columns.map((column) => {
                        const value = row[column.id];
                        return (
                          <TableCell
                            key={column.id}
                            align={column.align || "center"}
                            sx={{ minWidth: column.minWidth }}
                          >
                            {column.id === "actions" ? (
                              <Button
                                variant="contained"
                                sx={{
                                  backgroundColor: "#D2B48C",
                                  color: "white",
                                  opacity: row.status === "待審核" ? 0.5 : 1,
                                  cursor:
                                    row.status === "待審核"
                                      ? "not-allowed"
                                      : "pointer",
                                }}
                                disabled={row.status === "待審核"}
                                onClick={() => {
                                  if (row.status !== "待審核") {
                                    setSelectedRow(row);
                                    setOpenDetailsDialog(true);
                                  }
                                }}
                              >
                                查詢
                              </Button>
                            ) : (
                              value
                            )}
                          </TableCell>
                        );
                      })}
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </TableContainer>
          {/* 分頁功能 */}
          <TablePagination
            rowsPerPageOptions={[10, 25, 50]} // 可選擇的每頁筆數
            component="div" // 告訴MUI這是一個div容器
            count={totalRecords} // 總資料筆數
            rowsPerPage={rowsPerPage} // 當前每頁顯示筆數
            page={page} // 當前頁碼(從0開始)
            onPageChange={handleChangePage} // 換頁時觸發的函式
            onRowsPerPageChange={handleChangeRowsPerPage} // 改變每頁顯示筆數時觸發
            sx={{
              borderTop: "1px solid #ddd", // 增加分隔線
              backgroundColor: "#fff", // 確保背景與表格一致
            }}
          />
        </Paper>
      </Paper>

      {/* 查詢原因彈出視窗 */}
      <Dialog
        open={openDetailsDialog}
        onClose={() => setOpenDetailsDialog(false)}
      >
        <DialogContent
          sx={{
            backgroundColor: "#D2E4F0",
            padding: "20px",
            display: "flex",
            flexDirection: "column",
            gap: 2,
          }}
        >
          {/* 申請人 & 日期 */}
          <Box sx={{ display: "flex", gap: 2 }}>
            <Box sx={{ flex: 1 }}>
              <b>申請人：</b>
              <TextField
                value={selectedRow?.user_name || ""}
                variant="outlined"
                size="small"
                fullWidth
                InputProps={{ readOnly: true }}
                sx={{ backgroundColor: "white" }}
              />
            </Box>

            <Box sx={{ flex: 1 }}>
              <b>日期：</b>
              <TextField
                value={selectedRow?.date || ""}
                variant="outlined"
                size="small"
                fullWidth
                InputProps={{ readOnly: true }}
                sx={{ backgroundColor: "white" }}
              />
            </Box>
          </Box>

          {/* 時間 & 原因 */}
          <Box sx={{ display: "flex", gap: 2 }}>
            <Box sx={{ flex: 1 }}>
              <b>時間：</b>
              <TextField
                value={selectedRow?.time || ""}
                variant="outlined"
                size="small"
                fullWidth
                InputProps={{ readOnly: true }}
                sx={{ backgroundColor: "white" }}
              />
            </Box>

            <Box sx={{ flex: 1 }}>
              <b>原因：</b>
              <TextField
                value={selectedRow?.reason || ""}
                variant="outlined"
                size="small"
                fullWidth
                InputProps={{ readOnly: true }}
                sx={{
                  color: "red",
                  fontWeight: "bold",
                  backgroundColor: "white",
                }}
              />
            </Box>
          </Box>

          {/* 申請日期 & 申請狀態 */}
          <Box sx={{ display: "flex", gap: 2 }}>
            <Box sx={{ flex: 1 }}>
              <b>申請日期：</b>
              <TextField
                value={selectedRow?.created_at || ""}
                variant="outlined"
                size="small"
                fullWidth
                InputProps={{ readOnly: true }}
                sx={{ backgroundColor: "white" }}
              />
            </Box>

            <Box sx={{ flex: 1 }}>
              <b>申請狀態：</b>
              <TextField
                value={selectedRow?.status || "N/A"}
                variant="outlined"
                size="small"
                fullWidth
                InputProps={{ readOnly: true }}
                sx={{ backgroundColor: "white" }}
              />
            </Box>
          </Box>

          {/* 拒絕原因（僅在申請被拒絕時顯示，獨立一行） */}
          {selectedRow?.status === "審核未通過" && (
            <Box>
              <b>拒絕原因：</b>
              <TextField
                value={selectedRow?.review_message || "無"}
                variant="outlined"
                size="small"
                fullWidth
                sx={{ backgroundColor: "white" }}
                disabled
              />
            </Box>
          )}
        </DialogContent>

        {/* 按鈕 */}
        <DialogActions
          sx={{
            justifyContent: "center",
            backgroundColor: "#D2E4F0",
            padding: "10px",
          }}
        >
          <Button
            variant="contained"
            sx={{
              backgroundColor: "#AB9681",
              color: "white",
              fontWeight: "bold",
              width: "80%",
              marginBottom: "5px",
            }}
            onClick={() => setOpenDetailsDialog(false)}
          >
            完成
          </Button>
        </DialogActions>
      </Dialog>

      {/* 右下角浮動按鈕 */}
      <Box>
        <Fab
          sx={{
            position: "fixed",
            bottom: "5%",
            right: 20,
            backgroundColor: "#4A4A4A",
            color: "white",
          }}
          onClick={() => setOpenAddDialog(true)} // 只開啟新增申請視窗
        >
          <AddIcon />
        </Fab>
        {/* 右下浮動按鈕的彈跳視窗 (Dialog) */}
        <Dialog open={openAddDialog} onClose={() => setOpenAddDialog(false)}>
          <DialogContent
            sx={{
              backgroundColor: "#D2E4F0",
              padding: "20px",
              display: "flex",
              flexDirection: "column",
              gap: 1,
            }}
          >
            <LocalizationProvider dateAdapter={AdapterDateFns}>
              <b>選擇日期</b>
              <DatePicker
                value={date}
                onChange={(newValue) => {
                  if (newValue) {
                    setDate(new Date(newValue)); // 確保是 Date 物件
                  }
                }}
                maxDate={new Date()} // 不能選擇未來日期
                format="yyyy/MM/dd"
                slotProps={{
                  textField: {
                    variant: "outlined",
                    size: "small",
                    placeholder: "請選擇日期",
                    sx: { backgroundColor: "white" }, // ✅ 確保輸入框為白色
                  },
                  input: {
                    endAdornment: (
                      <InputAdornment position="end">
                        <CalendarTodayIcon sx={{ fontSize: "18px" }} />
                      </InputAdornment>
                    ),
                  },
                }}
              />

              <b>選擇時間</b>
              <TimePicker
                value={time}
                onChange={(newValue) => setTime(newValue)}
                ampm={false} // 24小時制，確保格式統一
                format="HH:mm" // 確保格式為24小時制
                maxTime={new Date()} // 不能選擇未來時間
                slotProps={{
                  textField: {
                    variant: "outlined",
                    size: "small",
                    sx: { backgroundColor: "white" },
                  },
                }}
              />
            </LocalizationProvider>

            <b>選擇班別</b>
            <RadioGroup
              row
              value={shift}
              onChange={(e) => setShift(e.target.value)}
              sx={{ marginTop: "10px" }}
            >
              <FormControlLabel
                value="上班"
                control={<Radio color="default" />}
                label="上班"
              />
              <FormControlLabel
                value="下班"
                control={<Radio color="default" />}
                label="下班"
              />
            </RadioGroup>

            <b>原因</b>
            <TextField
              value={reason}
              onChange={(e) => setReason(e.target.value)}
              fullWidth
              variant="outlined"
              margin="dense"
              sx={{
                color: "red",
                fontWeight: "bold",
                backgroundColor: "white",
                marginBottom: "-10px",
              }}
            />
          </DialogContent>
          {/* 按鈕 */}
          <DialogActions
            sx={{
              justifyContent: "center",
              backgroundColor: "#D2E4F0",
              padding: "10px",
            }}
          >
            <Button
              variant="contained"
              sx={{
                backgroundColor: "#AB9681",
                color: "white",
                fontWeight: "bold",
                width: "80%",
                marginBottom: "5px",
              }}
              onClick={handleAddRecord}
            >
              送出
            </Button>
          </DialogActions>
        </Dialog>
      </Box>
    </Box>
  );
}

export default ClockReissueHistoryPage;
