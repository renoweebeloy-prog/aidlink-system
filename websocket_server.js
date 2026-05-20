
const WebSocket = require('ws');
const amqp = require('amqplib');

const PORT = 8081;
const BASE_QUEUE = 'aidlink_messenger_queue_user_';
const DELAY_MS = 3000;
const CHECK_INTERVAL_MS = 1000;

const wss = new WebSocket.Server({ port: PORT });
const onlineUsers = new Map();
const queueIntervals = new Map();
let connection = null;
let channel = null;
let connecting = false;

console.log('AidLink WebSocket server running on ws://localhost:' + PORT);

function userQueue(userId) {
    return BASE_QUEUE + Number(userId);
}

async function ensureRabbitMQ() {
    if (channel) return channel;
    if (connecting) {
        while (connecting) await new Promise(r => setTimeout(r, 100));
        if (channel) return channel;
    }
    connecting = true;
    connection = await amqp.connect('amqp://guest:guest@localhost');
    channel = await connection.createChannel();
    channel.on('error', err => { console.error('RabbitMQ channel error:', err.message); channel = null; });
    channel.on('close', () => { console.error('RabbitMQ channel closed.'); channel = null; });
    connecting = false;
    return channel;
}

async function checkUserQueue(userId) {
    const client = onlineUsers.get(Number(userId));
    if (!client || client.readyState !== WebSocket.OPEN) return;

    const ch = await ensureRabbitMQ();
    const q = userQueue(userId);
    await ch.assertQueue(q, { durable: true });

    const msg = await ch.get(q, { noAck: false });
    if (!msg) return;

    let payload;
    try {
        payload = JSON.parse(msg.content.toString());
    } catch (error) {
        console.log('Invalid message dropped from', q);
        ch.ack(msg);
        return;
    }

    console.log('Message found in', q, 'waiting', DELAY_MS + 'ms');
    await new Promise(resolve => setTimeout(resolve, DELAY_MS));

    const latestClient = onlineUsers.get(Number(userId));
    if (!latestClient || latestClient.readyState !== WebSocket.OPEN) {
        console.log('User went offline during delay. Message returned to READY:', q);
        ch.nack(msg, false, true);
        return;
    }

    latestClient.send(JSON.stringify({ type: 'message', payload }));
    ch.ack(msg);
    console.log('Message pushed and ACKed from', q);
}

function startUserQueue(userId) {
    userId = Number(userId);
    if (queueIntervals.has(userId)) return;
    const interval = setInterval(() => {
        checkUserQueue(userId).catch(error => console.error('Queue check error for user ' + userId + ':', error.message));
    }, CHECK_INTERVAL_MS);
    queueIntervals.set(userId, interval);
}

function stopUserQueue(userId) {
    userId = Number(userId);
    const interval = queueIntervals.get(userId);
    if (interval) clearInterval(interval);
    queueIntervals.delete(userId);
}

wss.on('connection', ws => {
    ws.userId = null;
    ws.on('message', message => {
        try {
            const data = JSON.parse(message.toString());
            if (data.type === 'register' && data.user_id) {
                ws.userId = Number(data.user_id);
                onlineUsers.set(ws.userId, ws);
                console.log('Online user registered:', ws.userId);
                startUserQueue(ws.userId);
            }
        } catch (error) {
            console.error('Invalid WebSocket registration:', error.message);
        }
    });
    ws.on('close', () => {
        if (ws.userId) {
            onlineUsers.delete(ws.userId);
            stopUserQueue(ws.userId);
            console.log('User offline:', ws.userId);
        }
    });
});

ensureRabbitMQ().then(() => console.log('RabbitMQ connected. Per-user messenger queues ready.')).catch(error => console.error('RabbitMQ setup failed:', error.message));
