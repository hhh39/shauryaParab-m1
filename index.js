// script.js

const shellContainer =
  document.getElementById(
    "shellContainer"
  );

const messageBox =
  document.getElementById(
    "messageBox"
  );

const flash =
  document.getElementById(
    "flash"
  );

const shotgun =
  document.getElementById(
    "shotgun"
  );

const playerHealthText =
  document.getElementById(
    "playerHealth"
  );

const dealerHealthText =
  document.getElementById(
    "dealerHealth"
  );

const gameOver =
  document.getElementById(
    "gameOver"
  );

const gameOverText =
  document.getElementById(
    "gameOverText"
  );

let playerHealth = 4;
let dealerHealth = 4;

let shells = [];

let gameEnded = false;

function setMessage(text){

  messageBox.textContent =
    text;
}

function updateHealth(){

  playerHealthText.textContent =
    "HEALTH: " + playerHealth;

  dealerHealthText.textContent =
    "HEALTH: " + dealerHealth;
}

function renderShells(){

  shellContainer.innerHTML = "";

  shells.forEach(shell => {

    const div =
      document.createElement("div");

    div.className =
      "shell " +
      (shell ? "live" : "blank");

    shellContainer.appendChild(div);
  });
}

function reloadGun(){

  shells = [];

  const amount =
    Math.floor(
      Math.random() * 4
    ) + 4;

  for(let i=0;i<amount;i++){

    shells.push(
      Math.random() > 0.45
    );
  }

  shells.sort(
    () => Math.random() - 0.5
  );

  renderShells();

  setMessage(
    "THE SHOTGUN HAS BEEN RELOADED"
  );
}

function flashScreen(){

  flash.style.opacity = "1";

  setTimeout(() => {

    flash.style.opacity = "0";

  },80);
}

function recoil(){

  shotgun.style.transform =
    "translateX(-60px)";

  setTimeout(() => {

    shotgun.style.transform =
      "translateX(0px)";

  },120);
}

function endGame(text){

  gameEnded = true;

  gameOver.style.display =
    "flex";

  gameOverText.textContent =
    text;
}

function fire(target){

  if(gameEnded) return;

  if(shells.length <= 0){

    setMessage(
      "NO SHELLS LOADED"
    );

    return;
  }

  const shell =
    shells.shift();

  renderShells();

  flashScreen();

  recoil();

  if(shell){

    if(target === "dealer"){

      dealerHealth--;

      setMessage(
        "LIVE ROUND — DEALER HIT"
      );

    }else{

      playerHealth--;

      setMessage(
        "LIVE ROUND — YOU SHOT YOURSELF"
      );
    }

  }else{

    setMessage(
      "BLANK SHELL"
    );
  }

  updateHealth();

  if(playerHealth <= 0){

    endGame("YOU DIED");

    return;
  }

  if(dealerHealth <= 0){

    endGame("YOU WIN");

    return;
  }

  setTimeout(() => {

    dealerTurn();

  },1300);
}

function dealerTurn(){

  if(gameEnded) return;

  if(shells.length <= 0){

    reloadGun();

    return;
  }

  const target =
    Math.random() > 0.5
    ? "player"
    : "self";

  const shell =
    shells.shift();

  renderShells();

  flashScreen();

  recoil();

  if(shell){

    if(target === "player"){

      playerHealth--;

      setMessage(
        "DEALER FIRED AT YOU"
      );

    }else{

      dealerHealth--;

      setMessage(
        "DEALER SHOT HIMSELF"
      );
    }

  }else{

    setMessage(
      "DEALER FIRED A BLANK"
    );
  }

  updateHealth();

  if(playerHealth <= 0){

    endGame("YOU DIED");
  }

  if(dealerHealth <= 0){

    endGame("YOU WIN");
  }
}

document
.getElementById(
  "shootDealer"
)
.addEventListener(
  "click",
  () => fire("dealer")
);

document
.getElementById(
  "shootSelf"
)
.addEventListener(
  "click",
  () => fire("self")
);

document
.getElementById(
  "reloadBtn"
)
.addEventListener(
  "click",
  reloadGun
);

document
.getElementById(
  "restartBtn"
)
.addEventListener(
  "click",
  () => location.reload()
);

reloadGun();

updateHealth();