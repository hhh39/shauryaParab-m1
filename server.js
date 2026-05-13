const express = require("express");
const http = require("http");
const { Server } = require("socket.io");

const app = express();

const server = http.createServer(app);

const io = new Server(server, {
  cors: {
    origin: "*"
  }
});

app.use(express.static("public"));

const PORT = process.env.PORT || 3000;

const parties = new Map();

const CODE_CHARS =
  "ABCDEFGHJKLMNPQRSTUVWXYZ123456789";

function generateCode(length = 6) {

  let code = "";

  for (let i = 0; i < length; i++) {

    code += CODE_CHARS[
      Math.floor(Math.random() * CODE_CHARS.length)
    ];
  }

  return code;
}

function createUniqueCode() {

  let tries = 0;

  while (tries < 1000) {

    const code = generateCode();

    if (!parties.has(code)) {
      return code;
    }

    tries++;
  }

  throw new Error("FAILED TO GENERATE CODE");
}

function getParty(socket) {

  if (!socket.partyCode) {
    return null;
  }

  return parties.get(socket.partyCode);
}

function sendPlayerList(code) {

  const party = parties.get(code);

  if (!party) return;

  io.to(code).emit(
    "players",
    Array.from(party.players.values())
  );
}

function createPlayer(id, username) {

  return {
    id,
    username,
    x: 128,
    y: 128,
    angle: 0
  };
}

io.on("connection", socket => {

  console.log("CONNECTED:", socket.id);

  socket.on("createParty", username => {

    try {

      if (typeof username !== "string") {
        return;
      }

      username = username
        .trim()
        .toUpperCase()
        .slice(0, 16);

      if (!username) {
        return;
      }

      const code = createUniqueCode();

      const party = {
        admin: socket.id,
        players: new Map()
      };

      party.players.set(
        socket.id,
        createPlayer(socket.id, username)
      );

      parties.set(code, party);

      socket.partyCode = code;

      socket.join(code);

      socket.emit("partyCreated", code);

      sendPlayerList(code);

    } catch (err) {

      console.error(err);

      socket.emit("serverError");
    }
  });

  socket.on("joinParty", data => {

    if (!data) return;

    const code =
      String(data.code || "")
      .trim()
      .toUpperCase();

    const username =
      String(data.username || "")
      .trim()
      .toUpperCase()
      .slice(0, 16);

    const party = parties.get(code);

    if (!party) {

      socket.emit("invalidParty");

      return;
    }

    party.players.set(
      socket.id,
      createPlayer(socket.id, username)
    );

    socket.partyCode = code;

    socket.join(code);

    socket.emit("partyJoined", code);

    sendPlayerList(code);
  });

  socket.on("startPartyGame", () => {

    const party = getParty(socket);

    if (!party) return;

    if (party.admin !== socket.id) {
      return;
    }

    io.to(socket.partyCode)
      .emit("gameStarted");
  });

  socket.on("move", data => {

    const party = getParty(socket);

    if (!party) return;

    const player =
      party.players.get(socket.id);

    if (!player || !data) return;

    if (
      typeof data.x !== "number" ||
      typeof data.y !== "number" ||
      typeof data.angle !== "number"
    ) {
      return;
    }

    player.x = data.x;
    player.y = data.y;
    player.angle = data.angle;

    socket.to(socket.partyCode)
      .emit("playerMoved", player);
  });

  socket.on("chat", text => {

    const party = getParty(socket);

    if (!party) return;

    const player =
      party.players.get(socket.id);

    if (!player) return;

    text = String(text || "")
      .trim()
      .slice(0, 120);

    if (!text) return;

    io.to(socket.partyCode).emit(
      "chat",
      {
        username: player.username,
        text
      }
    );
  });

  socket.on("disconnect", () => {

    console.log("DISCONNECTED:", socket.id);

    const code = socket.partyCode;

    if (!code) return;

    const party = parties.get(code);

    if (!party) return;

    party.players.delete(socket.id);

    if (party.admin === socket.id) {

      const nextPlayer =
        Array.from(
          party.players.keys()
        )[0];

      if (nextPlayer) {
        party.admin = nextPlayer;
      }
    }

    if (party.players.size === 0) {

      parties.delete(code);

      return;
    }

    sendPlayerList(code);
  });
});

app.get("/", (req, res) => {
  res.sendFile(__dirname + "/public/index.html");
});

server.listen(PORT, () => {

  console.log(
    `SERVER RUNNING ON ${PORT}`
  );
});
