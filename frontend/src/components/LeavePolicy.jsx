import React, { useState } from "react";
import {
    Box,
    Typography,
    Card,
    CardContent,
    List,
    ListItem,
    ListItemText,
    styled,
    Tab,
    Tabs,
    Button
} from "@mui/material";
import {
    EventNote,
    FactCheck,
    NearbyError,
    LooksOne,
    Warning,
    LooksTwo,
    Looks3,
    Looks4,
} from "@mui/icons-material";

// 🔥 MUI Tabs 樣式
const TabItem = styled(Tab)(({ theme }) => ({
    opacity: 1,
    overflow: "initial",
    paddingLeft: theme.spacing(2),
    paddingRight: theme.spacing(2),
    borderTopLeftRadius: theme.spacing(1),
    borderTopRightRadius: theme.spacing(1),
    color: theme.palette.text.primary,
    backgroundColor: theme.palette.grey[300],
    transition: "0.2s",
    zIndex: 2,
    marginTop: theme.spacing(0.5),
    textTransform: "initial",
    [theme.breakpoints.up("md")]: {
        minWidth: 160,
    },
    "&:hover": {
        backgroundColor: "rgba(0, 0, 0, 0.2)",
    },
    "&.Mui-selected": {
        backgroundColor: theme.palette.grey[900],
        color: theme.palette.common.white,
    },
}));

