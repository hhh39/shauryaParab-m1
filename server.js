const express = require('express');
const http = require('http');
const { Server } = require('socket.io');

const app = express();

const server = http.createServer(app);

const io = new Server(server,{
  cors:{
    origin:"*"
  }
});

app.use(express.static('public'));

const parties = {};

function generateCode(){

  const chars =
    'ABCDEFGHJKLMNPQRSTUVWXYZ123456789';

  let code = '';

  for(let i=0;i<6;i++){

    code += chars[
      Math.floor(Math.random()*chars.length)
    ];
  }

  return code;
}

io.on('connection',socket=>{

  console.log('PLAYER CONNECTED');

  socket.on('createParty',username=>{

    let code = generateCode();

    while(parties[code]){
      code = generateCode();
    }

    parties[code] = {
      admin:socket.id,
      players:{}
    };

    parties[code].players[socket.id] = {
      id:socket.id,
      username,
      x:128,
      y:128,
      angle:0
    };

    socket.join(code);

    socket.partyCode = code;

    socket.emit('partyCreated',code);

    io.to(code).emit(
      'players',
      Object.values(parties[code].players)
    );
  });

  socket.on('joinParty',data=>{

    const code = data.code.toUpperCase();

    if(!parties[code]){

      socket.emit('invalidParty');

      return;
    }

    parties[code].players[socket.id] = {
      id:socket.id,
      username:data.username,
      x:128,
      y:128,
      angle:0
    };

    socket.join(code);

    socket.partyCode = code;

    socket.emit('partyJoined',code);

    io.to(code).emit(
      'players',
      Object.values(parties[code].players)
    );
  });

  socket.on('startPartyGame',()=>{

    const code = socket.partyCode;

    if(!code || !parties[code]) return;

    if(parties[code].admin !== socket.id) return;

    io.to(code).emit('gameStarted');
  });

  socket.on('move',data=>{

    const code = socket.partyCode;

    if(!code || !parties[code]) return;

    const player =
      parties[code].players[socket.id];

    if(!player) return;

    player.x = data.x;
    player.y = data.y;
    player.angle = data.angle;

    socket.to(code).emit(
      'playerMoved',
      player
    );
  });

  socket.on('chat',text=>{

    const code = socket.partyCode;

    if(!code || !parties[code]) return;

    const player =
      parties[code].players[socket.id];

    io.to(code).emit('chat',{
      username:player.username,
      text
    });
  });

  socket.on('disconnect',()=>{

    console.log('PLAYER DISCONNECTED');

    const code = socket.partyCode;

    if(!code || !parties[code]) return;

    delete parties[code].players[socket.id];

    io.to(code).emit(
      'players',
      Object.values(parties[code].players)
    );

    if(
      Object.keys(parties[code].players).length
      === 0
    ){
      delete parties[code];
    }
  });
});

const PORT = process.env.PORT || 3000;

server.listen(PORT,()=>{
  console.log(`SERVER RUNNING ON ${PORT}`);
});