import { useState, useEffect, useRef } from "react";
import { useForm, Controller } from "react-hook-form";
import dayjs from "dayjs";
import API from "../api/axios";
import LeavePolicy from "../components/LeavePolicy";
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
  FormControl,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Link
} from "@mui/material";
import { Search } from "@mui/icons-material";
import { LocalizationProvider, DateTimePicker } from "@mui/x-date-pickers";
import { AdapterDayjs } from '@mui/x-date-pickers/AdapterDayjs';

function ApproveLeave() {
  const [leaveRequests, setLeaveRequests] = useState([]); // 假單資料
  const [permissions, setPermissions] = useState([]);     // 存使用者的權限
  const [startDate, setStartDate] = useState(dayjs());
  const [endDate, setEndDate] = useState(dayjs());
  const [attachmentFile, setAttachmentFile] = useState(null);     // 附件
  const [selectedRequest, setSelectedRequest] = useState(null);   // 目前選中的請假單
  const [searchLeaveTypeOptions, setSearchLeaveTypeOptions] = useState([]); // 搜尋欄位的所有假別（包含「全部假別」）
  const [selectedLeaveType, setSelectedLeaveType] = useState("");           // 搜尋欄"選中"的假別 (查詢 API 帶的參數)
  const [formLeaveTypeOptions, setFormLeaveTypeOptions] = useState([]);     // 彈窗內的所有假別（不含「全部」）
  const leaveTypesWithLimit = [4, 5, 6, 7, 8, 9, 10];   // 可限制查詢剩餘時數的假別 ID（例如：生理假、特休等）
  const [leaveHours, setLeaveHours] = useState(null);   // 剩餘時數
  const [totalPages, setTotalPages] = useState(1);      // 總頁數
  const [dialogOpen, setDialogOpen] = useState(false);          // 控制 Dialog 開關
  const [dialogMessage, setDialogMessage] = useState("");        // Dialog 內容
  const [dialogSuccess, setDialogSuccess] = useState(true);      // 是成功 or 失敗
  const [status, setStatus] = useState("");  // 選中的狀態
  const statusMap = {       // 審核狀態
    0: "待審核",
    1: "主管通過",
    2: "主管駁回",
    3: "人資通過",
    4: "人資駁回",
  };

  // 請假列表Title
  const columns = [
    { id: "applicant", label: "申請人" },
    { id: "leaveTypes", label: "請假類型" },
    { id: "reason", label: "請假原因" },
    { id: "date", label: "請假日期" },
    { id: "days", label: "請假天數" },
    { id: "applyDay", label: "申請日期" },
    { id: "applyStatus", label: "申請狀態" },
    { id: "action", label: "操作" },
  ]

  // 設定當月時間範圍
  useEffect(() => {
    const start = dayjs().startOf("month"); // 本月第一天
    const end = dayjs().endOf("month");     // 本月最後一秒
    setStartDate(start);
    setEndDate(end);
  }, []);

  // 取得使用者權限
  useEffect(() => {
    const authData = localStorage.getItem("auth");
    const parsedAuth = authData ? JSON.parse(authData) : null;
    const rolesPermissions = parsedAuth?.roles_permissions;

    if (rolesPermissions?.permissions) {
      setPermissions(rolesPermissions.permissions);
    }
  }, []);

  // 獲取假別
  useEffect(() => {
    const fetchLeaveTypes = async () => {
      const cachedData = sessionStorage.getItem("leaveTypes");

      if (cachedData) {
        const parsed = JSON.parse(cachedData);
        const allTypes = parsed.filter((item) => item.id !== ""); // 除掉「全部假別」      
        setSearchLeaveTypeOptions(parsed);
        setFormLeaveTypeOptions(allTypes);
        return;
      }

      try {
        const res = await API.get("/leavetypes");
        const allTypes = res.data.map((item) => ({
          id: item.id,
          description: item.description,
        }));

        const searchOptions = [{ id: "", description: "全部假別" }, ...allTypes];
        sessionStorage.setItem("leaveTypes", JSON.stringify(searchOptions));

        setSearchLeaveTypeOptions(searchOptions);
        setFormLeaveTypeOptions(allTypes);
      } catch (error) {
        console.error("❌ 取得 leave types 失敗", error);
      }
    };
    fetchLeaveTypes();
  }, []);

  const [page, setPage] = useState(1);   // 分頁狀態
  const pageSize = 10;

  // 獲取請假列表
  const fetchLeaveRequests = async () => {
    if (!permissions.includes("view_leave_records")) {
      setLeaveRequests([]);
      return;
    }

    try {
      const apiRoute = "/leave/my-records";
      const params = {
        ...(selectedLeaveType && selectedLeaveType !== "所有假別" && { leave_type: selectedLeaveType }),
        ...(status !== "" && status !== "全部狀態" ? { status } : {}),
        ...(startDate && { start_date: dayjs(startDate).format("YYYY-MM-DD"), }),
        ...(endDate && { end_date: dayjs(endDate).format("YYYY-MM-DD"), }),
        page,
      };

      const res = await API.get(apiRoute, { params });
      // console.log("請假紀錄：", res.data);
      setLeaveRequests(res.data?.records || []);
      setTotalPages(Math.ceil((res.data?.total || 0) / pageSize));
    } catch (error) {
      // console.error("取得請假資料失敗", error);
      setLeaveRequests([]);
      setTotalPages(1); // 失敗時也要歸 1，避免卡住
    }
  };
  useEffect(() => {
    if (permissions.length) {
      fetchLeaveRequests();
    }
  }, [permissions, page]);

  // 初始化 react-hook-form (表單管理)
  const {
    handleSubmit,   // 表單送出，處理驗證
    reset,          // 重置表單
    register,       // 綁定欄位給 Hook Form 管理
    control,
    setValue,
    watch,
    formState: { errors },
  } = useForm();

  const [open, setOpen] = useState(false); // 彈窗開啟
  const [mode, setMode] = useState("create"); // 彈窗可為 'create' | 'edit' | 'view'
  const [currentLeaveId, setCurrentLeaveId] = useState(null);
  const watchedStartTime = watch("startTime");
  const watchedEndTime = watch("endTime");
  const hasInitializedRef = useRef(false);
  const [policyOpen, setPolicyOpen] = useState(false);

  // 🧼 統一初始化表單（根據 mode 決定）
  const initForm = (request, openMode) => {
    if (openMode === "create") {
      const defaultStart = dayjs();
      const defaultEnd = dayjs().add(1, "hour");
      const typeId = "";

      setLeaveHours(null);
      reset({
        startTime: defaultStart,
        endTime: defaultEnd,
        leave_type_id: typeId,
        status: "",
        reject_reason: "",
        reason: "",
      });

      // 🪄 預設請假類型查詢剩餘時數（目前應該不會觸發）
      if (leaveTypesWithLimit.includes(Number(typeId))) {
        fetchRemainingLeaveHours(typeId, defaultStart);
      }

    } else if ((openMode === "edit" || openMode === "view") && request) {
      const start = dayjs(request.start_time);
      const end = dayjs(request.end_time);
      const typeId = request.leave_type_id ?? "";

      setLeaveHours(null);
      reset({
        startTime: start,
        endTime: end,
        leave_type_id: typeId,
        status: request.status ?? "",
        reject_reason: request.reject_reason ?? "",
        reason: request.reason ?? "",
      });

      // 🪄 如果是編輯模式就查詢剩餘時數
      if (openMode !== "view" && leaveTypesWithLimit.includes(Number(typeId))) {
        fetchRemainingLeaveHours(typeId, start);
      }
    }
  };

  // 開啟彈窗
  const handleOpen = (request = null, openMode = "create") => {
    // console.log("🧾 handleOpen 傳入的 request：", request);
    setSelectedRequest(request);
    setCurrentLeaveId(request?.leave_id ?? null);
    setMode(openMode);
    setOpen(true);

    if (formLeaveTypeOptions.length) {   // 在打開彈窗時直接初始化表單
      initForm(request, openMode);
    }
  };
  useEffect(() => {
    if (open) {
      if (formLeaveTypeOptions.length && !hasInitializedRef.current) {
        initForm(selectedRequest, mode);
        hasInitializedRef.current = true;
      }
    } else {
      hasInitializedRef.current = false; // 關閉時重設
    }
  }, [open, formLeaveTypeOptions, selectedRequest, mode]);

  // 關閉彈窗
  const handleClose = () => setOpen(false);

  // 切換請假類型時，自動查詢特殊假別剩餘時數
  const fetchRemainingLeaveHours = async (
    leaveTypeId,
    dateFromForm = watch("startTime") // ✅ 直接呼叫 watch 時值是最新的
  ) => {
    const typeId = Number(leaveTypeId);
    const dateObj = dayjs(dateFromForm);


    if (!leaveTypesWithLimit.includes(typeId) || !dateObj.isValid()) {
      setLeaveHours(null);
      return;
    }

    try {
      const res = await API.get(`/leavetypes/hours/${typeId}`, {
        params: {
          start_time: dateObj.format("YYYY-MM-DD HH:mm"),
          exclude_id: selectedRequest?.leave_id ?? null,
        },
      });
      setLeaveHours(res.data?.remaining_hours ?? null);
      // console.log("✅ 剩餘請假時數查詢成功", res.data);
    } catch (error) {
      // console.error("❌ 查詢失敗", error);
      setLeaveHours(null);
    }
  };

  const watchedLeaveTypeId = watch("leave_type_id");
  useEffect(() => {
    const typeId = Number(watchedLeaveTypeId);
    if (typeId && leaveTypesWithLimit.includes(typeId) && watchedStartTime) {
      fetchRemainingLeaveHours(typeId, watchedStartTime);
    }
  }, [watchedStartTime, watchedLeaveTypeId]);


  // 送出請假申請 | 請假修改
  const handleLeaveSubmit = async (mode, leaveData, leaveId = null) => {
    const permissionMap = {
      create: "request_leave",
      edit: "update_leave",
    };

    if (!permissions.includes(permissionMap[mode])) {
      return console.warn("⚠️ 權限不足");
    }

    if (mode === "edit" && !leaveId) return console.warn("⚠️ 編輯模式下缺少 leaveId！");

    const routeMap = {
      create: "/leave/request",
      edit: `/leave/update/${leaveId}`,
    };

    try {
      const res = await API.post(routeMap[mode], leaveData, {
        headers: {
          "Content-Type": "multipart/form-data",
        },
      });
      // console.log(`📌 ${mode === "create" ? "申請" : "修改"}成功`, res.data);
      setDialogMessage("假單已成功送出，請靜候審核～");
      setDialogSuccess(true);
      setDialogOpen(true);
      const leaveTypeId = Number(leaveData.get("leave_type_id"));
      const leaveStartTime = dayjs(leaveData.get("start_time"));
      fetchRemainingLeaveHours(leaveTypeId, leaveStartTime);     // 正確查詢剩餘時數
      fetchLeaveRequests(); // 更新列表
      setPage(1);
      setDialogOpen(true);
    } catch (error) {
      // console.error(`❌ ${mode === "create" ? "申請" : "修改"}失敗`, error);
      const errorMsg =
        error.response?.data?.message ||
        "申請失敗，請檢查輸入資訊是否有誤。";
      setDialogMessage(errorMsg);
      setDialogSuccess(false);
      setDialogOpen(true);
    }
  };

  // 表單送出
  const onSubmit = (formValues) => {
    if (mode === "view") return;

    if (leaveHours !== null && leaveHours <= 0) {
      alert("⛔ 請假時間區間無效，請重新選擇有效的請假時段");
      return;
    }

    const leaveData = new FormData();
    leaveData.append("start_time", dayjs(watchedStartTime).format("YYYY-MM-DD HH:mm"));
    leaveData.append("end_time", dayjs(watchedEndTime).format("YYYY-MM-DD HH:mm"));
    leaveData.append("leave_type_id", formValues.leave_type_id);
    leaveData.append("reason", formValues.reason || selectedRequest?.reason || "");

    if (attachmentFile instanceof File) {
      leaveData.append("attachment", attachmentFile);
    }

    const leaveId = currentLeaveId;
    handleLeaveSubmit(mode, leaveData, leaveId);
    handleClose();
  };

  // 切換分頁
  const handleChange = (event, value) => setPage(value);
  const handleNext = () => page < totalPages && setPage(page + 1);  // 下一頁
  const handleBack = () => page > 1 && setPage(page - 1);           // 上一頁


  return (
    <Box sx={{ padding: "100px", textAlign: "center" }}>
      <Typography variant="h4" fontWeight="bold" mb={1}>
        查詢個人請假紀錄
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
          gap: 4,
          alignItems: "center",
          justifyContent: "space-between", // 讓間距自然分散
        }}
      >
        {/* 請假類型 */}
        <Box sx={{ flex: 1, minWidth: 130, display: "flex", alignItems: "center", gap: 2 }}>
          <Typography sx={{ fontWeight: "bold", fontSize: "14px", minWidth: "60px" }}>
            請假類型
          </Typography>
          <Select
            value={selectedLeaveType || ""}
            onChange={(e) => setSelectedLeaveType(e.target.value)}
            displayEmpty
            sx={{
              flex: 1,
              height: "35px",
              backgroundColor: "#fff",
              borderRadius: "8px",
              fontSize: "14px",
            }}
          >
            {searchLeaveTypeOptions.map((item) => (
              <MenuItem key={item.id} value={item.id}>
                {item.description}
              </MenuItem>
            ))}
          </Select>
        </Box>

        {/* 審核狀態 */}
        <Box sx={{ flex: 1, minWidth: 130, display: "flex", alignItems: "center", gap: 1 }}>
          <Typography sx={{ fontWeight: "bold", fontSize: "14px", minWidth: "60px" }}>
            審核狀態
          </Typography>
          <Select
            value={status ?? ""}
            onChange={(e) => setStatus(e.target.value)}
            displayEmpty
            sx={{
              flex: 1,
              height: "35px",
              backgroundColor: "#fff",
              borderRadius: "8px",
              fontSize: "14px",
            }}
          >
            <MenuItem value="">全部狀態</MenuItem>
            {Object.entries(statusMap).map(([key, value]) => (
              <MenuItem key={key} value={Number(key)}>
                {value}
              </MenuItem>
            ))}
          </Select>
        </Box>

        {/* 開始日期 */}
        <Box
          sx={{
            flex: 1,
            minWidth: 550,
            display: "flex",
            alignItems: "center",
            gap: 1,
          }}
        >
          <Typography sx={{ fontWeight: "bold", fontSize: "14px", whiteSpace: "nowrap" }}>
            選擇日期範圍
          </Typography>

          {/* 日期輸入區塊 */}
          <Box sx={{ display: "flex", alignItems: "center", gap: 1 }}>
            <TextField
              type="date"
              value={dayjs(startDate).format("YYYY-MM-DD")}
              onChange={(e) => setStartDate(dayjs(e.target.value))}
              sx={{
                width: 190,
                backgroundColor: "#fff",
                borderRadius: "8px",
                fontSize: "14px",
                "& .MuiInputBase-root": {
                  height: "35px",
                  fontSize: "14px",
                },
              }}
            />

            <Typography sx={{ fontWeight: "bold", fontSize: "14px" }}>~</Typography>

            <TextField
              type="date"
              value={dayjs(endDate).format("YYYY-MM-DD")}
              onChange={(e) => setEndDate(dayjs(e.target.value))}
              sx={{
                width: 190,
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
          setPage(1);              // 先把頁碼設回第一頁
          fetchLeaveRequests();    // 再呼叫查詢 API
        }}   // 這裡綁儲存篩選資料後的變數(searchFilters)
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

      {/* 假單列表 */}
      <TableContainer
        component={Paper}
        sx={{
          borderRadius: "12px",
          boxShadow: 3,
          maxWidth: "1300px",
          margin: "auto",
        }}
      >
        {/* 標題列 */}
        <Table>
          <TableHead>
            <TableRow sx={{ backgroundColor: "#f0e6da" }}>
              {columns.map((column) => (
                <TableCell
                  key={column.id}
                  sx={{
                    maxWidth: column.maxWidth,
                    margin: column.margin,
                    orderRadius: column.orderRadius,
                    boxShadow: column.boxShadow,
                    fontWeight: "bold",
                    textAlign: "center",
                  }}
                >
                  {column.label}
                </TableCell>
              ))}
            </TableRow>
          </TableHead>

          {/* 假單內容 */}
          <TableBody>
            {leaveRequests.length > 0 ? (
              leaveRequests.map((request) => {
                const totalHour = request.leave_hours ?? 8;
                const fullDays = Math.floor(totalHour / 8);
                const remainingHours = totalHour % 8;
                let days = "";
                if (fullDays > 0) days += `${fullDays} 天`;
                if (remainingHours > 0) days += `${fullDays > 0 ? ' ' : ''}${remainingHours} 小時`;
                if (!days) days = "0 小時";
                const applyDate = request.created_at?.split("T")[0] ?? "-";  // 申請日期
                return (
                  <TableRow key={request.leave_id}>
                    <TableCell>
                      <Box sx={{ ml: 3 }}>{request.user_name}</Box>
                    </TableCell>
                    <TableCell>
                      <Box sx={{ ml: 3.5 }}>{request.leave_type_name}</Box>
                    </TableCell>
                    <TableCell>
                      <Box sx={{ ml: 3.5 }}>{request.reason}</Box>
                    </TableCell>
                    <TableCell>
                      <Box sx={{ ml: 10 }}>
                        {request.start_time.split(":").slice(0, 2).join(":")} -{" "}
                        {request.end_time.split(":").slice(0, 2).join(":")}
                      </Box>
                    </TableCell>
                    <TableCell>
                      <Box sx={{ ml: 3.5 }}>{days}</Box>
                    </TableCell>
                    <TableCell>
                      <Box sx={{ ml: 3.5 }}>{applyDate}</Box>
                    </TableCell>
                    <TableCell>
                      <Box sx={{ ml: 3 }}>{statusMap[request.status]}</Box>
                    </TableCell>
                    <TableCell>
                      {/* 編輯/查詢按鈕 */}
                      <Box sx={{ ml: 3.5 }}>
                        {/* 編輯按鈕（僅待審核可見） */}
                        {request.status === 0 && (
                          <Button
                            variant="contained"
                            sx={{
                              backgroundColor: "#A1887F",
                              color: "#fff",
                              borderRadius: "12px",
                              fontSize: "14px",
                              padding: "5px 15px",
                              "&:hover": { backgroundColor: "#795548" },
                            }}
                            onClick={() => handleOpen(request, "edit")}
                          >
                            編輯
                          </Button>
                        )}

                        {/* 查詢按鈕（其他狀態） */}
                        {[1, 2, 3, 4].includes(request.status) && (
                          <Button
                            variant="outlined"
                            sx={{
                              backgroundColor: "#fff",
                              color: "#A1887F",
                              borderColor: "#A1887F",
                              borderRadius: "12px",
                              fontSize: "14px",
                              padding: "5px 15px",
                              "&:hover": {
                                backgroundColor: "#F5F5F5",
                                borderColor: "#795548",
                                color: "#795548",
                              },
                            }}
                            onClick={() => handleOpen(request, "view")}
                          >
                            查詢
                          </Button>
                        )}
                      </Box>
                    </TableCell>
                  </TableRow>
                );
              })
            ) : (
              <TableRow>
                <TableCell align="center" colSpan={columns.length}>
                  查無資料
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </TableContainer>


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

      <Box
        sx={{
          position: "fixed",
          bottom: 30,
          right: 30,
          zIndex: 1000,
        }}
      >
        <Button
          variant="contained"
          color="primary"
          sx={{
            width: 65,
            height: 65,
            borderRadius: "50%",
            fontSize: 30,
            minWidth: "unset",
            boxShadow: 3,
            marginBottom: 3,
            "&:hover": { backgroundColor: "#1976d2" },
          }}
          onClick={() => handleOpen(null, "create")}
        >
          +
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
          {(mode === "create" || selectedRequest) && (
            <LocalizationProvider dateAdapter={AdapterDayjs}>
              <>
                <Typography
                  variant="h4"
                  sx={{ fontWeight: "bold", textAlign: "center", mb: 3 }}
                >
                  {mode === "create" && "請假申請"}
                  {mode === "edit" && "編輯假單"}
                  {mode === "view" && "查詢假單"}
                </Typography>

                {/* 請假申請彈出框 */}
                <form onSubmit={handleSubmit(onSubmit)}>
                  <Box
                    sx={{
                      backgroundColor: "white",
                      borderRadius: "12px",
                      maxWidth: "100%",
                      margin: "auto",
                      display: "flex",
                      flexDirection: "column",
                      boxShadow: "0 4px 20px rgba(0, 0, 0, 0.1)",
                      padding: 6,
                      gap: 3,
                      ...(mode === "view" && {
                        backgroundColor: "#fefefe", // 或可用 white
                        boxShadow: "0 4px 20px rgba(0, 0, 0, 0.15)",
                        // padding: 3,
                      }),
                    }}
                  >
                    {/* 第一排：請假日期 */}
                    <Box
                      sx={{
                        display: "flex",
                        gap: 2,
                        flexWrap: "wrap",
                      }}
                    >
                      {/* 開始時間 */}
                      <Box sx={{ flex: 1, minWidth: "150px" }}>
                        <Typography fontSize={14}>開始時間</Typography>
                        <Controller
                          name="startTime"
                          control={control}
                          rules={{ required: "請選擇開始時間" }}
                          defaultValue={null}
                          render={({ field, fieldState }) => (
                            <DateTimePicker
                              {...field}
                              disabled={mode === "view"}
                              value={field.value}
                              onChange={(newValue) => {
                                field.onChange(newValue);

                                const typeId = Number(watch("leave_type_id"));
                                if (typeId && leaveTypesWithLimit.includes(typeId)) {
                                  fetchRemainingLeaveHours(typeId, newValue);
                                }
                              }}
                              format="YYYY-MM-DD HH:mm"
                              slotProps={{
                                textField: {
                                  fullWidth: true,
                                  error: !!fieldState.error,
                                  helperText: fieldState.error?.message,
                                  size: "small",
                                  sx: {
                                    backgroundColor: "white",
                                    borderRadius: "8px",
                                  },
                                },
                              }}
                            />
                          )}
                        />
                      </Box>

                      {/* 結束時間 */}
                      <Box sx={{ flex: 1, minWidth: "150px" }}>
                        <Typography fontSize={14}>結束時間</Typography>
                        <Controller
                          name="endTime"
                          control={control}
                          defaultValue={null}
                          rules={{
                            required: "請選擇結束時間",
                            validate: (value) => {
                              if (!watch("startTime")) return true; // 避免 startTime 還沒選時報錯
                              return dayjs(value).isAfter(watch("startTime")) || "結束時間需晚於開始時間";
                            },
                          }}
                          render={({ field, fieldState }) => (
                            <DateTimePicker
                              {...field}
                              disabled={mode === "view"}
                              value={field.value}
                              onChange={(newValue) => {
                                field.onChange(newValue);
                              }}
                              format="YYYY-MM-DD HH:mm"
                              slotProps={{
                                textField: {
                                  fullWidth: true,
                                  error: !!fieldState.error,
                                  helperText: fieldState.error?.message,
                                  size: "small",
                                  sx: {
                                    backgroundColor: "white",
                                    borderRadius: "8px",
                                  },
                                },
                              }}
                            />
                          )}
                        />
                      </Box>
                    </Box>

                    {/* 第二排：請假類型 */}
                    <Box
                      sx={{
                        display: "flex",
                        gap: 2,
                        flexWrap: "wrap",
                      }}
                    >
                      <Box sx={{ flex: 1 }}>
                        <Typography fontSize={14}>請假類型</Typography>
                        <Controller
                          name="leave_type_id"
                          control={control}
                          defaultValue=""
                          rules={{ required: "請選擇請假類型" }}
                          render={({ field, fieldState }) => (
                            <FormControl fullWidth error={!!fieldState.error} sx={{ backgroundColor: "white", borderRadius: "8px" }}>
                              <Select
                                {...field}
                                disabled={mode === "view"}
                                displayEmpty
                                size="small"
                                onChange={(e) => {
                                  const selectedId = e.target.value;
                                  field.onChange(selectedId);
                                  const watchedStartTime = watch("startTime"); // ✅ 改用 RHF 的值
                                  fetchRemainingLeaveHours(Number(selectedId), watchedStartTime);
                                }}
                              >
                                <MenuItem value="" disabled>請選擇請假類型</MenuItem>
                                {formLeaveTypeOptions.map((item) => (
                                  <MenuItem key={item.id} value={item.id}>
                                    {item.description}
                                  </MenuItem>
                                ))}
                              </Select>
                              {/* ✅ 顯示錯誤訊息 */}
                              {fieldState.error && (
                                <Typography fontSize={12} color="error" sx={{ mt: 0.5, ml: 1 }}>
                                  {fieldState.error.message}
                                </Typography>
                              )}
                            </FormControl>
                          )}
                        />

                        {/* 自動顯示剩餘時數 */}
                        {mode !== "view" && leaveHours !== null && (
                          <Typography fontSize={13} sx={{ mt: 1, color: "#1976d2" }}>
                            💡 剩餘可請假時數：<strong>{leaveHours} 小時</strong>
                          </Typography>
                        )}
                      </Box>
                    </Box>

                    {/* 第三排：附件 */}
                    <Box>
                      <Typography fontSize={14}>附件</Typography>

                      {/* 新檔案上傳區域（只在 create / edit 模式可用） */}
                      {mode !== "view" && (
                        <TextField
                          type="file"
                          inputProps={{ accept: "image/*,application/pdf" }}
                          onChange={(e) => {
                            const file = e.target.files[0];
                            if (file) {
                              console.log("📎 使用者選擇了檔案：", file);
                              setAttachmentFile(file);
                            }
                          }}
                          sx={{ backgroundColor: "white", borderRadius: "8px", mb: 1 }}
                          size="small"
                          fullWidth
                        />
                      )}

                      {/* 若為編輯或查詢模式且有舊檔案，顯示連結 */}
                      {mode !== "create" && (
                        <Typography fontSize={15} sx={{ textAlign: "left" }}>
                          {selectedRequest?.attachment ? (
                            <>
                              📎 已上傳附件：&nbsp;
                              <a
                                href={selectedRequest.attachment}
                                target="_blank"
                                rel="noopener noreferrer"
                              >
                                查看附件
                              </a>
                            </>
                          ) : (
                            <span style={{ color: "#999" }}>⚠️ 此假單未附上任何檔案</span>
                          )}
                        </Typography>
                      )}
                    </Box>

                    {/* 第三排：請假原因 */}
                    <Box>
                      <Typography fontSize={14}>請假原因</Typography>
                      <TextField
                        {...register("reason", { required: "請假原因為必填" })}
                        multiline
                        rows={3}
                        disabled={mode === "view"}
                        error={!!errors.reason}
                        helperText={errors.reason?.message}
                        sx={{ backgroundColor: "white", borderRadius: "8px" }}
                        margin="dense"
                        fullWidth
                      />
                      {mode !== "view" && (
                        <Typography fontSize={13} sx={{ mt: 1 }}>
                          📌 不確定怎麼請假？&nbsp;
                          <Link
                            component="button"
                            variant="body2"
                            onClick={() => setPolicyOpen(true)}
                            underline="hover"
                          >
                            查看請假規則
                          </Link>
                        </Typography>
                      )}
                    </Box>

                    {/* 第四排：駁回原因（只在 view 模式顯示） */}
                    {mode === "view" && (
                      <Box>
                        <Typography fontSize={14}>駁回原因</Typography>
                        <TextField
                          value={selectedRequest?.reject_reason ?? "無"}
                          multiline
                          rows={2}
                          disabled
                          sx={{ backgroundColor: "white", borderRadius: "8px" }}
                          margin="dense"
                          fullWidth
                        />
                      </Box>
                    )}
                  </Box>

                  {/* 送出按鈕 */}
                  <Box sx={{ display: "flex", justifyContent: "center", gap: 2, mt: 3 }}>
                    {mode === "view" ? (
                      <Button
                        variant="outlined"
                        onClick={handleClose}
                        sx={{
                          backgroundColor: "white",
                          color: "#555",
                          borderColor: "#ccc",
                          width: "200px",
                          padding: "10px 25px",
                          borderRadius: "30px",
                          fontSize: "16px",
                          "&:hover": {
                            backgroundColor: "#e0e0e0",
                            borderColor: "#999",
                          },
                        }}
                      >
                        關閉
                      </Button>
                    ) : (
                      <>
                        {/* ✅ 送出放左邊 */}
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
                        >
                          送出
                        </Button>

                        {/* ❌ 取消放右邊 */}
                        <Button
                          variant="outlined"
                          onClick={handleClose}
                          sx={{
                            backgroundColor: "white",
                            color: "#555",
                            borderColor: "#ccc",
                            width: "200px",
                            padding: "10px 25px",
                            borderRadius: "30px",
                            fontSize: "16px",
                            "&:hover": {
                              backgroundColor: "#e0e0e0",
                              borderColor: "#999",
                            },
                          }}
                        >
                          取消
                        </Button>
                      </>
                    )}
                  </Box>
                </form>
              </>
            </LocalizationProvider>
          )}
        </Box>
      </Modal>

      <Dialog open={dialogOpen} onClose={() => setDialogOpen(false)}>
        <DialogTitle
          sx={{
            fontWeight: "bold",
            fontSize: 20,
            color: dialogSuccess ? "#388e3c" : "#d32f2f",
          }}
        >
          {dialogSuccess ? "假單送出成功" : "假單送出失敗"}
        </DialogTitle>

        <DialogContent>
          <Typography fontSize={16} mt={1}>
            {dialogMessage}
          </Typography>
        </DialogContent>

        <DialogActions sx={{ pr: 3, pb: 2 }}>
          <Button
            variant="contained"
            onClick={() => setDialogOpen(false)}
            sx={{
              backgroundColor: dialogSuccess ? "#4caf50" : "#d32f2f",
              borderRadius: "30px",
              px: 4,
              fontWeight: "bold",
              "&:hover": {
                backgroundColor: dialogSuccess ? "#388e3c" : "#c62828",
              },
            }}
          >
            確認
          </Button>
        </DialogActions>
      </Dialog>

      <Dialog
        open={policyOpen}
        onClose={() => setPolicyOpen(false)}
        PaperProps={{
          sx: {
            width: "1000px", 
            maxWidth: "95vw",   
            borderRadius: "16px",
            minHeight: "90vh",
            maxHeight: "95vh",
          },
        }}
      >
        <DialogContent sx={{ p: 0 }}>
          <LeavePolicy onClose={() => setPolicyOpen(false)} />
        </DialogContent>
      </Dialog>

    </Box >
  );
}

export default ApproveLeave;