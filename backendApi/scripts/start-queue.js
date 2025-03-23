const { spawn } = require("child_process");

console.log("Starting Laravel Queue Worker...");

const queue = spawn("php", [
  "artisan",
  "queue:work",
  "--tries=3",
  "--timeout=0",
  "--sleep=5"
], {
  cwd: __dirname + "/..",  // 確保在 backendapi 根目錄下執行
  detached: true,
  stdio: "ignore"
});

queue.unref();