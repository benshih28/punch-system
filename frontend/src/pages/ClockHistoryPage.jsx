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
   // 查詢輸入框
   // const [departments, setDepartments] = useState([]); //確保departments 初始值為空陣列
   const [departments, setDepartments] = useState([
     { id: 1, name: "人資部" },
     { id: 2, name: "行銷部" },
   ]);
   const [department, setDepartment] = useState("");
   const [employeeId, setEmployeeId] = useState("");
   const [selectedYear, setSelectedYear] = useState(new Date().getFullYear());
   const [selectedMonth, setSelectedMonth] = useState(new Date().getMonth() + 1);
   // 分頁控制
   const [page, setPage] = useState(0); //預設第0頁
   const [rowsPerPage, setRowsPerPage] = useState(10); //每頁顯示的資料筆數，預設 10 筆
   // const [filteredRows, setFilteredRows] = useState([]);

   const [rows, setRows] = useState([
     {
       applicant: "王小明",
       employeeId: "001",
       department: "人資部",
       records: {
         1: { punchIn: "08:00", punchOut: "18:00" },
         3: { punchIn: "09:00", punchOut: "17:30" },
       },
     },
     {
       applicant: "黃冬天",
       employeeId: "002",
       department: "行銷部",
       records: {
         1: { punchIn: "08:59", punchOut: "18:05" },
         2: { punchIn: "08:58", punchOut: "18:00" },
         5: { punchIn: "09:00", punchOut: "17:30" },
         6: { punchIn: "09:00", punchOut: "17:30" },
         13: { punchIn: "09:14", punchOut: "17:30" },
       },
     },
     {
       applicant: "何夏天",
       employeeId: "003",
       department: "行銷部",
       records: {
         1: { punchIn: "08:55", punchOut: "18:03" },
         2: { punchIn: "08:55", punchOut: "18:04" },
         4: { punchIn: "09:00", punchOut: "17:30" },
         16: { punchIn: "09:00", punchOut: "17:30" },
       },
     },
   ]);

   const filteredRows = rows.filter((row) => {
     return (
       (department === "" || row.department === department) && // 如果 `department` 為空，顯示所有部門；否則只顯示該部門的員工
       (employeeId === "" || row.employeeId.includes(employeeId)) // 如果 `employeeId` 為空，顯示所有員工；否則只顯示符合的員工
     );
   });

   // 1. 取得所有部門（用於下拉選單）
   // useEffect(() => {
   //   API.get("/departments/")
   //     .then((response) => {
   //       setDepartments(response.data);//將 API 回應存入 departments
   //     })
   //     .catch((error) => console.error("獲取部門失敗", error));
   // }, []);

   useEffect(() => {
     console.log("部門清單:", departments);
   }, [departments]); // 當 `departments` 改變時，執行 log

   // 2. 取得個人的補登打卡紀錄
   // useEffect(() => {
   //   API.get("/punch/correction")
   //     .then((response) => {
   //       setUserCorrections(response.data);
   //     })
   //     .catch((error) => console.error("獲取個人補登打卡紀錄失敗", error));
   // }, []);

   // 3. 取得所有人的打卡紀錄（HR 使用）
   // useEffect(() => {
   //   API.get("/attendancerecords")
   //     .then((response) => {
   //       setAllAttendanceRecords(response.data);
   //     })
   //     .catch((error) => console.error("獲取所有人的打卡紀錄失敗", error));
   // }, []);


   const handleSearch = async () => {
     try {
       const response = await API.get(
         `/attendance?year=${selectedYear}&month=${selectedMonth}`
       );
       const attendanceData = response.data;

       const formattedData = attendanceData.map((record) => ({
         applicant: record.employeeName, //員工名稱
         records: record.days.reduce((acc, day) => { //每一天的上班和下班時間
           acc[day.date] = {
             punchIn: day.punchInTime,
             punchOut: day.punchOutTime,
           };
           return acc;
         }, {}),
       }));

       setRows(formattedData); //更新rows
     } catch (error) {
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
             {Array.isArray(departments) &&  //確保departments 是陣列
             departments.map((dept) => (
               <MenuItem key={dept.id} value={dept.name}>
                 {dept.name}
               </MenuItem>
             ))}
           </Select>

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
           <TableContainer sx={{ flex: 1, overflow: "auto" }}>
             <Table stickyHeader>
               <TableHead>
                 <TableRow>
                   <TableCell align="center">姓名</TableCell>
                   {[...Array(31)].map((_, index) => (
                     <TableCell key={index} align="center">
                       {index + 1}
                     </TableCell>
                   ))}
                 </TableRow>
               </TableHead>
               <TableBody>
                 {filteredRows.map((row) => (
                   <TableRow key={row.applicant}>
                     <TableCell align="center">{row.applicant}</TableCell>
                     {[...Array(31)].map((_, index) => {
                       const day = index + 1;
                       const punchIn = row.records?.[day]?.punchIn || "";
                       const punchOut = row.records?.[day]?.punchOut || "";
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
             count={rows.length}
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