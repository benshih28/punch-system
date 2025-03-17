import { useState } from "react";
import { useForm } from "react-hook-form";
import {
  Button,
  Box,
  Typography,
  TextField,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Paper,
  MenuItem,
  Select,
  Modal,
  Pagination,
} from "@mui/material";
import { Search, TaskAltOutlined } from "@mui/icons-material";

function ApproveLeave() {
  // 控制彈窗開啟
  const [open, setOpen] = useState(false);
  // 存放選定的請假單
  const [selectedRequest, setSelectedRequest] = useState(null);

  const handleOpen = (request) => {
    setSelectedRequest(request);
    setOpen(true);
  };
  // 關閉彈窗
  const handleClose = () => setOpen(false);

  // 假單請求資料 (模擬)
  const leaveRequests = [
  { id: 1, applicant: "王小美", leaveType: "事假", reason: "家中有事", leaveDate: "2024/07/08 - 2024/07/09", leaveDays: "2天", applyDate: "2024/06/01", status: "主管審核中" },
  { id: 2, applicant: "王小明", leaveType: "事假", reason: "家中有事", leaveDate: "2024/07/08 - 2024/07/09", leaveDays: "1天", applyDate: "2024/06/01", status: "主管審核中" },
  { id: 3, applicant: "張大華", leaveType: "病假", reason: "發燒不適", leaveDate: "2024/07/10", leaveDays: "1天", applyDate: "2024/06/02", status: "已核准" },
  { id: 4, applicant: "李小龍", leaveType: "特休", reason: "旅遊計畫", leaveDate: "2024/08/01 - 2024/08/05", leaveDays: "5天", applyDate: "2024/06/05", status: "待審核" },
  { id: 5, applicant: "陳美麗", leaveType: "事假", reason: "家庭聚會", leaveDate: "2024/07/15", leaveDays: "1天", applyDate: "2024/06/07", status: "主管審核中" },
  { id: 6, applicant: "王志明", leaveType: "病假", reason: "感冒", leaveDate: "2024/07/18 - 2024/07/19", leaveDays: "2天", applyDate: "2024/06/10", status: "已核准" },
  { id: 7, applicant: "鄭小芳", leaveType: "特休", reason: "家人探親", leaveDate: "2024/07/22 - 2024/07/24", leaveDays: "3天", applyDate: "2024/06/12", status: "待審核" },
  { id: 8, applicant: "許大衛", leaveType: "病假", reason: "流感", leaveDate: "2024/07/25", leaveDays: "1天", applyDate: "2024/06/15", status: "主管審核中" },
  { id: 9, applicant: "郭小婷", leaveType: "事假", reason: "搬家", leaveDate: "2024/07/27 - 2024/07/28", leaveDays: "2天", applyDate: "2024/06/18", status: "已核准" },
  { id: 10, applicant: "周文彬", leaveType: "特休", reason: "度假", leaveDate: "2024/08/02 - 2024/08/06", leaveDays: "5天", applyDate: "2024/06/20", status: "待審核" },
  { id: 11, applicant: "周文彬", leaveType: "特休", reason: "度假", leaveDate: "2024/08/02 - 2024/08/06", leaveDays: "5天", applyDate: "2024/06/20", status: "待審核" },
  { id: 12, applicant: "周文彬", leaveType: "特休", reason: "度假", leaveDate: "2024/08/02 - 2024/08/06", leaveDays: "5天", applyDate: "2024/06/20", status: "待審核" },
  ];

  const onSubmit = (data) => {
    console.log("審核結果:", { ...selectedRequest, ...data });
    handleClose();
  };

  const {
    register,
    handleSubmit,
    watch,
    formState: { errors },
  } = useForm();

  // 監聽 Select (審核狀態)的值
  const status = watch("status", "待審核");

  // 分頁狀態
  const [page, setPage] = useState(1);
  const rowsPerPage = 10; // 每頁顯示幾筆 (可調整)
  const totalPages = Math.ceil(leaveRequests.length / rowsPerPage); // 計算總頁數

  const handleChange = (event, value) => setPage(value);
  const handleNext = () => page < totalPages && setPage(page + 1);
  const handleBack = () => page > 1 && setPage(page - 1);

  // 取得當前分頁的資料
  const currentRequests = leaveRequests.slice(
    (page - 1) * rowsPerPage,
    page * rowsPerPage
  );

  return (
    <Box sx={{ padding: "100px", textAlign: "center" }}>
      <Typography variant="h4" fontWeight="bold" mb={1}>
        假單審核
      </Typography>

      {/* 搜尋欄位 */}
      <Box
        sx={{
          backgroundColor: "#cfe2f3",
          padding: "25px",
          borderRadius: "12px",
          maxWidth: "1100px",
          width: "100%",
          margin: "auto",
          display: "flex",
          flexWrap: "wrap",
          gap: 2,
          alignItems: "center",
          justifyContent: "center",
        }}
      >
        {/* 選擇部門 */}
        <Box sx={{ display: "flex", alignItems: "center", gap: 1 }}>
          <Typography
            sx={{ fontWeight: "bold", fontSize: "14px", minWidth: "50px" }}
          >
            選擇部門
          </Typography>
          <Select
            displayEmpty
            value=""
            onChange={() => {}}
            sx={{
              width: 100,
              height: "35px",
              backgroundColor: "#fff",
              borderRadius: "8px",
              fontSize: "14px",
            }}
          >
            <MenuItem value="">請選擇</MenuItem>
          </Select>
        </Box>

        {/* 員工編號 */}
        <Box sx={{ display: "flex", alignItems: "center", gap: 1 }}>
          <Typography
            sx={{ fontWeight: "bold", fontSize: "14px", minWidth: "50px" }}
          >
            員工編號
          </Typography>
          <TextField
            placeholder="請輸入員工編號"
            sx={{
              width: 140,
              backgroundColor: "#fff",
              borderRadius: "8px",
              fontSize: "14px",
              "& .MuiInputBase-root": {
                height: "35px",
                fontSize: "14px",
              },
            }}
          />
        </Box>

        {/* 選擇審核狀態 */}
        <Box sx={{ display: "flex", alignItems: "center", gap: 1 }}>
          <Typography
            sx={{ fontWeight: "bold", fontSize: "14px", minWidth: "50px" }}
          >
            審核狀態
          </Typography>
          <Select
            displayEmpty
            value=""
            onChange={() => {}}
            sx={{
              width: 100,
              height: "35px",
              backgroundColor: "#fff",
              borderRadius: "8px",
              fontSize: "14px",
            }}
          >
            <MenuItem value="" disabled>請選擇</MenuItem>
            <MenuItem value="待審核">待審核</MenuItem>
            <MenuItem value="審核通過">審核通過</MenuItem>
            <MenuItem value="審核拒絕">審核拒絕</MenuItem>
          </Select>
        </Box>

        {/* 日期選擇 */}
        <Box
          sx={{
            display: "flex",
            alignItems: "center",
            flexWrap: "wrap",
            gap: 1,
          }}
        >
          <Typography
            sx={{ fontWeight: "bold", fontSize: "14px", minWidth: "50px" }}
          >
            選擇日期範圍
          </Typography>
          <Box sx={{ display: "flex", alignItems: "center", gap: 1 }}>
            <TextField
              type="date"
              placeholder="年/月/日"
              sx={{
                width: 140,
                backgroundColor: "#fff",
                borderRadius: "8px",
                fontSize: "14px",
                "& .MuiInputBase-root": {
                  height: "35px",
                  fontSize: "14px",
                },
              }}
            />
            <Typography sx={{ fontWeight: "bold", fontSize: "14px" }}>
              ~
            </Typography>
            <TextField
              type="date"
              placeholder="年/月/日"
              sx={{
                width: 140,
                backgroundColor: "#fff",
                borderRadius: "8px",
                fontSize: "14px",
                "& .MuiInputBase-root": {
                  height: "35px",
                  fontSize: "14px",
                },
              }}
            />
          </Box>
        </Box>
      </Box>

      {/* 查詢按鈕 */}
      <Button
        variant="contained"
        sx={{
          backgroundColor: "#A1887F",
          width: "200px",
          padding: "10px 25px",
          borderRadius: "30px",
          fontSize: "16px",
          marginTop: "30px",
          marginBottom: "30px", // 增加與下方表格的間距
          "&:hover": { backgroundColor: "#795548" },
        }}
        startIcon={<Search />}
      >
        查詢
      </Button>

      {/* 假單審核列表 */}
      <TableContainer
        component={Paper}
        sx={{
          borderRadius: "12px",
          boxShadow: 3,
          maxWidth: "1400px",
          margin: "auto",
        }}
      >
        <Table>
          <TableHead>
            <TableRow sx={{ backgroundColor: "#f0e6da" }}>
              <TableCell sx={{ fontWeight: "bold" }}>申請人</TableCell>
              <TableCell sx={{ fontWeight: "bold" }}>請假類型</TableCell>
              <TableCell sx={{ fontWeight: "bold" }}>請假原因</TableCell>
              <TableCell sx={{ fontWeight: "bold" }}>請假日期</TableCell>
              <TableCell sx={{ fontWeight: "bold" }}>請假天數</TableCell>
              <TableCell sx={{ fontWeight: "bold" }}>申請日期</TableCell>
              <TableCell sx={{ fontWeight: "bold" }}>申請狀態</TableCell>
              <TableCell sx={{ fontWeight: "bold" }}>操作</TableCell>
            </TableRow>
          </TableHead>
          <TableBody>
            {currentRequests.map((request) => (
              <TableRow key={request.id}>
                <TableCell>{request.applicant}</TableCell>
                <TableCell>{request.leaveType}</TableCell>
                <TableCell>{request.reason}</TableCell>
                <TableCell>{request.leaveDate}</TableCell>
                <TableCell>{request.leaveDays}</TableCell>
                <TableCell>{request.applyDate}</TableCell>
                <TableCell>{request.status}</TableCell>
                <TableCell>
                  <Button
                    variant="contained"
                    sx={{
                      backgroundColor: "#A1887F",
                      borderRadius: "12px",
                      fontSize: "14px",
                      padding: "5px 15px",
                      "&:hover": { backgroundColor: "#795548" },
                    }}
                    onClick={() => handleOpen(request)}
                  >
                    審核
                  </Button>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </TableContainer>
      {/* 分頁按鈕 */}
      <Box
        sx={{
          display: "flex",
          justifyContent: "center",
          alignItems: "center",
          gap: 2,
          mt: 3,
        }}
      >
        <Button
          onClick={handleBack}
          disabled={page === 1}
          sx={{
            backgroundColor: "#B0BEC5",
            "&:hover": { backgroundColor: "#78909C" },
          }}
        >
          上一頁
        </Button>
        <Pagination
          count={totalPages}
          page={page}
          onChange={handleChange}
          color="primary"
        />
        <Button
          onClick={handleNext}
          disabled={page === totalPages}
          sx={{
            backgroundColor: "#90CAF9",
            "&:hover": { backgroundColor: "#64B5F6" },
          }}
        >
          下一頁
        </Button>
      </Box>

      {/* 彈出視窗 */}
      <Modal open={open} onClose={handleClose}>
        <Box
          sx={{
            position: "absolute",
            top: "50%",
            left: "50%",
            transform: "translate(-50%, -50%)",
            width: "90%", // 讓彈窗在小螢幕時適應
            maxWidth: "600px", // 限制最大寬度
            bgcolor: "#cfe2f3",
            boxShadow: 24,
            p: 4,
            borderRadius: "12px",
            maxHeight: "80vh", // 設置最大高度
            overflowY: "auto", // 啟用垂直滾動
          }}
        >
          {selectedRequest && (
            <>
              <Typography
                variant="h6"
                sx={{ fontWeight: "bold", textAlign: "center", mb: 3 }}
              >
                假單審核
              </Typography>
              {/* 審核表單 */}
              <form onSubmit={handleSubmit(onSubmit)}>
                <Box
                  sx={{
                    borderRadius: "12px",
                    maxWidth: "100%",
                    margin: "auto",
                    display: "flex",
                    alignItems: "center",
                    justifyContent: "space-between",
                    gap: 2,
                    flexWrap: "wrap", // 讓內容可以在小螢幕換行
                  }}
                >
                  {/* 申請人輸入框 */}
                  <Box sx={{ width: { xs: "100%", sm: "48%" } }}>
                    <Typography fontSize={14}>申請人</Typography>
                    <TextField
                      value={selectedRequest.applicant}
                      sx={{
                        backgroundColor: "white",
                        mb: 2,
                        borderRadius: "8px",
                      }}
                      margin="dense"
                      disabled
                      fullWidth
                    />
                  </Box>
                  {/* 請假類型輸入框 */}
                  <Box sx={{ width: { xs: "100%", sm: "48%" } }}>
                    <Typography fontSize={14}>請假類型</Typography>
                    <TextField
                      value={selectedRequest.leaveType}
                      sx={{
                        backgroundColor: "white",
                        mb: 2,
                        borderRadius: "8px",
                      }}
                      margin="dense"
                      disabled
                      fullWidth
                    />
                  </Box>
                </Box>

                <Box>
                  <Typography fontSize={14}>請假日期</Typography>
                  <TextField
                    value={selectedRequest.leaveDate}
                    sx={{
                      backgroundColor: "white",
                      mb: 2,
                      borderRadius: "8px",
                    }}
                    margin="dense"
                    fullWidth
                    disabled
                  />
                </Box>

                <Box>
                  <Typography fontSize={14}>請假原因</Typography>
                  <TextField
                    value={selectedRequest.reason}
                    sx={{
                      backgroundColor: "white",
                      mb: 2,
                      borderRadius: "8px",
                    }}
                    margin="dense"
                    disabled
                    fullWidth
                  />
                </Box>

                <Box>
                  <Typography fontSize={14}>申請狀態</Typography>
                  <Select
                    defaultValue=""
                    {...register("status", { required: "請選擇審核狀態" })}
                    sx={{
                      backgroundColor: "white",
                      mb: 2,
                      borderRadius: "8px",
                      my: 1,
                    }}
                    displayEmpty
                    fullWidth
                  >
                    <MenuItem value="待審核">待審核</MenuItem>
                    <MenuItem value="審核通過">審核通過</MenuItem>
                    <MenuItem value="審核拒絕">審核拒絕</MenuItem>
                  </Select>
                  {errors.status && (
                    <Typography color="error">
                      {errors.status.message}
                    </Typography>
                  )}
                </Box>

                {/* 當 status 為 "審核拒絕" 才顯示 */}
                {status === "審核拒絕" && (
                  <Box>
                    <Typography fontSize={14}>拒絕原因 (若拒絕)</Typography>
                    <TextField
                      {...register("reason", { required: "請輸入拒絕原因" })}
                      fullWidth
                      multiline
                      maxRows={4}
                      sx={{
                        backgroundColor: "white",
                        mb: 2,
                        borderRadius: "8px",
                        wordWrap: "break-word",
                      }}
                      margin="dense"
                    />
                    {errors.reason && (
                      <Typography color="error">
                        {errors.reason.message}
                      </Typography>
                    )}
                  </Box>
                )}

                {/* 送出按鈕 */}
                <Box sx={{ display: "flex", justifyContent: "center", mt: 3 }}>
                  <Button
                    variant="contained"
                    type="submit"
                    sx={{
                      backgroundColor: "#A1887F",
                      width: "200px",
                      padding: "10px 25px",
                      borderRadius: "30px",
                      fontSize: "16px",
                      "&:hover": { backgroundColor: "#795548" },
                    }}
                    startIcon={<TaskAltOutlined />}
                  >
                    送出
                  </Button>
                </Box>
              </form>
            </>
          )}
        </Box>
      </Modal>
    </Box>
  );
}

export default ApproveLeave;
