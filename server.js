const express = require("express");
const http = require("http");
const { Server } = require("socket.io");

const app = express();
const server = http.createServer(app);
const io = new Server(server, { cors: { origin: "*" } });

app.use(express.static("public"));

const PORT = process.env.PORT || 3000;

const parties = new Map();

const CHARS = "ABCDEFGHJKLMNPQRSTUVWXYZ123456789";

function generateCode(){
  let code = "";
  for(let i=0;i<6;i++){
    code += CHARS[Math.floor(Math.random()*CHARS.length)];
  }
  return code;
}

function createCode(){
  let code;
  do{
    code = generateCode();
  } while(parties.has(code));
  return code;
}

function getParty(socket){
  return socket.partyCode ? parties.get(socket.partyCode) : null;
}

io.on("connection",(socket)=>{

  socket.on("createParty",(username)=>{

    username = String(username||"").trim().toUpperCase();
    if(!username) return;

    const code = createCode();

    parties.set(code,{
      admin: socket.id,
      players: new Map()
    });

    const party = parties.get(code);

    party.players.set(socket.id,{
      id: socket.id,
      username,
      x:128,
      y:128,
      angle:0,
      alive:true
    });

    socket.partyCode = code;
    socket.join(code);

    socket.emit("partyCreated",code);
    io.to(code).emit("players",[...party.players.values()]);
  });

  socket.on("joinParty",(data)=>{

    const username = String(data?.username||"").trim().toUpperCase();
    const code = String(data?.code||"").trim().toUpperCase();

    const party = parties.get(code);
    if(!party) return socket.emit("invalidParty");

    party.players.set(socket.id,{
      id: socket.id,
      username,
      x:128,
      y:128,
      angle:0,
      alive:true
    });

    socket.partyCode = code;
    socket.join(code);

    socket.emit("partyJoined",code);
    io.to(code).emit("players",[...party.players.values()]);
  });

  socket.on("startPartyGame",()=>{

    const party = getParty(socket);
    if(!party) return;
    if(party.admin !== socket.id) return;

    io.to(socket.partyCode).emit("gameStarted");
  });

  socket.on("move",(data)=>{

    const party = getParty(socket);
    if(!party) return;

    const player = party.players.get(socket.id);
    if(!player || !player.alive) return;

    player.x = data.x;
    player.y = data.y;
    player.angle = data.angle;

    socket.to(socket.partyCode).emit("playerMoved",{
      id: socket.id,
      x: player.x,
      y: player.y,
      angle: player.angle
    });
  });

  socket.on("died",()=>{

    const party = getParty(socket);
    if(!party) return;

    const player = party.players.get(socket.id);
    if(!player) return;

    player.alive = false;

    io.to(socket.partyCode).emit("players",[...party.players.values()]);
  });

  socket.on("chat",(text)=>{

    const party = getParty(socket);
    if(!party) return;

    const player = party.players.get(socket.id);
    if(!player) return;

    io.to(socket.partyCode).emit("chat",{
      username: player.username,
      text: String(text).slice(0,120)
    });
  });

  socket.on("disconnect",()=>{

    const party = getParty(socket);
    if(!party) return;

    party.players.delete(socket.id);

    if(party.players.size === 0){
      parties.delete(socket.partyCode);
      return;
    }

    io.to(socket.partyCode).emit("players",[...party.players.values()]);
  });

});

server.listen(PORT,()=>{
  console.log("RUNNING ON",PORT);
});
