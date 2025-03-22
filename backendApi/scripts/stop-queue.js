const { exec } = require("child_process");

exec('wmic process where "CommandLine like \'%queue:work%\'" get ProcessId,CommandLine /FORMAT:CSV', (err, stdout) => {
  const lines = stdout.trim().split('\n').slice(1);
  lines.forEach(line => {
    const parts = line.trim().split(',');
    const cmd = parts[1];
    const pid = parts[2];

    if (pid && cmd.includes('queue:work')) {
      exec(`taskkill /F /PID ${pid}`, () => {
        console.log(`✅ Killed Laravel queue worker (PID ${pid})`);
      });
    }
  });

  if (lines.length <= 1) {
    console.log("ℹ️ No Laravel queue workers running.");
  }
});
