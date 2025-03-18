<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>使用者問題反饋</title>
</head>
<body>
    <h2>使用者問題反饋</h2>
    <p><strong>姓名：</strong> {{ $details['name'] }}</p>
    <p><strong>電子郵件：</strong> {{ $details['email'] }}</p>
    <p><strong>問題類型：</strong> {{ $details['issueType'] }}</p>
    <p><strong>詳細描述：</strong> {{ $details['message'] }}</p>
</body>
</html>
