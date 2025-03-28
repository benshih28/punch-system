import { useEffect, useState } from "react";
import { useForm, Controller } from "react-hook-form";
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
import api from "../api/axios";

function ApproveLeave() {
  // 存放資料處
  const [permissions, setPermissions] = useState(""); // 使用者權限
  const [employeeId, setEmployeeId] = useState(""); // 員工編號
  const [startDate, setStartDate] = useState(""); // 開始日期
  const [endDate, setEndDate] = useState(""); // 結束日期
  const [leaveRequests, setLeaveRequests] = useState([]); // 假單資料
  const [selectedDepartment, setSelectedDepartment] = useState(""); // 選定的部門 (下拉選單)
  const [department, setDepartment] = useState([]); // 取得部門列表
  const [leavestatus, setLeaveStatus] = useState(""); // 假單狀態列表
  const G_LEAVE_STATUS = {
    0: "待審核",
    1: "主管通過",
    2: "主管駁回",
    3: "人資通過",
    4: "人資駁回",
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 0:
        return "orange"; // 待審核
      case 1:
        return "green"; // 主管通過
      case 2:
        return "red"; // 主管駁回
      case 3:
        return "blue"; // 人資通過
      case 4:
        return "darkred"; // 人資駁回
      default:
        return "black";
    }
  };

  // **獲取假單列表**
  const fetchLeaveRequests = async () => {
    try {
      let endpoint = "";

      if (permissions === "HR") {
        endpoint = "/leave/company";
      } else if (permissions === "Manager") {
        endpoint = "/leave/department";
      } else {
        console.warn("沒有權限查詢請假紀錄");
        return;
      }

      // **構造搜尋條件**
      const params = {
        ...(selectedDepartment && { department_id: selectedDepartment }),
        ...(employeeId && { employee_id: employeeId }),
        ...(startDate && { start_date: startDate }),
        ...(endDate && { end_date: endDate }),
        ...(leavestatus !== "" &&
          leavestatus !== null && { status: leavestatus }), // 當假單狀態不為空時傳遞
        page, // ✅ 傳遞當前頁碼
      };

      // **發送 API 請求**
      const response = await api.get(endpoint, { params });
      // console.log("✅ API 回應請假資料:", response.data.records);

      setLeaveRequests(response.data?.records || []);
      // console.log("總頁數:", response.data);
      setTotalPages(Math.ceil(response.data.total / 10));
    } catch (error) {
      console.error("請假紀錄查詢失敗:", error);
      setLeaveRequests([]); // ✅ 清空請假資料
    }
  };

  const getDepartment = async () => {
    try {
      // **獲取部門**
      let departmentData = [];
      const DPSelectResponse = await api.get(`/departments`);

      // ✅ 確保 departmentData 是 [{ id, name }] 格式
      departmentData =
        DPSelectResponse.data?.departments?.map((dept) => ({
          id: dept.id,
          name: dept.name,
        })) || [];
      setDepartment(departmentData);
      // console.log("HR 部門列表:", departmentData);
    } catch (error) {
      console.error("初始化數據失敗:", error);
    }
  };
  // 取得使用者資料，並載入初始值資料
  useEffect(() => {
    // 取得用戶資料
    const userResponse = JSON.parse(localStorage.getItem("auth"));

    // 取得使用者權限(判斷身分)
    const permissionResponse =
      userResponse.roles_permissions?.permissions || [];
    // console.log("使用者權限:", permissionResponse);

    let userPermission = "";

    if (
      permissionResponse.includes("view_company_leave_records") &&
      permissionResponse.includes("view_department_leave_records")
    ) {
      userPermission = "HR"; // 具有公司級和部門級權限
      getDepartment(); // 取得部門
    } else if (permissionResponse.includes("view_department_leave_records")) {
      userPermission = "Manager"; // 只有部門權限
      // console.log(userResponse.user.department_name);
      setDepartment([userResponse.user.department_name]); // 存入 useState
    } else {
      userPermission = "Employee"; // 一般員工
    }

    // console.log("設定權限:", userPermission);
    setPermissions(userPermission); // ✅ 存入 useState

    // **設定當月時間範圍**
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1); // 當月第一天
    const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0); // 當月最後一天
    const formattedStartDate = firstDay.toISOString().split("T")[0];
    const formattedEndDate = lastDay.toISOString().split("T")[0];

    setStartDate(formattedStartDate);
    setEndDate(formattedEndDate);
  }, []);

  // useForm 初始化
  const {
    register,
    handleSubmit,
    watch,
    reset,
    control,
    formState: { errors },
  } = useForm({
    defaultValues: {
      status: "",
      reject_reason: "",
    },
  });

  const selectedStatus = watch("status"); // 監聽 select 選擇的值(出現拒絕原因欄位)

  // 控制彈窗開啟
  const [open, setOpen] = useState(false);
  // 存放選定的請假單
  const [selectedRequest, setSelectedRequest] = useState(null);

  // 開啟彈窗，傳入選定的請假單，並清空表單
  const handleOpen = (request) => {
    setSelectedRequest(request);
    setOpen(true);
    reset({ status: "", reject_reason: "" }); // 清空所有欄位
  };
  // 關閉彈窗
  const handleClose = () => setOpen(false);

  // 根據權限和狀態獲取對應的選項
  const getApprovalOptions = () => {
    const loginUserId = JSON.parse(localStorage.getItem("auth"))?.user?.id; // 取得登入用戶的 ID(HR假單)
    const isOwnRequest = selectedRequest?.user_id === loginUserId; // 判斷是否為自己的請假

    if (permissions === "HR") {
      if (selectedRequest?.status === 0 && isOwnRequest) {
        // HR 審自己部門（主管階段）
        return [
          <MenuItem key="1" value="1">
            審核通過（主管）
          </MenuItem>,
          <MenuItem key="2" value="2">
            審核未通過（主管）
          </MenuItem>,
        ];
      } else if (selectedRequest?.status === 1) {
        // HR 人資審核
        return [
          <MenuItem key="3" value="3">
            審核通過（人資）
          </MenuItem>,
          <MenuItem key="4" value="4">
            審核未通過（人資）
          </MenuItem>,
        ];
      }
    } else if (permissions === "Manager" && selectedRequest?.status === 0) {
      // 一般主管審核
      return [
        <MenuItem key="1" value="1">
          審核通過（主管）
        </MenuItem>,
        <MenuItem key="2" value="2">
          審核未通過（主管）
        </MenuItem>,
      ];
    }

    return null;
  };

  const onSubmit = async (data) => {
    const { status, reject_reason } = data;

    // apiRoutes這是一個對應關系
    const apiRoutes = {
      HR: {
        1: `/leave/${selectedRequest.leave_id}/department/approve`,
        2: `/leave/${selectedRequest.leave_id}/department/reject`,
        3: `/leave/${selectedRequest.leave_id}/approve`,
        4: `/leave/${selectedRequest.leave_id}/reject`,
      },
      Manager: {
        1: `/leave/${selectedRequest.leave_id}/department/approve`,
        2: `/leave/${selectedRequest.leave_id}/department/reject`,
      },
    };
    // 根據權限和狀態獲取對應的API路徑
    const apiRoute = apiRoutes[permissions]?.[status];

    if (!apiRoute) {
      alert("發生錯誤，請確認權限與狀態");
      return;
    }

    try {
      const response = await api.patch(apiRoute, {
        id: selectedRequest.leave_id,
        status,
        reject_reason: reject_reason || "",
      });

      alert(response.data.message);
      setOpen(false);
      fetchLeaveRequests();
    } catch (error) {
      console.error("審核假單失敗:", error);
    }
  };

  // 分頁狀態
  const [page, setPage] = useState(1); // 預設為第 1 頁
  const [totalPages, setTotalPages] = useState(1);
  // const [loading, setLoading] = useState(false); // ✅ 是否正在加載

  useEffect(() => {
    if (permissions === "HR" || permissions === "Manager") {
      fetchLeaveRequests();
    }
  }, [page]); // 當頁碼或權限改變時重新獲取假單

  const handleChange = (event, value) => setPage(value); // 切換頁碼
  const handleNext = () => page < totalPages && setPage(page + 1); // 下一頁
  const handleBack = () => page > 1 && setPage(page - 1); // 上一頁

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
          {department.length > 1 ? (
            <Select
              displayEmpty
              value={selectedDepartment}
              onChange={(e) => setSelectedDepartment(e.target.value)}
              sx={{
                width: 100,
                height: "35px",
                backgroundColor: "#fff",
                borderRadius: "8px",
                fontSize: "14px",
              }}
            >
              <MenuItem value="">全部部門</MenuItem>
              {/* 動態渲染部門列表 */}
              {department.map((dp) => (
                <MenuItem key={dp.id} value={dp.id}>
                  {dp.name}
                </MenuItem>
              ))}
            </Select>
          ) : (
            <TextField
              variant="outlined"
              value={department[0] ?? ""} // 如果部門列表為空，顯示空字串
              sx={{
                width: 100,
                height: "35px", // ✅ 確保與 Select 一致
                backgroundColor: "#fff",
                borderRadius: "8px", // ✅ 確保邊角一致
                fontSize: "14px",
                "& .MuiOutlinedInput-root": {
                  height: "35px", // ✅ 內部對齊
                  "& fieldset": {
                    borderRadius: "8px", // ✅ 讓外框一致
                  },
                },
              }}
              disabled
            />
          )}
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
            value={employeeId}
            onChange={(e) => setEmployeeId(e.target.value)}
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
            value={leavestatus ?? ""}
            onChange={(e) => setLeaveStatus(e.target.value)} // ✅ 更新 state
            sx={{
              width: 100,
              height: "35px",
              backgroundColor: "#fff",
              borderRadius: "8px",
              fontSize: "14px",
            }}
          >
            <MenuItem value="">全部狀態</MenuItem>
            {/* 使用 G_LEAVE_STATUS 來動態渲染選項 */}
            {Object.entries(G_LEAVE_STATUS).map(([key, value]) => (
              <MenuItem key={key} value={Number(key)}>
                {value}
              </MenuItem>
            ))}
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
              value={startDate}
              onChange={(e) => setStartDate(e.target.value)}
              sx={{
                width: 140,
                backgroundColor: "#fff",
                borderRadius: "8px",
                fontSize: "14px",
                "& .MuiInputBase-root": { height: "35px", fontSize: "14px" },
              }}
            />
            <Typography sx={{ fontWeight: "bold", fontSize: "14px" }}>
              ~
            </Typography>
            <TextField
              type="date"
              placeholder="年/月/日"
              value={endDate}
              onChange={(e) => setEndDate(e.target.value)}
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
        onClick={() => {
          fetchLeaveRequests();
          setPage(1);
        }} // 只有按下搜尋按鈕才查詢
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
              <TableCell sx={{ fontWeight: "bold" }}>申請日期</TableCell>
              <TableCell sx={{ fontWeight: "bold" }}>申請狀態</TableCell>
              <TableCell sx={{ fontWeight: "bold" }}>操作</TableCell>
            </TableRow>
          </TableHead>
          <TableBody>
            {leaveRequests.length > 0 ? (
              leaveRequests.map((request) => (
                <TableRow key={request.leave_id}>
                  <TableCell>{request.user_name}</TableCell>
                  <TableCell>{request.leave_type_name}</TableCell>
                  <TableCell>{request.reason}</TableCell>
                  <TableCell>
                    {" "}
                    {new Date(request.start_time).toLocaleDateString("zh-TW", {
                      year: "numeric",
                      month: "2-digit",
                      day: "2-digit",
                    })}{" "}
                    -{" "}
                    {new Date(request.end_time).toLocaleDateString("zh-TW", {
                      year: "numeric",
                      month: "2-digit",
                      day: "2-digit",
                    })}
                  </TableCell>
                  <TableCell>
                    {" "}
                    {new Date(request.created_at).toLocaleDateString("zh-TW", {
                      year: "numeric",
                      month: "2-digit",
                      day: "2-digit",
                    })}
                  </TableCell>
                  <TableCell sx={{ color: getStatusColor(request.status) }}>
                    {G_LEAVE_STATUS[request.status]}
                  </TableCell>
                  <TableCell>
                    {/* HR 人資審核 (status == 1) */}
                    {permissions === "HR" && request.status === 1 ? (
                      <Button
                        variant="contained"
                        onClick={() => handleOpen(request)}
                        sx={{
                          backgroundColor: "#A1887F",
                          color: "#fff",
                        }}
                      >
                        人資審核
                      </Button>
                    ) : permissions === "HR" &&
                      request.status === 0 &&
                      request.user_id ===
                        JSON.parse(localStorage.getItem("auth"))?.user?.id ? (
                      // HR 身份 & 是自己的假單 & 主管審核階段
                      <Button
                        variant="contained"
                        onClick={() => handleOpen(request)}
                        sx={{
                          backgroundColor: "#A1887F",
                          color: "#fff",
                        }}
                      >
                        主管審核
                      </Button>
                    ) : permissions === "Manager" && request.status === 0 ? (
                      // 一般主管審核
                      <Button
                        variant="contained"
                        onClick={() => handleOpen(request)}
                        sx={{
                          backgroundColor: "#A1887F",
                          color: "#fff",
                        }}
                      >
                        主管審核
                      </Button>
                    ) : null}
                  </TableCell>
                </TableRow>
              ))
            ) : (
              <TableRow>
                <TableCell align="center" colSpan={7}>
                  查無資料
                </TableCell>
              </TableRow>
            )}
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
                      value={selectedRequest.user_name}
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
                      value={selectedRequest.leave_type_name}
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
                    value={`${new Date(
                      selectedRequest.start_time
                    ).toLocaleDateString("zh-TW", {
                      year: "numeric",
                      month: "2-digit",
                      day: "2-digit",
                    })} - ${new Date(
                      selectedRequest.end_time
                    ).toLocaleDateString("zh-TW", {
                      year: "numeric",
                      month: "2-digit",
                      day: "2-digit",
                    })}`}
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

                {/* 請假附件的欄位，有值傳入時才出現 */}
                {selectedRequest.attachment && (
                  <Box>
                    <Typography fontSize={14}>請假附件</Typography>
                    <Box
                      sx={{
                        backgroundColor: "white",
                        mb: 2,
                        borderRadius: "8px",
                        p: 1.5,
                        wordBreak: "break-all", // 防止長連結破版
                      }}
                    >
                      <a
                        href={selectedRequest.attachment}
                        target="_blank"
                        rel="noopener noreferrer"
                        style={{ textDecoration: "none", color: "#1976d2" }} // 可根據你的主題改變顏色
                      >
                        {selectedRequest.attachment}
                      </a>
                    </Box>
                  </Box>
                )}

                <Box>
                  <Typography fontSize={14}>申請狀態</Typography>
                  <Controller
                    name="status"
                    control={control}
                    rules={{ required: "請選擇審核狀態" }}
                    render={({ field }) => (
                      <Select
                        {...field}
                        displayEmpty
                        fullWidth
                        sx={{
                          backgroundColor: "white",
                          mb: 2,
                          borderRadius: "8px",
                          my: 1,
                        }}
                      >
                        <MenuItem value="" disabled>
                          請選擇
                        </MenuItem>
                        {getApprovalOptions()}
                      </Select>
                    )}
                  />
                  {errors.status && (
                    <Typography color="error">
                      {errors.status.message}
                    </Typography>
                  )}
                </Box>

                {/* 如果選擇拒絕，則顯示「拒絕原因」輸入框 */}
                {(selectedStatus === "2" || selectedStatus === "4") && (
                  <Box>
                    <Typography fontSize={14}>拒絕原因</Typography>
                    <TextField
                      {...register("reject_reason", {
                        required: "請輸入拒絕原因",
                      })}
                      multiline
                      maxRows={4}
                      fullWidth
                      sx={{
                        backgroundColor: "white",
                        mb: 2,
                        borderRadius: "8px",
                        wordWrap: "break-word",
                      }}
                      margin="dense"
                    />
                    {errors.reject_reason && (
                      <Typography color="error">
                        {errors.reject_reason.message}
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
