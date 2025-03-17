import { useState } from "react"; // React Hook 用於管理元件的內部狀態
import { useForm } from "react-hook-form"; // React Hook Form 用於表單管理
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

function ClockReissueHistoryPage() {
  // **React Hook Form - 表單管理**

  // **Jotai - 全局狀態管理**
  // const [, setAuth] = useAtom(authAtom); // setAuth 更新 Jotai 全局狀態 (authAtom)

  // 設定起始 & 結束日期
  const [startDate, setStartDate] = useState(new Date());
  const [endDate, setEndDate] = useState(new Date());
  const [page, setPage] = useState(0);
  const [rowsPerPage, setRowsPerPage] = useState(10);

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
  const [open, setOpen] = useState(false);
  const [date, setDate] = useState(null);
  const [time, setTime] = useState(null);
  const [shift, setShift] = useState("上班"); // 預設為 "上班"
  const [reason, setReason] = useState("忘記打卡");

  const columns = [
    { id: "applicant", label: "申請人", minwidth: 100 },
    { id: "date", label: "日期", minwidth: 100 },
    { id: "time", label: "時間", minWidth: 100 },
    { id: "shift", label: "班別", minWidth: 100, align: "center" },
    {
      id: "reason",
      label: "原因",
      minWidth: 150,
      align: "center",
    },
    { id: "applicationDate", label: "申請日期", minWidth: 100 },
    { id: "status", label: "申請狀態", minWidth: 150 },
    { id: "actions", label: "申請選項", minWidth: 150 },
  ];
  const [rows, setRows] = useState([
    {
      id: 1,
      applicant: "王小美",
      date: "2024/07/08",
      time: "08:00",
      shift: "上班",
      reason: "忘記打卡",
      applicationDate: "2024/07/09",
      status: "待審核",
    },
    {
      id: 2,
      applicant: "王小美",
      date: "2024/07/03",
      time: "18:00",
      shift: "下班",
      reason: "忘記打卡",
      applicationDate: "2024/07/04",
      status: "審核通過",
    },
    {
      id: 3,
      applicant: "王小美",
      date: "2024/07/01",
      time: "18:00",
      shift: "下班",
      reason: "忘記打卡",
      applicationDate: "2024/07/02",
      status: "審核未通過",
    },
  ]);

  // 刪除申請
  const handleDelete = (id) => {
    setRows(rows.filter((row) => row.id !== id));
  };

  // 新增申請
  const handleAddRecord = () => {
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

    // 產生新的 ID
    const newId = rows.length > 0 ? rows[rows.length - 1].id + 1 : 1;

    const newRow = {
      id: newId,
      applicant: "王小美", // 預設申請人
      date: date.toLocaleDateString("zh-TW"), // 格式化日期
      time: time.toLocaleTimeString("zh-TW", { hour: "2-digit", minute: "2-digit", hour12: false }), // 24 小時制
      shift,
      reason,
      applicationDate: new Date().toLocaleDateString("zh-TW"), // 申請日期為當天
      status: "待審核", // 預設狀態
    };

    // 更新 rows & filteredRows，確保畫面同步
    const updatedRows = [...rows, newRow];
    setRows(updatedRows);

    // 直接更新 `filteredRows`，確保畫面立刻顯示新資料
    setFilteredRows(updatedRows.filter((row) => {
      const [year, month, day] = row.date.split("/").map(Number);
      const rowDate = new Date(year, month - 1, day);
      return rowDate >= startDate && rowDate <= endDate;
    }));

    setOpenAddDialog(false); // 關閉彈跳視窗

    // 清空表單
    setDate(null);
    setTime(null);
    setShift("上班");
    setReason(""); // 清空輸入欄位
  };

  const handleSearch = () => {
    console.log("篩選條件 - 開始日期:", startDate, "結束日期:", endDate);

    if (!startDate || !endDate) {
      alert("請選擇完整的日期範圍！");
      return;
    }

    // 確保開始日期為 00:00:00，結束日期為 23:59:59
    const normalizedStartDate = new Date(startDate);
    normalizedStartDate.setHours(0, 0, 0, 0);

    const normalizedEndDate = new Date(endDate);
    normalizedEndDate.setHours(23, 59, 59, 999);

    // 過濾 rows，篩選符合日期範圍內的資料
    const filtered = rows.filter((row) => {
      const [year, month, day] = row.date.split("/").map(Number);
      const rowDate = new Date(year, month - 1, day);

      return rowDate >= normalizedStartDate && rowDate <= normalizedEndDate;
    });

    console.log("篩選結果:", filtered);
    setFilteredRows(filtered.length > 0 ? filtered : []); // ✅ 確保即使無資料，也更新狀態
  };

  const [filteredRows, setFilteredRows] = useState(rows); // 預設顯示所有資料

  // 打開修改視窗，並設定當前編輯的資料
  const handleEditOpen = (row) => {
    setEditRow({
      ...row,
      time: row.time ? new Date(`1970-01-01T${row.time}:00`) : null, // 確保 time 是 Date 物件
      shift: row.shift || "上班"
    });
    setOpenEditDialog(true);
  };

  // 送出修改
  const handleEditRecord = () => {
    if (!editRow.date || !editRow.time || !editRow.reason.trim()) {
      alert("請填寫完整資訊！");
      return;
    }

    setRows(rows.map((row) => (row.id === editRow.id ? { ...row, ...editRow } : row)));

    setOpenEditDialog(false);
  };

  const handleChangePage = (event, newPage) => {
    setPage(newPage); // 更新當前頁面索引
  };

  const handleChangeRowsPerPage = (event) => {
    setRowsPerPage(+event.target.value); // 解析數字並更新
    setPage(0); // 回到第一頁，避免超出頁碼範圍
  };

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
                {filteredRows.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={columns.length} align="center">
                      無符合條件的資料
                    </TableCell>
                  </TableRow>
                ) : (
                  filteredRows.slice(page * rowsPerPage, page * rowsPerPage + rowsPerPage).map((row) => (
                    <TableRow key={row.id} hover>
                      {columns.map((column) => {
                        const value = row[column.id];
                        return (
                          <TableCell key={column.id} align={column.align || "center"} sx={{ minWidth: column.minWidth }}>
                            {column.id === "actions" ? (
                              row.status === "待審核" ? (
                                <>
                                  <Button variant="contained" sx={{ mr: 1, backgroundColor: "#D2B48C", color: "white" }} onClick={() => handleDelete(row.id)}>
                                    刪除
                                  </Button>
                                  <Button variant="contained" sx={{ backgroundColor: "#D2B48C", color: "white" }} onClick={() => handleEditOpen(row)}>
                                    修改
                                  </Button>
                                </>
                              ) : (
                                <Button variant="contained" sx={{ backgroundColor: "#D2B48C", color: "white" }} onClick={() => { setSelectedRow(row); setOpenDetailsDialog(true); }}>
                                  查詢
                                </Button>
                              )
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
            count={rows.length} // 總資料筆數
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
      {/* 頁尾 */}
      <Box
        sx={{
          width: "100%",
          mt: "auto",
          textAlign: "center",
          position: "absolute", // 讓頁尾固定在底部
          bottom: 0, // 設定在底部
          overflow: "hidden", // ✅ 隱藏滾動條
        }}
      >
        <hr style={{ width: "100%", marginBottom: "10px" }} />
        <Typography sx={{ fontSize: "20px", fontWeight: "bold" }}>
          聯絡我們
        </Typography>
      </Box>
      <Dialog open={openEditDialog} onClose={() => setOpenEditDialog(false)}>
        <DialogContent
          sx={{
            backgroundColor: "#D2E4F0",
            padding: "20px",
            display: "flex",
            flexDirection: "column",
            gap: 2,
          }}
        >
          {/* 修改日期 */}
          <b>選擇日期</b>
          <LocalizationProvider dateAdapter={AdapterDateFns}>
            <DatePicker
              value={editRow?.date ? new Date(editRow.date) : null}
              onChange={(newValue) =>
                setEditRow((prev) => ({ ...prev, date: newValue.toLocaleDateString("zh-TW") }))
              }
              maxDate={new Date()}
              format="yyyy/MM/dd"
              slotProps={{
                textField: { variant: "outlined", size: "small", sx: { backgroundColor: "white" } },
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

          {/* 修改時間 */}
          <b>選擇時間</b>
          <LocalizationProvider dateAdapter={AdapterDateFns}>
            <TimePicker
              value={editRow?.time ? new Date(`1970-01-01T${editRow.time}:00`) : null}
              onChange={(newValue) => {
                if (newValue) {
                  const formattedTime = newValue.toLocaleTimeString("zh-TW", {
                    hour: "2-digit",
                    minute: "2-digit",
                    hour12: false, // 24小時制
                  });
                  setEditRow((prev) => ({ ...prev, time: formattedTime }));
                }
              }}
              ampm={false}
              format="HH:mm"
              slotProps={{
                textField: { variant: "outlined", size: "small", sx: { backgroundColor: "white" } },
              }}
            />
          </LocalizationProvider>

          {/* 修改班別 */}
          <b>班別</b>
          <RadioGroup row value={editRow?.shift || "上班"} onChange={(e) => setEditRow((prev) => ({ ...prev, shift: e.target.value }))}>
            <FormControlLabel value="上班" control={<Radio color="default" />} label="上班" />
            <FormControlLabel value="下班" control={<Radio color="default" />} label="下班" />
          </RadioGroup>

          {/* 修改原因 */}
          <b>原因</b>
          <TextField
            value={editRow?.reason || ""}
            onChange={(e) => setEditRow((prev) => ({ ...prev, reason: e.target.value }))}
            fullWidth
            variant="outlined"
            sx={{ color: "red", fontWeight: "bold", backgroundColor: "white" }}
          />
        </DialogContent>

        {/* 送出按鈕 */}
        <DialogActions sx={{ justifyContent: "center", backgroundColor: "#D2E4F0", padding: "10px" }}>
          <Button
            variant="contained"
            sx={{ backgroundColor: "#AB9681", color: "white", fontWeight: "bold", width: "80%" }}
            onClick={handleEditRecord}
          >
            送出
          </Button>
        </DialogActions>
      </Dialog>
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
                value={selectedRow?.applicant || ""}
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
                value={selectedRow?.applicationDate || ""}
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
                variant="outlined"
                size="small"
                fullWidth
                placeholder="輸入拒絕原因"
                sx={{ backgroundColor: "white" }}
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
            送出
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