function LeavePolicy({ onClose }) {
    const [tabIndex, setTabIndex] = useState(0);

    return (
        <Box
            sx={{
                maxWidth: "800px",
                margin: "auto",
                marginTop: "25px", // 設定與上方的距離
                marginBottom: "25px", // 設定與上方的距離                
                padding: 6,
                height: "700px", // 設定固定高度
                backgroundColor: "#f9f9f9",
                borderRadius: "12px",
                boxShadow: 3,
                textAlign: "center",
                alignItems: "center", // 水平置中
            }}
        >
            {/* **應用程式 Logo** */}
            <img
                src="/logo.png"
                alt="Dacall Logo"
                style={{ width: 90, display: "block", margin: "0 auto 20px" }} // Logo 設定
            />
            <Typography variant="h4" fontWeight="bold" gutterBottom >
                員工請假規則
            </Typography>

            {/* 🔥 Tabs 選單 */}
            <Tabs
                value={tabIndex}
                onChange={(event, newValue) => setTabIndex(newValue)}
                sx={{
                    marginBottom: 1.5,
                    marginTop: "20px", // 設定與上方的距離

                }}
            >
                <TabItem label="請假類型" sx={{ fontSize: 15, fontWeight: "bold" }} />
                <TabItem label="申請流程" sx={{ fontSize: 15, fontWeight: "bold" }} />
                <TabItem label="特殊條件" sx={{ fontSize: 15, fontWeight: "bold" }} />
            </Tabs>

            {/* 🔥 Tabs 內容 */}
            {tabIndex === 0 && (
                <Card sx={{ backgroundColor: "rgba(227, 242, 253, 0.7)", marginBottom: 2, padding: 1 }}>
                    <CardContent sx={{ padding: 3, maxHeight: "380px", overflowY: "auto" }} >
                        <Typography variant="h5" fontWeight="bold" gutterBottom>
                            <EventNote sx={{ verticalAlign: "middle", mr: 1 }} />
                            假別類型 (Leave Types)
                        </Typography>

                        <Typography variant="body2" sx={{ color: "gray", fontStyle: "italic", mt: 1.5, mb: 3, ml: 10, mr: 10, textAlign: "left" }}>
                            根據《勞動基準法》及《性別工作平等法》之相關規定，勞工依法享有請假權益，包括特別休假、生理假、婚假、喪假等。
                            雇主應依規定保障勞工請假權益，並不得無故拒絕或影響其勞動條件。
                        </Typography>

                        {/* 一般假別 */}
                        <Typography fontWeight="bold" align="left" sx={{ fontSize: "18px", mt: 1, ml: 1.5 }}>
                            <LooksOne sx={{ verticalAlign: "top", mr: 1 }} />
                            一般假別
                        </Typography>
                        <List sx={{ ml: 2 }}>
                            <ListItem sx={{ py: 0.5 }}>
                                <ListItemText
                                    primary={
                                        <Typography fontWeight="bold">
                                            1. 事假（Personal Leave）：需提前 3 天申請，扣薪。
                                        </Typography>
                                    }
                                />
                            </ListItem>
                            <ListItem sx={{ py: 0.5 }}>
                                <ListItemText
                                    primary={
                                        <Typography fontWeight="bold">
                                            2. 病假（Sick Leave）：當日或事後 3 日內補請，需附醫療證明，不扣薪。
                                        </Typography>
                                    }
                                />
                            </ListItem>
                            <ListItem sx={{ py: 0.5 }}>
                                <ListItemText
                                    primary={
                                        <Typography fontWeight="bold">
                                            3. 公假（Official Leave）：因公務需求請假，需核准，不扣薪。
                                        </Typography>
                                    }
                                />
                            </ListItem>
                        </List>

                        {/* 特殊假別 */}
                        <Typography fontWeight="bold" align="left" sx={{ fontSize: "18px", mt: 1, ml: 1.5 }}>
                            <LooksTwo sx={{ verticalAlign: "top", mr: 1 }} />
                            特殊假別
                        </Typography>
                        <List sx={{ ml: 2 }}>
                            <ListItem sx={{ py: 0.5 }}>
                                <ListItemText
                                    primary={
                                        <Typography fontWeight="bold">
                                            1. 生理假（Menstrual Leave）：每月最多 1 天（8 小時），不扣薪。
                                        </Typography>
                                    }
                                />
                            </ListItem>
                            <ListItem sx={{ py: 0.5 }}>
                                <ListItemText
                                    primary={
                                        <Typography fontWeight="bold">
                                            2. 特休假（Annual Leave）
                                        </Typography>
                                    }
                                    secondary={
                                        <>
                                            <Typography sx={{ py: 0.3, ml: 2, color: "black", fontSize: "15px", mt: 1 }}>
                                                •  服務滿 6 個月，未滿 1 年 → 3 天 （24小時）。
                                            </Typography>
                                            <Typography sx={{ py: 0.3, ml: 2, color: "black", fontSize: "15px", mt: 1 }}>
                                                •  滿 1 年但未滿 2 年 → 7 天 （56小時）。
                                            </Typography>
                                            <Typography sx={{ py: 0.3, ml: 2, color: "black", fontSize: "15px", mt: 1 }}>
                                                •  滿 2 年但未滿 3 年 → 10 天 （80小時）。
                                            </Typography>
                                            <Typography sx={{ py: 0.3, ml: 2, color: "black", fontSize: "15px", mt: 1 }}>
                                                •  滿 3 年但未滿 5 年 → 14 天 （112小時）。
                                            </Typography>
                                            <Typography sx={{ py: 0.3, ml: 2, color: "black", fontSize: "15px", mt: 1 }}>
                                                •  滿 5 年但未滿 10 年 → 15 天 （120小時）。
                                            </Typography>
                                            <Typography sx={{ py: 0.3, ml: 2, color: "black", fontSize: "15px", mt: 1 }}>
                                                •  滿 10 年以上，每年增加 1 天，但最多 30 天 （240小時）。
                                            </Typography>
                                        </>
                                    }
                                />
                            </ListItem>

                            <ListItem sx={{ py: 0.5 }}>
                                <ListItemText
                                    primary={
                                        <Typography fontWeight="bold">
                                            3. 婚假（Marriage Leave）：最多 3 天（24 小時），需提前 7 天申請，給薪。
                                        </Typography>
                                    }
                                />
                            </ListItem>
                            <ListItem sx={{ py: 0.5 }}>
                                <ListItemText
                                    primary={
                                        <Typography fontWeight="bold">
                                            4. 產假（Maternity Leave）：依勞基法規定，最多 8 天（64 小時），給薪。
                                        </Typography>
                                    }
                                />
                            </ListItem>
                            <ListItem sx={{ py: 0.5 }}>
                                <ListItemText
                                    primary={
                                        <Typography fontWeight="bold">
                                            5. 喪假（Bereavement Leave）
                                        </Typography>
                                    }
                                    secondary={
                                        <>
                                            <Typography sx={{ py: 0.3, ml: 2, color: "black", fontSize: "15px", mt: 1 }}>
                                                •  父母配偶（Immediate Family）：最多 8 天（64 小時），給薪。
                                            </Typography>
                                            <Typography sx={{ py: 0.3, ml: 2, color: "black", fontSize: "15px", mt: 1 }}>
                                                •  祖父母、子女 （Close Family）：最多 5 天（40 小時），給薪。
                                            </Typography>
                                            <Typography sx={{ py: 0.3, ml: 2, color: "black", fontSize: "15px", mt: 1 }}>
                                                •  兄弟姊妹、岳父母（Extended Family）：最多 3 天（24 小時），給薪。
                                            </Typography>
                                        </>
                                    }
                                />
                            </ListItem>
                        </List>
                    </CardContent>
                </Card>
            )}

            {tabIndex === 1 && (
                <Card sx={{ backgroundColor: "#FFF3E0", marginBottom: 2, padding: 1 }}>
                    <CardContent sx={{ padding: 3, maxHeight: "380px", overflowY: "auto" }}>
                        <Typography variant="h5" fontWeight="bold" gutterBottom>
                            <FactCheck sx={{ verticalAlign: "middle", mr: 1, fontSize: "25px" }} />
                            請假申請流程 (Leave Application Process)
                        </Typography>

                        <Typography variant="body2" sx={{ color: "gray", fontStyle: "italic", mt: 1.5, mb: 1.2, ml: 10, mr: 10, textAlign: "left" }}>
                            依據《勞動基準法》及公司請假規範，員工應遵循標準程序辦理請假申請。請假流程包含員工提交申請、主管審核、人資最終確認，並由 HR 通知審核結果。
                            所有請假類別（特休、病假、事假等）均需依規定提出，特定類別（如病假、婚假）可能須提供相關證明文件。
                            雇主應依規定保障勞工請假權益，並確保請假流程透明、公正。
                        </Typography>

                        <List>
                            <ListItem sx={{ py: 0.5 }}>
                                <ListItemText
                                    primary={
                                        <Typography fontWeight="bold" sx={{ fontSize: "18px" }}>
                                            <LooksOne sx={{ verticalAlign: "top", mr: 1 }} />
                                            員工透過「請假系統」提交假單，申請須包含：
                                        </Typography>
                                    }
                                    secondary={
                                        <>
                                            <Typography sx={{ py: 0.3, ml: 2, color: "black", fontSize: "15px", mt: 1 }}>
                                                •  請假類型：特休、事假、病假、婚假、喪假等
                                            </Typography>
                                            <Typography sx={{ py: 0.3, ml: 2, color: "black", fontSize: "15px", mt: 1 }}>
                                                •  請假時間：開始與結束日期
                                            </Typography>
                                            <Typography sx={{ py: 0.3, ml: 2, color: "black", fontSize: "15px", mt: 1 }}>
                                                •  請假原因
                                            </Typography>
                                            <Typography sx={{ py: 0.3, ml: 2, color: "black", fontSize: "15px", mt: 1 }}>
                                                •  附加文件，如醫療證明、結婚證明等
                                            </Typography>
                                        </>
                                    }
                                />
                            </ListItem>
                            <ListItem sx={{ py: 0.5 }}>
                                <ListItemText
                                    primary={
                                        <Typography fontWeight="bold" sx={{ fontSize: "18px" }}>
                                            <LooksTwo sx={{ verticalAlign: "top", mr: 1 }} />
                                            直屬主管：負責第一階段審核
                                        </Typography>
                                    }
                                    secondary={
                                        <>
                                            <Typography sx={{ py: 0.3, ml: 2, color: "black", fontSize: "15px", mt: 1 }}>
                                                •  是否影響部門運作
                                            </Typography>
                                            <Typography sx={{ py: 0.3, ml: 2, color: "black", fontSize: "15px", mt: 1 }}>
                                                •  是否符合請假規範（如：事假是否超過限制、是否符合特休規則）
                                            </Typography>
                                            <Typography sx={{ py: 0.3, ml: 2, color: "black", fontSize: "15px", mt: 1 }}>
                                                •  是否需要調整工作安排（例如：轉交工作、安排代班）
                                            </Typography>
                                        </>
                                    }
                                />
                            </ListItem>
                            <ListItem sx={{ py: 0.5 }}>
                                <ListItemText
                                    primary={
                                        <Typography fontWeight="bold" sx={{ fontSize: "18px" }}>
                                            <Looks3 sx={{ verticalAlign: "top", mr: 1 }} />
                                            HR（人資）：最終審核
                                        </Typography>
                                    }
                                    secondary={
                                        <>
                                            <Typography sx={{ py: 0.3, ml: 2, color: "black", fontSize: "15px", mt: 1 }}>
                                                •  檢查請假申請是否符合請假規範，做最終決策
                                            </Typography>
                                        </>
                                    }
                                />
                            </ListItem>

                        </List>
                    </CardContent>
                </Card>
            )}

            {tabIndex === 2 && (
                <Card sx={{ backgroundColor: "#FFEBEE", marginBottom: 2, padding: 1 }}>
                    <CardContent sx={{ padding: 3, maxHeight: "380px", overflowY: "auto" }}>
                        <Typography variant="h5" fontWeight="bold" gutterBottom>
                            <NearbyError sx={{ verticalAlign: "middle", mr: 1, fontSize: "25px" }} />
                            特殊條件 (Special Conditions)
                        </Typography>

                        <Typography variant="body2" sx={{ color: "gray", fontStyle: "italic", mt: 1.5, mb: 1.2, ml: 10, mr: 10, textAlign: "left" }}>
                            依據《勞動基準法》及公司請假規範，部分請假類型適用特定條件，如試用期內的請假限制、事假與病假額度、請假期間的薪資影響等。
                            員工應提前申請，並遵守公司內部審核流程。特定類別（如病假、婚假）可能需提供證明文件，連續請假超過規定天數須經主管與 HR 雙重核准。
                            企業應確保請假管理公平、透明，兼顧員工權益與公司運營需求。
                        </Typography>

                        <List>
                            <ListItem sx={{ py: 0.5 }}>
                                <ListItemText
                                    primary={
                                        <Typography fontWeight="bold" sx={{ fontSize: "18px" }}>
                                            <LooksOne sx={{ verticalAlign: "top", mr: 1 }} />
                                            試用期內請假限制：
                                        </Typography>
                                    }
                                    secondary={
                                        <>
                                            <Typography sx={{ py: 0.3, ml: 2, color: "black", fontSize: "15px", mt: 1 }}>
                                                •  試用期（前 3~6 個月）不享特休，但可請病假、事假
                                            </Typography>
                                            <Typography sx={{ py: 0.3, ml: 2, color: "black", fontSize: "15px", mt: 1 }}>
                                                •  病假 3 天內免證明，超過須醫療證明
                                            </Typography>
                                            <Typography sx={{ py: 0.3, ml: 2, color: "black", fontSize: "15px", mt: 1 }}>
                                                •  試用期內頻繁請假，可能影響轉正考核
                                            </Typography>
                                        </>
                                    }
                                />
                            </ListItem>
                            <ListItem sx={{ py: 0.5 }}>
                                <ListItemText
                                    primary={
                                        <Typography fontWeight="bold" sx={{ fontSize: "18px" }}>
                                            <LooksTwo sx={{ verticalAlign: "top", mr: 1 }} />
                                            事假 & 病假規則：
                                        </Typography>
                                    }
                                    secondary={
                                        <>
                                            <Typography sx={{ py: 0.3, ml: 2, color: "black", fontSize: "15px", mt: 1 }}>
                                                •  事假須提前 3 天申請，請假天數過多，可能會影響績效獎金
                                            </Typography>
                                            <Typography sx={{ py: 0.3, ml: 2, color: "black", fontSize: "15px", mt: 1 }}>
                                                •  病假請假天數過多，需轉為事假或無薪假
                                            </Typography>
                                        </>
                                    }
                                />
                            </ListItem>
                            <ListItem sx={{ py: 0.5 }}>
                                <ListItemText
                                    primary={
                                        <Typography fontWeight="bold" sx={{ fontSize: "18px" }}>
                                            <Looks3 sx={{ verticalAlign: "top", mr: 1 }} />
                                            連續請假 & 旺季限制：
                                        </Typography>
                                    }
                                    secondary={
                                        <>
                                            <Typography sx={{ py: 0.3, ml: 2, color: "black", fontSize: "15px", mt: 1 }}>
                                                •  事假最多 3 天，超過須主管批准
                                            </Typography>
                                            <Typography sx={{ py: 0.3, ml: 2, color: "black", fontSize: "15px", mt: 1 }}>
                                                •  年度結算、專案交付等關鍵時段，請假須提前 2 週申請
                                            </Typography>
                                            <Typography sx={{ py: 0.3, ml: 2, color: "black", fontSize: "15px", mt: 1 }}>
                                                •  同部門同時請假超過 2 人，須主管協調
                                            </Typography>
                                        </>
                                    }
                                />
                            </ListItem>
                            <ListItem sx={{ py: 0.5 }}>
                                <ListItemText
                                    primary={
                                        <Typography fontWeight="bold">
                                            <Looks4 sx={{ verticalAlign: "top", mr: 1 }} />
                                            緊急狀況 & 天災請假：
                                        </Typography>
                                    }
                                    secondary={
                                        <>
                                            <Typography sx={{ py: 0.3, ml: 2, color: "black", fontSize: "15px", mt: 1 }}>
                                                •  遇颱風、地震、疫情等災害，依政府公告是否停班停課，若仍需上班，可選擇請假或補休
                                            </Typography>
                                            <Typography sx={{ py: 0.3, ml: 2, color: "black", fontSize: "15px", mt: 1 }}>
                                                •  疫情期間，可遠端工作或申請特別假
                                            </Typography>
                                        </>
                                    }
                                />
                            </ListItem>
                        </List>
                    </CardContent>
                </Card>
            )}
            <Button
                variant="contained"
                color="info"
                onClick={onClose} // 👉 點擊就呼叫 props 傳進來的 onClose 方法
                sx={{ width: "150px", height: "40px", fontSize: "16px" }}
            >
                返回上一頁
            </Button>
        </Box>
    );
}


export default LeavePolicy;