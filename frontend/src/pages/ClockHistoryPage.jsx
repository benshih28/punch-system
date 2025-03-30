import { useState, useEffect } from "react";
 import { useAtom } from "jotai";
 import { authAtom } from "../state/authAtom";
 import API from "../api/axios";

 // Material UI 元件
 import {
   Box,
   Paper,
   Button,
   Typography,
   Table,
   TableBody,
   TableCell,
   TableContainer,
   TableHead,
   TableRow,
   TablePagination,
   TextField,
   Select,
   MenuItem,
 } from "@mui/material";
 import ManageSearchIcon from "@mui/icons-material/ManageSearch";
 import { DatePicker, LocalizationProvider } from "@mui/x-date-pickers";
 import { AdapterDateFns } from "@mui/x-date-pickers/AdapterDateFns";

 function ClockHistoryPage() {
  const [auth] = useAtom(authAtom);
   // 查詢輸入框
    const [departments, setDepartments] = useState([]); //確保departments 初始值為空陣列
    //  { id: 1, name: "人資部" },
    //  { id: 2, name: "行銷部" },

   const [department, setDepartment] = useState("");
   const [employeeId, setEmployeeId] = useState("");
   const [selectedYear, setSelectedYear] = useState(new Date().getFullYear());
   const [selectedMonth, setSelectedMonth] = useState(new Date().getMonth() + 1);
   const [attendanceRecords, setAttendanceRecords] = useState([]);
   // 分頁控制
   const [page, setPage] = useState(0); //預設第0頁
   const [rowsPerPage, setRowsPerPage] = useState(10); //每頁顯示的資料筆數，預設 10 筆
   // const [filteredRows, setFilteredRows] = useState([]);
   
   const [rows, setRows] = useState([]);
    // 測試的假資料
    //  {
    //    applicant: "黃冬天",
    //    employeeId: "002",
    //    department: "行銷部",
    //    records: {
    //      1: { punchIn: "08:59", punchOut: "18:05" },
    //      2: { punchIn: "08:58", punchOut: "18:00" },
    //      13: { punchIn: "09:14", punchOut: "17:30" },
    //    },
    //  },
    //  {
    //    applicant: "何夏天",
    //    employeeId: "003",
    //    department: "行銷部",
    //    records: {
    //      1: { punchIn: "08:55", punchOut: "18:03" },
    //      4: { punchIn: "09:00", punchOut: "17:30" },
    //      16: { punchIn: "09:00", punchOut: "17:30" },
    //    },
    //  },

  //  const filteredRows = rows.filter((row) => {
  //    return (
  //      (department === "" || row.department === department) && // 如果 `department` 為空，顯示所有部門；否則只顯示該部門的員工
  //      (employeeId === "" || row.employeeId.includes(employeeId)) // 如果 `employeeId` 為空，顯示所有員工；否則只顯示符合的員工
  //    );
  //  });
     
  const user = auth?.user;// 拿登入者資訊
  const roles = auth?.roles_permissions?.roles || [];
  const permissions = auth?.roles_permissions?.permissions || [];
  const userRole = roles[0] || "";

  const departmentName = user?.department_name || ""; // 這就是你要顯示的部門名稱

   // 1. 取得所有部門
   useEffect(() => {
   if (permissions.includes("manage_departments")) {
    // HR 或有權限的使用者 → 撈全部部門
     API.get("/departments/")
       .then((response) => {
         setDepartments(response.data.departments);//從 JSON 物件中取出departments陣列
       })
       .catch((error) => console.error("獲取部門失敗", error));
   }else if (user?.department_id && user?.department_name) {
    // 沒有權限 → 用登入者的部門
    setDepartments([{ id: user.department_id, name: user.department_name }]);
    setDepartment(user.department_name);
  }
}, [permissions, user]);

   // 2. 取得個人的打卡紀錄
   useEffect(() => {
     API.get(`/attendance/record?year=${selectedYear}&month=${selectedMonth}`)
       .then((response) => {
        const data = response.data.data.data;
      // console.log("獲取的打卡紀錄（完整）:", data);
      // console.log("user_id 為 4 的資料:", data.find(item => item.user_id === 4));
      // console.log("獲取的打卡紀錄:", response.data.data.data); 

        const formattedRecords = response.data.data.data.map(user => ({
          userId: user.user_id,
          userName: user.user_name,
          records: user.records.reduce((acc, record) => {
            acc[record.date] = {
              punchIn: record.punch_in ? record.punch_in.split(" ")[1] : "",  // 只取時間部分
              punchOut: record.punch_out ? record.punch_out.split(" ")[1] : "",
            };
            return acc;
          }, {})
        }));
        console.log("轉換後的打卡資料:", formattedRecords); // Debug 確保格式正確
        setAttendanceRecords(formattedRecords);
      })
      .catch((error) => console.error("獲取個人打卡紀錄失敗", error));
   }, []);

   // 3. 取得所有人的打卡紀錄（HR 使用）
   // useEffect(() => {
   //   API.get("/attendancerecords")
   //     .then((response) => {
   //       setAllAttendanceRecords(response.data);
   //     })
   //     .catch((error) => console.error("獲取所有人的打卡紀錄失敗", error));
   // }, []);

   const fetchAllAttendanceRecords = async ({
    year,
    month,
    page = 1,
    perPage = 10,
    departmentId = null,
    userId = null,
  }) => {
    try {
      const params = {
        year,
        month,
        page,
        per_page: perPage,
      };
      if (departmentId) params.department_id = departmentId;
      if (userId) params.user_id = userId;

      const response = await API.get("/attendancerecords", { params });
      return response.data.data; // 取出真正的資料
    } catch (error) {
      console.error("獲取所有人打卡紀錄失敗", error);
      return null;
    }
  };

   const handleSearch = async () => {
     try {
      // 判斷是否為 HR（部門 id = 1）
      const isHR = auth?.user?.department_id === 1;

    if (isHR) {
      // 如果是 HR，查詢所有人的紀錄
      const params = {
        year: selectedYear,
        month: selectedMonth,
        page: page + 1, // 分頁從 1 開始
        per_page: rowsPerPage,
      };
      if (department !== "") params.department_id = department;
      if (employeeId) params.user_id = employeeId;

      const response = await API.get("/attendancerecords", { params });

      //  const attendanceData = response.data;
      
      const formattedData = response.data.data.data.map((user) => ({
        userId: user.user_id,
        userName: user.user_name,
        department: user.department_name || "",
        records: user.records.reduce((acc, record) => { //每一天的上班和下班時間
          acc[record.date] = {
            punchIn: record.punch_in ? record.punch_in.split(" ")[1] : "",
            punchOut: record.punch_out ? record.punch_out.split(" ")[1] : "",
          };
          return acc;
        }, {}),
      }));
      setAttendanceRecords(formattedData);
    } else {
      //  如果不是 HR，查詢自己的紀錄
      const response = await API.get(
        `/attendance/record?year=${selectedYear}&month=${selectedMonth}`
      );
      const formattedData = response.data.data.data.map((user) => ({
        userId: user.user_id,
        userName: user.user_name,
        department: user.department_name || "",
        records: user.records.reduce((acc, record) => {
          acc[record.date] = {
            punchIn: record.punch_in ? record.punch_in.split(" ")[1] : "",
            punchOut: record.punch_out ? record.punch_out.split(" ")[1] : "",
          };
          return acc;
        }, {}),
      }));

      // setRows(formattedData); //更新rows
      setAttendanceRecords(formattedData);
     } 
    }
     catch (error) {
       console.error("查詢失敗", error);
     }
   };

   return (
     <Box
       sx={{
         width: "100%",
         height: "100%",
         display: "flex",
         flexDirection: "column",
         alignItems: "center",
         backgroundColor: "#ffffff",
       }}
     >
       <Paper
         elevation={0}
         sx={{
           width: "90%",
           flex: 1,
           display: "flex",
           flexDirection: "column",
           alignItems: "center",
           padding: "20px",
         }}
       >
         {/* 標題 */}
         <Typography variant="h4" fontWeight={900} textAlign="center" sx={{ mb: 1 }}>
           查詢打卡紀錄
         </Typography>

         {/* 查詢條件 */}
         <Box
           sx={{
             backgroundColor: "#D2E4F0",
             width: "90%",
             // height: "45px", // 設定固定高度
             padding: "10px",
             borderRadius: "8px",
             display: "flex",
             flexWrap: "wrap", // 在縮小時換行
             alignItems: "center",
             justifyContent: "center",
             gap: 2,
           }}
         >

           {/* 選擇部門 */}
           <Typography variant="body1" sx={{ whiteSpace: "nowrap" }}>請選擇部門</Typography>
           {permissions.includes("manage_departments") ? (
            <Select
              // label="選擇部門" variant="outlined" size="small"
              value={department}
              onChange={(e) => setDepartment(e.target.value)}
              displayEmpty
              variant="outlined"
              size="small"
              sx={{ backgroundColor: "white", width: "130px" }}
            >
              <MenuItem value="">請選擇部門</MenuItem>
              {/* {Array.isArray(departments) &&  //確保departments 是陣列 */
              departments.map((dept) => (
                <MenuItem key={dept.id} value={dept.id}>
                  {dept.name}
                </MenuItem>
              ))}
            </Select>
            ) : (
              <Typography
                variant="body1"
                sx={{
                  backgroundColor: "white",
                  padding: "6px 12px",
                  borderRadius: "4px",
                  border: "1px solid #ccc",
                  minWidth: "130px",
                  textAlign: "center",
                }}
              >
                {departmentName || "尚未設定部門"}
              </Typography>
            )}

           {/* 員工編號 */}
           <Box sx={{ display: "flex", alignItems: "center", gap: 1 }}>
             <Typography variant="body1" sx={{ whiteSpace: "nowrap" }}>員工編號</Typography>
             <TextField
               // label="員工編號"
               variant="outlined"
               size="small"
               value={employeeId}
               onChange={(e) => setEmployeeId(e.target.value)}
               sx={{ backgroundColor: "white", width: "130px" }}
             />
           </Box>

           {/* 選擇年份 */}
           <Box sx={{ display: "flex", alignItems: "center", gap: 1 }}>
             <Typography variant="body1" sx={{ whiteSpace: "nowrap" }}>選擇年份</Typography>
             <LocalizationProvider dateAdapter={AdapterDateFns}>
               <DatePicker
                 views={["year"]}
                 // label="選擇年份"
                 value={new Date(selectedYear, 0)}
                 onChange={(newValue) => setSelectedYear(newValue.getFullYear())}
                 slotProps={{
                   textField: {
                     variant: "outlined",
                     size: "small",
                     sx: { backgroundColor: "white", width: "130px" },
                   },
                 }}
               />
             </LocalizationProvider>

           </Box>

           {/* 選擇月份 */}
           <Box sx={{ display: "flex", alignItems: "center", gap: 1 }}>
             <Typography variant="body1" sx={{ whiteSpace: "nowrap" }}>選擇月份</Typography>
             <LocalizationProvider dateAdapter={AdapterDateFns}>
               <DatePicker
                 views={["month"]}
                 // label="選擇月份"
                 value={new Date(selectedYear, selectedMonth - 1)}
                 onChange={(newValue) => setSelectedMonth(newValue.getMonth() + 1)}
                 slotProps={{
                   textField: {
                     variant: "outlined",
                     size: "small",
                     sx: { backgroundColor: "white", width: "130px" },
                   },
                 }}
               />
             </LocalizationProvider>
           </Box>
         </Box>

         {/* 查詢按鈕 */}
         <Button
           variant="contained"
           sx={{
             backgroundColor: "#AB9681",
             color: "white",
             fontWeight: "bold",
             fontSize: "18px",
             borderRadius: "20px",
             padding: "2px 40px",
             justifyContent: "flex-start",
             marginTop: "15px",
           }}
           startIcon={<ManageSearchIcon />}
           onClick={handleSearch}
         >
           查詢
         </Button>

         {/* 打卡紀錄表格 */}
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
           <TableContainer component={Paper} sx={{ flex: 1, overflow: "auto" }}>
             <Table stickyHeader>
               <TableHead>
                 <TableRow>
                   <TableCell align="center">姓名</TableCell>
                   {[...Array(31)].map((_, index) => {
                    // 判斷是否是假日
                    const day = (index + 1).toString().padStart(2, "0");
                    const dateKey = `${selectedYear}-${selectedMonth.toString().padStart(2, "0")}-${day}`;
                    const date = new Date(dateKey);
                    const isWeekend = date.getDay() === 0 || date.getDay() === 6; // Sunday or Saturday
                    return (
                     <TableCell key={index} align="center"
                     sx={{
                      color: isWeekend ? "#d35724" : "inherit",
                      fontWeight: isWeekend ? "bold" : "normal",
                    }}>
                       {index + 1}
                     </TableCell>
                    );
                  })}
                 </TableRow>
               </TableHead>
               <TableBody>
                 {attendanceRecords.map((row) => (
                   <TableRow key={row.userId}>
                     <TableCell align="center">{row.userName}</TableCell>
                     {[...Array(31)].map((_, index) => {
                       const day = (index + 1).toString().padStart(2, "0");;
                       const dateKey = `${selectedYear}-${selectedMonth.toString().padStart(2, "0")}-${day}`;
                       const punchIn = row.records?.[dateKey]?.punchIn || "";
                       const punchOut = row.records?.[dateKey]?.punchOut || "";
                       return (
                         <TableCell key={index} align="center">
                           {punchIn && <div>{punchIn}</div>}
                           {punchOut && <div>{punchOut}</div>}
                         </TableCell>
                       );
                     })}
                   </TableRow>
                 ))}
               </TableBody>
             </Table>
           </TableContainer>

           {/* 分頁 */}
           <TablePagination
             rowsPerPageOptions={[10, 25, 50]}
             component="div"
             count={attendanceRecords.length}
             rowsPerPage={rowsPerPage}
             page={page}
             onPageChange={(event, newPage) => setPage(newPage)}
             onRowsPerPageChange={(event) => setRowsPerPage(+event.target.value)}
             sx={{
               borderTop: "1px solid #ddd",
               backgroundColor: "#fff",
             }}
           />
         </Paper>
       </Paper>
     </Box>
   );
 }

 export default ClockHistoryPage;