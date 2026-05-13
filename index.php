<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>STATIC LINE</title>

<style>
*{
  margin:0;
  padding:0;
  box-sizing:border-box;
}

body{
  overflow:hidden;
  background:black;
  font-family:monospace;
}

canvas{
  display:block;
}

#menu{
  position:fixed;
  inset:0;
  background:black;
  display:flex;
  flex-direction:column;
  justify-content:center;
  align-items:center;
  gap:20px;
  z-index:100;
  color:white;
}

#menu h1{
  font-size:70px;
  color:#22ffaa;
}

.menuBox{
  display:flex;
  flex-direction:column;
  gap:12px;
  width:320px;
}

.menuBox input{
  padding:12px;
  background:#111;
  border:2px solid #22ffaa;
  color:white;
  font-size:16px;
}

.menuBox button{
  padding:12px;
  background:#22ffaa;
  border:none;
  font-size:16px;
  cursor:pointer;
  font-weight:bold;
}

#partyInfo{
  color:#22ffaa;
  text-align:center;
  min-height:180px;
  line-height:1.6;
}

#chatBox{
  position:fixed;
  top:20px;
  left:20px;
  width:320px;
  height:220px;
  background:rgba(0,0,0,0.82);
  border:2px solid #22ffaa;
  padding:12px;
  overflow:hidden;
  z-index:20;
}

#messages{
  display:flex;
  flex-direction:column;
  gap:6px;
  color:#22ffaa;
  font-size:14px;
}

#chatInput{
  position:fixed;
  bottom:20px;
  left:50%;
  transform:translateX(-50%);
  width:420px;
  padding:12px;
  background:rgba(0,0,0,0.92);
  border:2px solid #22ffaa;
  color:white;
  font-size:16px;
  outline:none;
  display:none;
  z-index:30;
}

#jumpscare{
  position:fixed;
  inset:0;
  background:black;
  display:flex;
  justify-content:center;
  align-items:center;
  color:red;
  font-size:120px;
  font-weight:bold;
  opacity:0;
  pointer-events:none;
  z-index:999;
  transition:0.15s;
}
</style>
</head>
<body>

<div id="menu">

  <h1>STATIC LINE</h1>

  <div class="menuBox">

    <input
      id="username"
      placeholder="USERNAME"
      maxlength="12"
    >

    <button id="createPartyBtn">
      CREATE PARTY
    </button>

    <input
      id="partyCodeInput"
      placeholder="ENTER 3 DIGIT CODE"
      maxlength="3"
    >

    <button id="joinPartyBtn">
      JOIN PARTY
    </button>

    <div id="partyInfo">
      NOT CONNECTED
    </div>

  </div>

</div>

<div id="chatBox">
  <div id="messages"></div>
</div>

<input
  id="chatInput"
  placeholder="TYPE MESSAGE..."
  maxlength="80"
>

<div id="jumpscare">
  YOU DIED
</div>

<canvas id="game"></canvas>

<script>
const canvas =
  document.getElementById("game");

const ctx =
  canvas.getContext("2d");

let width = innerWidth;
let height = innerHeight;

canvas.width = width;
canvas.height = height;

window.addEventListener("resize",() => {

  width = innerWidth;
  height = innerHeight;

  canvas.width = width;
  canvas.height = height;
});

ctx.imageSmoothingEnabled = true;

const TILE = 64;

const map = [
"####################",
"#..................#",
"#....######........#",
"#..................#",
"#..........####....#",
"#..................#",
"#....######........#",
"#..................#",
"#..........####....#",
"#..................#",
"#....######........#",
"#..................#",
"#..........####....#",
"#..................#",
"#..................#",
"####################"
];

const player = {
  x:TILE*2,
  y:TILE*2,
  angle:0,
  speed:2.5,
  username:"PLAYER"
};

const monster = {
  x:TILE*14,
  y:TILE*10,
  speed:1
};

let currentParty = null;
let isHost = false;

let connectedPlayers = 1;

let playerList = [];

let gameStarted = false;
let dead = false;

let lastWarningTime = 0;

const keys = {};

const messages =
  document.getElementById("messages");

const chatInput =
  document.getElementById("chatInput");

const jumpscare =
  document.getElementById("jumpscare");

const menu =
  document.getElementById("menu");

const partyInfo =
  document.getElementById("partyInfo");

const beginBtn =
  document.createElement("button");

beginBtn.textContent =
  "BEGIN GAME";

beginBtn.style.display =
  "none";

beginBtn.style.background =
  "#ff4444";

beginBtn.style.color =
  "white";

beginBtn.style.padding =
  "12px";

beginBtn.style.fontSize =
  "16px";

beginBtn.style.border =
  "none";

beginBtn.style.cursor =
  "pointer";

document
.querySelector(".menuBox")
.appendChild(beginBtn);

function addMessage(
  name,
  text,
  color="#22ffaa"
){

  const div =
    document.createElement("div");

  div.textContent =
    `[${name}]: ${text}`;

  div.style.color = color;

  messages.appendChild(div);

  while(messages.children.length > 6){

    messages.removeChild(
      messages.firstChild
    );
  }
}

