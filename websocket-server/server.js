const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const jwt = require('jsonwebtoken');
const cors = require('cors');

const JWT_SECRET = '你的 Laravel JWT_SECRET';

const app = express();
app.use(cors());
app.use(express.json());

const server = http.createServer(app);
const io = new Server(server, {
    cors: { origin: '*' }
});

// JWT 驗證中介層
io.use((socket, next) => {
    const token = socket.handshake.auth.token;
    try {
        const user = jwt.verify(token, JWT_SECRET);
        socket.user = user;
        next();
    } catch (err) {
        console.log('❌ JWT 驗證失敗:', err.message);
        next(new Error('Unauthorized'));
    }
});

// 連線成功後加入房間
io.on('connection', (socket) => {
    const userId = socket.user.sub;
    socket.join(`user.${userId}`);

    socket.on('disconnect', () => {
        // console.log(`❌ User disconnected: ${userId}`);
    });
});

// Laravel 廣播 POST 接收端
app.post('/notify', (req, res) => {
    const { id, userId, title, message, link } = req.body;
    io.to(`user.${userId}`).emit('notification', {
        id,
        title,
        message,
        link
    });
    res.json({ status: 'sent' });
});

server.listen(6001, () => {
    console.log('🚀 WebSocket server running on port 6001');
});

