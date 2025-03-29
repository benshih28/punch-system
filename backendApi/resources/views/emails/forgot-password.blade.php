<!DOCTYPE html>
<html>
<head>
    <title>忘記密碼通知</title>
</head>
<body>
    <p>您的新密碼是： <strong>{{ $newPassword }}</strong></p>
    <p>請點擊以下連結登入</p>
    <a href="{{ $loginUrl }}" style="display: inline-block; padding: 10px 20px; background: #8B5E3B; color: white; text-decoration: none;">立即登入</a>
    <p>如果您沒有請求此密碼重設，請忽略此郵件。</p>

</body>
</html>