function generatePartyCode(){

  return Math.floor(
    100 + Math.random() * 900
  ).toString();
}

function updatePartyStatus(){

  const playersHTML =
    playerList
    .map(name => `• ${name}`)
    .join("<br>");

  if(isHost){

    if(connectedPlayers <= 1){

      partyInfo.innerHTML =
      `
      PARTY CREATED: ${currentParty}
      <br>
      STATUS: HOST
      <br><br>

      PLAYERS:
      <br><br>

      ${playersHTML}

      <br><br>

      PLAYING SOLO
      `;

    }else{

      partyInfo.innerHTML =
      `
      PARTY CREATED: ${currentParty}
      <br>
      STATUS: HOST
      <br><br>

      PLAYERS:
      <br><br>

      ${playersHTML}
      `;
    }

  }else{

    partyInfo.innerHTML =
    `
    CONNECTED TO PARTY: ${currentParty}
    <br>
    STATUS: PLAYER
    <br><br>

    PLAYERS:
    <br><br>

    ${playersHTML}

    <br><br>

    WAITING FOR HOST...
    `;
  }
}

function startGame(){

  menu.style.display = "none";

  canvas.requestPointerLock();

  gameStarted = true;

  addMessage(
    "SYSTEM",
    `connected to party ${currentParty}`
  );

  if(connectedPlayers <= 1){

    addMessage(
      "SYSTEM",
      "no real players joined — solo session started",
      "#ffaa00"
    );

  }else{

    addMessage(
      "SYSTEM",
      `${connectedPlayers} real players connected`,
      "#22ffaa"
    );
  }

  addMessage(
    "SYSTEM",
    "press ENTER to communicate"
  );

  gameLoop();
}

document
.getElementById("createPartyBtn")
.addEventListener("click",() => {

  const username =
    document
    .getElementById("username")
    .value
    .trim();

  if(username.length > 0){

    player.username =
      username.toUpperCase();
  }

  currentParty =
    generatePartyCode();

  isHost = true;

  connectedPlayers = 1;

  playerList = [
    player.username
  ];

  updatePartyStatus();

  beginBtn.style.display =
    "block";
});

document
.getElementById("joinPartyBtn")
.addEventListener("click",() => {

  const username =
    document
    .getElementById("username")
    .value
    .trim();

  if(username.length > 0){

    player.username =
      username.toUpperCase();
  }

  const code =
    document
    .getElementById("partyCodeInput")
    .value
    .trim();

  if(code.length === 3){

    currentParty = code;

    isHost = false;

    connectedPlayers++;

    playerList.push(
      player.username
    );

    updatePartyStatus();

    beginBtn.style.display =
      "none";
  }
});

beginBtn.addEventListener(
  "click",
  () => {

  if(isHost){

    startGame();
  }
});

function wallAt(x,y){

  const mx =
    Math.floor(x / TILE);

  const my =
    Math.floor(y / TILE);

  if(
    !map[my] ||
    !map[my][mx]
  ){
    return true;
  }

  return map[my][mx] === "#";
}

function updatePlayer(){

  let moveX = 0;
  let moveY = 0;

  const cos =
    Math.cos(player.angle);

  const sin =
    Math.sin(player.angle);

  if(keys["w"]){

    moveX +=
      cos * player.speed;

    moveY +=
      sin * player.speed;
  }

  if(keys["s"]){

    moveX -=
      cos * player.speed;

    moveY -=
      sin * player.speed;
  }

  if(keys["a"]){

    moveX +=
      sin * player.speed;

    moveY -=
      cos * player.speed;
  }

  if(keys["d"]){

    moveX -=
      sin * player.speed;

    moveY +=
      cos * player.speed;
  }

  const nextX =
    player.x + moveX;

  const nextY =
    player.y + moveY;

  if(
    !wallAt(nextX,player.y)
  ){
    player.x = nextX;
  }

  if(
    !wallAt(player.x,nextY)
  ){
    player.y = nextY;
  }
}

function updateMonster(){

  const dx =
    player.x - monster.x;

  const dy =
    player.y - monster.y;

  const dist =
    Math.sqrt(dx*dx + dy*dy);

  monster.x +=
    (dx / dist) *
    monster.speed;

  monster.y +=
    (dy / dist) *
    monster.speed;

  if(dist < 500){

    const now = Date.now();

    if(
      now - lastWarningTime
      > 8000
    ){

      const warnings = [
        "heavy footsteps nearby",
        "something is following you",
        "movement detected nearby",
        "you hear breathing close by",
        "the monster is hunting"
      ];

      const randomWarning =
        warnings[
          Math.floor(
            Math.random()
            * warnings.length
          )
        ];

      addMessage(
        "SYSTEM",
        randomWarning,
        "#ffaa00"
      );

      lastWarningTime = now;
    }
  }

  if(
    dist < 220 &&
    !dead
  ){

    dead = true;

    jumpscare.style.opacity =
      "1";

    setTimeout(() => {

      location.reload();

    },3000);
  }
}

