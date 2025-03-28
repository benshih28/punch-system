import { useState } from "react"; // React Hook 用於管理元件的內部狀態
import { useAtom } from "jotai"; // 從 Jotai 引入 `useAtom`，用來讀取 `authAtom`
import { authAtom } from "../state/authAtom"; // Jotai Atom 用於存儲身份驗證狀態
import { errorAtom } from "../state/errorAtom"; // Jotai Atom 用於存儲錯誤訊息
import { logoutAtom } from "../state/authAtom"; // Jotai Atom 用於登出
import { useEffect } from "react"; // 用於獲取API
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
  MenuItem,
} from "@mui/material";
import ManageSearchIcon from "@mui/icons-material/ManageSearch"; // 放大鏡圖示
import CalendarTodayIcon from "@mui/icons-material/CalendarToday"; // 📅 日期圖示
import { DatePicker, LocalizationProvider } from "@mui/x-date-pickers";
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

function ApproveClockReissuePage() {
  // **Jotai - 全局狀態管理**
  const [authState] = useAtom(authAtom); // 取得使用者狀態
  const departmentId = authState?.user?.department_id;

  // 設定起始 & 結束日期 & 頁數 & 限制筆數
  const [startDate, setStartDate] = useState(() => {
    const now = new Date();
    return new Date(now.getFullYear(), 0, 1);
  });
  // 起始日期
  const [endDate, setEndDate] = useState(new Date());
  const [page, setPage] = useState(0);
  const [rowsPerPage, setRowsPerPage] = useState(10);
  const [totalRecords, setTotalRecords] = useState(0); // 存儲 API 返回的總筆數

  // 設定部門 & 員工編號
  const [departments, setDepartments] = useState([]); // 存放所有部門
  const [, setDepartmentId] = useState(null); // 存儲部門 ID
  const [selectedDepartment, setSelectedDepartment] = useState(null); // 選擇的部門 ID
  const [, setSelectedDepartmentName] = useState(""); // 存部門名稱
  const [employeeId, setEmployeeId] = useState("");

  // 存放當前選中的資料
  const [selectedRow, setSelectedRow] = useState(null);
  // 新增狀態來儲存錯誤訊息
  const [rejectionError, setRejectionError] = useState("");
  // 新增無權限狀態
  const [unauthorized, setUnauthorized] = useState(false);
  // 取得錯誤訊息
  const [errorMessage] = useAtom(errorAtom);

  // 開啟 & 關閉 Dialog
  const [openDetailsDialog, setOpenDetailsDialog] = useState(false); // 審核詳情視窗

  // 狀態管理
  const [rows, setRows] = useState([]); // 存放API回傳的資料
  const [filteredRows, setFilteredRows] = useState([]); // 篩選後的資料
  const [loading, setLoading] = useState(true); // 載入狀態
  const [error, setError] = useState(null); // 錯誤訊息

  // 使用 useEffect 在畫面載入時請求 API
  // useEffect是React Hook，初次渲染時自動執行一次
  // 取得使用者資訊
  useEffect(() => {
    // async（非同步）函數，用來向後端 API 發送請求並獲取數據
    const fetchUserInfo = async () => {
      try {
          if (departmentId !== 1) {
            setUnauthorized(true); // 不是人資則標記為無權限
            return;
          }

        API.get("/departments")
          .then((response) => {
            if (Array.isArray(response.data.departments)) {
              setDepartments(response.data.departments);
            }
          })
      } catch (error) {
        console.error("錯誤詳情:", error.message);
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

    const pageNum = resetPage ? 0 : isNaN(newPage) ? 0 : Number(newPage);
    const rowsPerPageNum = isNaN(newRowsPerPage) ? 10 : Number(newRowsPerPage);

    setLoading(true);
    setError(null);
    setUnauthorized(false);

    try {
      const formattedStartDate = startDate.getFullYear() + "-" +
        String(startDate.getMonth() + 1).padStart(2, "0") + "-" +
        String(startDate.getDate()).padStart(2, "0");

      const formattedEndDate = endDate.getFullYear() + "-" +
        String(endDate.getMonth() + 1).padStart(2, "0") + "-" +
        String(endDate.getDate()).padStart(2, "0");


      let query = `/corrections?
          start_date=${formattedStartDate}&
          end_date=${formattedEndDate}&
          page=${pageNum + 1}&
          per_page=${rowsPerPageNum}`;

      if (selectedDepartment && !isNaN(selectedDepartment)) {
        query += `&department_id=${selectedDepartment}`; // 選擇部門
      }

      if (employeeId && employeeId !== "") {
        query += `&user_id=${employeeId}`; // 選擇員工
      }

      const response = await API.get(query);
      console.log("URL", query);

      const corrections = response.data?.data?.data || [];
      const total = corrections.length > 0 ? response.data.data.data[0].total_records || 0 : 0; // 取得總筆數

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
          };
        });

      setRows(formattedCorrections);
      setFilteredRows(formattedCorrections);
      setTotalRecords(total); // 設定總筆數
    } catch (error) {
      setRows([]);
      setFilteredRows([]);
      setTotalRecords(0); // 避免 totalRecords 遺留錯誤值

      console.error("錯誤詳情:", error.response?.data || error.message);
    } finally {
      setLoading(false);
    }
  };

  // 換頁
  const handleChangePage = (event, newPage) => {
    setPage(newPage);
  };

  // 更改每頁顯示筆數
  const handleChangeRowsPerPage = (event) => {
    const newRowsPerPage = parseInt(event.target.value, 10);
    setRowsPerPage(newRowsPerPage);
    setPage(0); // 重新回到第一頁
  };

  // 更改查詢的部門
  const handleDepartmentChange = async (event) => {
    const newDepartment = Number(event.target.value); // 確保存數字 ID
    setSelectedDepartment(newDepartment);

    const selectedDept = departments.find((dept) => dept.id === newDepartment);
    setSelectedDepartmentName(selectedDept ? selectedDept.name : "");

    setDepartmentId(newDepartment); // 同步更新 `departmentId`
    setUnauthorized(false); // 重置無權限狀態
  };

  useEffect(() => {
    handleSearch(page, rowsPerPage);
  }, [page, rowsPerPage]);

  // 不是人資時，顯示無權限
  if (unauthorized) {
    return (
      <Box sx={{ textAlign: "center", mt: 4 }}>
        <Typography variant="h5" color="error">
          無權限查看資料
        </Typography>
      </Box>
    );
  }

  // **🔹 5. 處理載入與錯誤**
  if (loading) return <Typography>Loading...</Typography>;
  if (error) return <Typography color="error">{error}</Typography>;

  // 打開審核的彈跳視窗
  const handleReviewOpen = (row) => {
    setSelectedRow({
      ...row,
      status: row.status || "待審核", // 確保審核狀態預設為「待審核」
      rejectionReason: row.rejectionReason || "", // 預設清空拒絕原因
    });
    setOpenDetailsDialog(true);
  };

  // 審核送出按鈕
  const handleReviewSubmit = async (row) => {
    // **當選擇「審核未通過」但未填寫拒絕原因時，顯示錯誤**
    if (
      selectedRow.status === "審核未通過" &&
      !selectedRow.rejectionReason.trim()
    ) {
      setRejectionError("請輸入拒絕原因");
      return; // 阻止送出
    }

    try {
      let apiUrl = "";
      let requestBody = {};

      // **決定 API 路徑**
      if (selectedRow.status === "審核通過") {
        apiUrl = `/punch/correction/${selectedRow.id}/approve`;
      } else if (selectedRow.status === "審核未通過") {
        apiUrl = `/punch/correction/${selectedRow.id}/reject`;
        requestBody.review_message = selectedRow.rejectionReason;
      }

      // **發送 API 更新補登打卡資料**
      const response = await API.put(apiUrl, requestBody);
      console.log("API 回應:", response.data);

      console.log(response);
      if (response.status === 200) {
        // **更新 rows 陣列**
        const updatedRows = rows.map((row) =>
          row.id === selectedRow.id
            ? {
              ...row,
              status: selectedRow.status,
              rejectionReason: selectedRow.rejectionReason,
            }
            : row
        );

        setRows(updatedRows); // 同步更新 rows 陣列
        setFilteredRows(updatedRows); // 同步更新顯示的資料
        setOpenDetailsDialog(false); // 關閉彈窗
        alert("審核結果已成功更新！");
      }
    } catch (error) {
      console.error("錯誤詳情:", error.response?.data || error.message);
      alert(error.response?.data?.message || "更新失敗，請稍後再試！");
    }
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
            gap: 2, // 設定元素之間的間距
            // RWD設定
            flexDirection: {
              xs: "column",
              md: "column",
              lg: "row",
            },
            alignItems: "center",
            justifyContent: "center",
          }}
        >
          <Box
            sx={{
              display: "flex",
              flexDirection: {
                xs: "column",
                sm: "row",
              },
              gap: 2,
            }}
          >
            <Box sx={{ display: "flex", gap: 2, alignItems: "center", width: "220px" }}>
              <Typography variant="body1">選擇部門</Typography>
              {/* 文字 */}
              {/* 部門輸入框 */}
              <TextField
                variant="outlined"
                size="small"
                value={selectedDepartment ?? ""}
                onChange={handleDepartmentChange}
                select
                fullWidth
                sx={{ backgroundColor: "white" }} // 白底，寬度限制
              >
                <MenuItem value="" disabled>
                  請選擇部門
                </MenuItem>
                {departments.length > 0 &&
                  departments.map((dept) => (
                    <MenuItem key={dept.id} value={dept.id}>
                      {dept.name}
                    </MenuItem>
                  ))}
              </TextField>
            </Box>
            <Box sx={{ display: "flex", gap: 2, alignItems: "center", width: "220px" }}>
              <Typography variant="body1">員工編號</Typography>
              {/* 文字 */}
              {/* 員工編號輸入框 */}
              <TextField
                variant="outlined"
                size="small"
                value={employeeId}
                onChange={(e) => setEmployeeId(e.target.value)}
                fullWidth
                sx={{ backgroundColor: "white" }}
              />
            </Box>
          </Box>

          <Box
            sx={{
              display: "flex",
              flexDirection: {
                xs: "column",
                sm: "row",
              },
              gap: 2,
              alignItems: "center",
            }}
          >
            <LocalizationProvider dateAdapter={AdapterDateFns}>
              <Typography variant="body1">選擇日期區間</Typography>
              {/* 起始日期 */}
              <DatePicker
                value={startDate}
                onChange={(newValue) => {
                  if (newValue) {
                    setStartDate(new Date(newValue)); // 確保 `startDate` 被正確更新
                  }
                }}
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
              <Typography variant="body1" sx={{ display: { xs: "none", sm: "block" } }}>~</Typography>

              {/* 結束日期 */}
              <DatePicker
                value={endDate}
                onChange={(newValue) => {
                  if (newValue) {
                    setEndDate(new Date(newValue));
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
            </LocalizationProvider>
          </Box>
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
          onClick={() => handleSearch(0, rowsPerPage, true)} // 點選後篩選日期
        >
          查詢
        </Button>

        {/* **表格顯示 API 取得的資料** */}
        <Paper
          sx={{
            height: "100%",
            width: "100%",
            overflow: "hidden", // 防止滾動條溢出
            borderRadius: "8px",
            mt: 2,
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
                        width:
                          column.id === "id"
                            ? 50
                            : [
                              "user_name",
                              "date",
                              "time",
                              "correction_type",
                              "created_at",
                              "status",
                            ].includes(column.id)
                              ? 150
                              : column.id === "actions"
                                ? 100
                                : "auto",
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
                  filteredRows.map((row) => (
                    <TableRow key={row.id} hover>
                      {columns.map((column) => {
                        let value = row[column.id];

                        return (
                          <TableCell
                            key={column.id}
                            align="center"
                            sx={{ minWidth: column.minWidth }}
                          >
                            {column.id === "actions" ? (
                              <Button
                                variant="contained"
                                sx={{
                                  backgroundColor: "#D2B48C",
                                  color: "white",
                                }}
                                onClick={() => handleReviewOpen(row)}
                                disabled={
                                  row.status === "審核通過" ||
                                  row.status === "審核未通過"
                                }
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
            count={totalRecords} // 總資料筆數
            rowsPerPage={rowsPerPage} // 當前每頁顯示筆數
            page={page} // 當前頁碼
            onPageChange={handleChangePage} // 換頁時觸發的函式
            onRowsPerPageChange={handleChangeRowsPerPage} // 改變每頁顯示筆數時觸發
            sx={{
              borderTop: "1px solid #ddd", // 增加分隔線
              backgroundColor: "#fff", // 確保背景與表格一致
            }}
          />
        </Paper>

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
                  select
                  value={selectedRow?.status || "待審核"}
                  onChange={(e) => {
                    setSelectedRow((prev) => ({
                      ...prev,
                      status: e.target.value,
                    }));
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
                    setSelectedRow((prev) => ({
                      ...prev,
                      rejectionReason: e.target.value,
                    }))
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
      </Paper >
    </Box >
  );
}

export default ApproveClockReissuePage;
