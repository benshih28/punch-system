import { useState } from "react"; // React Hook 用於管理元件的內部狀態
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
  TextField,
} from "@mui/material";
import ManageSearchIcon from "@mui/icons-material/ManageSearch"; // 放大鏡圖示
import CalendarTodayIcon from "@mui/icons-material/CalendarToday"; // 📅 日期圖示
import {
  DatePicker,
  LocalizationProvider,
} from "@mui/x-date-pickers";
import { AdapterDateFns } from "@mui/x-date-pickers/AdapterDateFns";

function ApproveClockReissuePage() {
  // **React Hook Form - 表單管理**

  // **Jotai - 全局狀態管理**
  const [, setAuth] = useAtom(authAtom); // setAuth 更新 Jotai 全局狀態 (authAtom)

  // 設定起始 & 結束日期
  const [startDate, setStartDate] = useState(new Date());
  const [endDate, setEndDate] = useState(new Date());
  const [page, setPage] = useState(0);
  const [rowsPerPage, setRowsPerPage] = useState(10);
  // 設定部門 & 員工編號
  const [department, setDepartment] = useState("");
  const [employeeId, setEmployeeId] = useState("");
  // 存放當前選中的資料
  const [selectedRow, setSelectedRow] = useState(null);
  // 開啟 & 關閉 Dialog
  const [openDetailsDialog, setOpenDetailsDialog] = useState(false); // 審核詳情視窗

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
      department: "人資部",
      employeeId: "A123",
      date: "2024/07/08",
      time: "08:00",
      shift: "上班",
      reason: "忘記打卡",
      applicationDate: "2024/07/09",
      status: "待審核",
    },
    {
      id: 2,
      applicant: "李大壯",
      department: "工程部",
      employeeId: "B456",
      date: "2024/07/03",
      time: "18:00",
      shift: "下班",
      reason: "忘記打卡",
      applicationDate: "2024/07/04",
      status: "審核通過",
    },
    {
      id: 3,
      applicant: "石中建",
      department: "財務部",
      employeeId: "C789",
      date: "2024/07/01",
      time: "18:00",
      shift: "下班",
      reason: "忘記打卡",
      applicationDate: "2024/07/02",
      status: "審核未通過",
    },
  ]);

  const [filteredRows, setFilteredRows] = useState(rows);  // 預設顯示所有資料

  // 新增狀態來儲存錯誤訊息
  const [rejectionError, setRejectionError] = useState("");

  const handleReviewOpen = (row) => {
    setSelectedRow({
      ...row,
      status: "待審核", // 確保審核狀態預設為「待審核」
      rejectionReason: "" // 預設清空拒絕原因
    });
    setOpenDetailsDialog(true);
  };

  const handleReviewSubmit = () => {
    if (!selectedRow) return;

    // **當選擇「審核未通過」但未填寫拒絕原因時，顯示錯誤**
    if (selectedRow.status === "審核未通過" && !selectedRow.rejectionReason.trim()) {
      setRejectionError("請輸入拒絕原因");
      return; // 阻止送出
    }

    // **清除錯誤訊息**
    setRejectionError("");

    // **更新 rows 陣列**
    const updatedRows = rows.map((row) =>
      row.id === selectedRow.id
        ? { ...row, status: selectedRow.status, rejectionReason: selectedRow.rejectionReason }
        : row
    );

    setRows(updatedRows);
    setFilteredRows(updatedRows); // **同步更新顯示的資料**

    setOpenDetailsDialog(false); // 關閉彈窗
  };



  const handleSearch = () => {

    // 確保 startDate 和 endDate 的時間為當天 00:00:00
    const normalizedStartDate = new Date(startDate);
    normalizedStartDate.setHours(0, 0, 0, 0);

    const normalizedEndDate = new Date(endDate);
    normalizedEndDate.setHours(23, 59, 59, 999); // 設定到當天 23:59:59，確保整天內的資料都包含

    const filtered = rows.filter((row) => {
      // 解析 row.date 成 Date 物件
      const [year, month, day] = row.date.split("/").map(Number);
      const rowDate = new Date(Date.UTC(year, month - 1, day)); // 確保時區是 UTC

      return rowDate >= normalizedStartDate && rowDate <= normalizedEndDate;
    });

    setFilteredRows(filtered);
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
          補打卡審核查詢
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
            flexWrap: "wrap" // 讓內容在小螢幕自動換行
          }}
        >
          {/* 文字 */}
          <Typography variant="body1">選擇部門</Typography>
          {/* 部門輸入框 */}
          <TextField
            variant="outlined"
            size="small"
            value={department}
            onChange={(e) => setDepartment(e.target.value)}
            sx={{ backgroundColor: "white", minWidth: "180px" }} // 白底，寬度限制
          />
          {/* 文字 */}
          <Typography variant="body1">員工編號</Typography>
          {/* 員工編號輸入框 */}
          <TextField
            variant="outlined"
            size="small"
            value={employeeId}
            onChange={(e) => setEmployeeId(e.target.value)}
            sx={{ backgroundColor: "white", minWidth: "180px" }}
          />

          {/* 文字 */}
          <Typography variant="body1">選擇日期區間</Typography>
          <LocalizationProvider dateAdapter={AdapterDateFns}>
            {/* 起始日期 */}
            <DatePicker
              value={startDate}
              onChange={(newValue) => newValue && setStartDate(new Date(newValue))}
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
              onChange={(newValue) => newValue && setEndDate(new Date(newValue))}
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
          onClick={handleSearch} // ✅ 點選後篩選日期
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
                      沒有符合條件的資料
                    </TableCell>
                  </TableRow>
                ) : (
                  filteredRows.slice(page * rowsPerPage, page * rowsPerPage + rowsPerPage).map((row) => (
                    <TableRow key={row.id} hover>
                      {columns.map((column) => {
                        const value = row[column.id];

                        return (
                          <TableCell key={column.id} align="center" sx={{ minWidth: column.minWidth }}>
                            {column.id === "actions" ? (
                              <Button
                                variant="contained"
                                sx={{ backgroundColor: "#D2B48C", color: "white" }}
                                onClick={() => handleReviewOpen(row)}
                              >
                                審核
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
        <Dialog open={openDetailsDialog} onClose={() => setOpenDetailsDialog(false)}>
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
                <b>日　期：</b>
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
                <b>時　間：</b>
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
                <b>原　因：</b>
                <TextField
                  value={selectedRow?.reason || ""}
                  variant="outlined"
                  size="small"
                  fullWidth
                  InputProps={{ readOnly: true }}
                  sx={{ backgroundColor: "white" }}
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
                  select
                  value={selectedRow?.status || "待審核"}
                  onChange={(e) => {
                    setSelectedRow((prev) => ({ ...prev, status: e.target.value }));
                    setRejectionError(""); // 切換狀態時清除錯誤訊息
                  }}
                  variant="outlined"
                  size="small"
                  fullWidth
                  SelectProps={{ native: true }}
                  sx={{ backgroundColor: "white" }}
                >
                  <option value="待審核">待審核</option>
                  <option value="審核通過">審核通過</option>
                  <option value="審核未通過">審核未通過</option>
                </TextField>
              </Box>
            </Box>

            {/* 拒絕原因（僅在申請狀態為「審核未通過」時顯示） */}
            {selectedRow?.status === "審核未通過" && (
              <Box>
                <b>拒絕原因：</b>
                <TextField
                  value={selectedRow?.rejectionReason || ""}
                  onChange={(e) =>
                    setSelectedRow((prev) => ({ ...prev, rejectionReason: e.target.value }))
                  }
                  variant="outlined"
                  size="small"
                  fullWidth
                  sx={{ backgroundColor: "white" }}
                  error={!!rejectionError} // 當有錯誤時顯示紅框
                  helperText={rejectionError} // 顯示錯誤訊息
                />
              </Box>
            )}
          </DialogContent>

          {/* 送出按鈕 */}
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
                borderRadius: "20px",
              }}
              onClick={handleReviewSubmit}
            >
              送出
            </Button>
          </DialogActions>
        </Dialog>
      </Paper>
    </Box>
  );
}

export default ApproveClockReissuePage;
