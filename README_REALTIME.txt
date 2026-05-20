AIDLINK REALTIME RABBITMQ MESSENGER

Messenger queue:
aidlink_messenger_queue

Start realtime relay:
1. Open CMD inside aidlink/realtime.
2. Run:
   npm install
3. Run:
   node websocket_server.js

Demo flow:
1. Open RabbitMQ Management: http://localhost:15672
2. Login guest / guest.
3. Open queue aidlink_messenger_queue.
4. Send a message in AidLink Messenger.
5. The queue count should increase.
6. After DELAY_MS in websocket_server.js, the relay consumes it and count decreases.

Change delay:
Open realtime/websocket_server.js and edit:

const DELAY_MS = 2500;

2500 = 2.5 seconds
5000 = 5 seconds
1000 = 1 second
