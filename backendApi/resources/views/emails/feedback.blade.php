<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>使用者問題反饋</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            border-left: 5px solid #007BFF;
        }
        h2 {
            color: #007BFF;
            text-align: center;
            margin-bottom: 20px;
        }
        .info {
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
            margin-bottom: 10px;
            border-left: 3px solid #007BFF;
        }
        .info p {
            margin: 8px 0;
        }
        .label {
            font-weight: bold;
            color: #007BFF;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }
        .btn {
            display: inline-block;
            background-color:rgb(204, 85, 212);
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            margin-top: 20px;
            text-align: center;
        }
        .btn:hover {
            background-color:rgb(183, 18, 208);
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>📩 使用者問題反饋</h2>

        <div class="info">
            <p><span class="label">姓名：</span> {{ $details['name'] }}</p>
            <p><span class="label">電子郵件：</span> {{ $details['email'] }}</p>
            <p><span class="label">問題類型：</span> {{ $details['issueType'] }}</p>
            <p><span class="label">詳細描述：</span></p>
            <p>{{ $details['message'] }}</p>
        </div>

        <p class="footer">若有進一步問題，請回覆此郵件。</p>
        <p style="text-align: center;">
            <a href="mailto:{{ $details['email'] }}" class="btn">回覆使用者</a>
        </p>
    </div>
</body>
</html>