function drawWorld(){

  const sky =
    ctx.createLinearGradient(
      0,
      0,
      0,
      height/2
    );

  sky.addColorStop(
    0,
    "#050505"
  );

  sky.addColorStop(
    1,
    "#111"
  );

  ctx.fillStyle = sky;

  ctx.fillRect(
    0,
    0,
    width,
    height/2
  );

  const floor =
    ctx.createLinearGradient(
      0,
      height/2,
      0,
      height
    );

  floor.addColorStop(
      0,
      "#2a0000"
  );

  floor.addColorStop(
      1,
      "#550000"
  );

  ctx.fillStyle = floor;

  ctx.fillRect(
    0,
    height/2,
    width,
    height/2
  );
}

function castRays(){

  const fov =
    Math.PI / 3;

  const rays =
    width;

  for(
    let i=0;
    i<rays;
    i++
  ){

    const rayAngle =
      player.angle -
      fov/2 +
      (i/rays) * fov;

    let distance = 0;
    let hit = false;

    while(
      !hit &&
      distance < 1000
    ){

      distance += 2;

      const rx =
        player.x +
        Math.cos(rayAngle)
        * distance;

      const ry =
        player.y +
        Math.sin(rayAngle)
        * distance;

      const mx =
        Math.floor(rx / TILE);

      const my =
        Math.floor(ry / TILE);

      if(
        map[my] &&
        map[my][mx] === "#"
      ){

        hit = true;
      }
    }

    if(!hit) continue;

    const corrected =
      distance *
      Math.cos(
        rayAngle -
        player.angle
      );

    const wallHeight =
      Math.min(
        32000 / corrected,
        height
      );

    const x = i;

    const y =
      height/2 -
      wallHeight/2;

    const gradient =
      ctx.createLinearGradient(
        0,
        y,
        0,
        y + wallHeight
      );

    gradient.addColorStop(
      0,
      "#5b3a1f"
    );

    gradient.addColorStop(
      0.5,
      "#3d2614"
    );

    gradient.addColorStop(
      1,
      "#24150c"
    );

    ctx.fillStyle =
      gradient;

    ctx.fillRect(
      x,
      y,
      2,
      wallHeight
    );
  }
}

function drawMonster(){

  const dx =
    monster.x - player.x;

  const dy =
    monster.y - player.y;

  const distance =
    Math.sqrt(
      dx*dx + dy*dy
    );

  const angle =
    Math.atan2(dy,dx)
    - player.angle;

  if(
    distance < 700 &&
    Math.abs(angle) < 0.6
  ){

    const size =
      22000 / distance;

    const screenX =
      width/2 +
      Math.tan(angle)
      * 700;

    const bodyY =
      height/2 -
      size/1.7;

    ctx.fillStyle =
      "rgba(0,0,0,0.95)";

    ctx.beginPath();

    ctx.moveTo(
      screenX,
      bodyY
    );

    ctx.lineTo(
      screenX - size*0.7,
      bodyY + size*1.8
    );

    ctx.lineTo(
      screenX + size*0.7,
      bodyY + size*1.8
    );

    ctx.closePath();

    ctx.fill();

    ctx.fillStyle = "red";

    ctx.beginPath();

    ctx.arc(
      screenX - size*0.12,
      bodyY + size*0.35,
      size*0.1,
      0,
      Math.PI*2
    );

    ctx.fill();

    ctx.beginPath();

    ctx.arc(
      screenX + size*0.12,
      bodyY + size*0.35,
      size*0.1,
      0,
      Math.PI*2
    );

    ctx.fill();
  }
}

function gameLoop(){

  if(
    !gameStarted ||
    dead
  ){
    return;
  }

  ctx.clearRect(
    0,
    0,
    width,
    height
  );

  updatePlayer();

  updateMonster();

  drawWorld();

  castRays();

  drawMonster();

  requestAnimationFrame(
    gameLoop
  );
}

document.addEventListener(
  "keydown",
  e => {

  keys[
    e.key.toLowerCase()
  ] = true;

  if(e.key === "Enter"){

    if(
      chatInput.style.display
      === "block"
    ){

      const value =
        chatInput.value.trim();

      if(value.length > 0){

        addMessage(
          player.username,
          value
        );
      }

      chatInput.value = "";

      chatInput.style.display =
        "none";

      canvas.requestPointerLock();

    }else{

      chatInput.style.display =
        "block";

      chatInput.focus();

      document.exitPointerLock();
    }
  }
});

document.addEventListener(
  "keyup",
  e => {

  keys[
    e.key.toLowerCase()
  ] = false;
});

document.addEventListener(
  "mousemove",
  e => {

  if(
    document.pointerLockElement
    === canvas
  ){

    player.angle +=
      e.movementX * 0.002;
  }
});
</script>

</body>
</html>