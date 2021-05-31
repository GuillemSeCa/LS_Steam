class Server {

    constructor(baseUrl) {
        this.baseUrl = baseUrl;
    }

    request(endpoint,method) {
        var self = this;

        // Guardem l'objecte per a fer-lo servir a l'interior de la funció de la promise.
        return new Promise(function (resolve, reject) {

            var xhr = new XMLHttpRequest();

            xhr.open(method === undefined ? "POST" : method, self.baseUrl + endpoint, true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            xhr.onload = function () {
                if (xhr.readyState === 4) {
                    resolve({
                        status: this.status,
                        text: this.responseText
                    });
                }
            };

            xhr.onerror = function () {

                console.log("Code: Request text: " + xhr.response);
                console.log("Code: Request status: " + this.status);
                console.log("Code: Request status: " + xhr.statusText);

                reject({
                    status: this.status,
                    statusText: xhr.statusText
                });
            };

            xhr.send(null);
        });
    }
}

const DEBUG = true;

var server = new Server("");

var buy_buttons = document.getElementsByName('BUY_BUTTON');

// # Permet eliminar un element de la generic game display
function fadeAwayFromChildItem(pr) {

    if( document.querySelectorAll(".gameContainer").length - document.querySelectorAll(".gameContainer.ksk").length === 1)
    {
        window.location.reload(false);
    }else{
        pr.classList.add("kska");
        pr.addEventListener("animationend",function (){
            pr.classList.add("ksk");
            pr.classList.remove("kska");
        })
    }


}

if (buy_buttons !== undefined) {
    buy_buttons.forEach(function (button, i, a){
        button.addEventListener("click", function () {
            if (DEBUG) {
                console.log("Buying game " + button.dataset.gameid)
            }
            button.classList.add('is-loading')
            server.request("/store/buy/" + button.dataset.gameid).then((resp) => {
                if(resp.status === 200){
                    // All ok. Buy just fine.
                    // Actualitzem el boto a Owned.
                    button.classList.remove('is-loading')
                    button.classList.remove('is-success')
                    button.innerText = "Owned";
                    button.disabled = true;

                    // Borrem el boto de fav.
                    button.parentElement.removeChild( button.parentElement.querySelector(".star"));

                    // Si estem a l'apartat de wishlist. Fem fade away.
                    if(window.location.pathname === "/user/wishlist") {
                        fadeAwayFromChildItem(button.parentElement.parentElement.parentElement.parentElement);
                    }

                }else{
                    // ha pasat algo, actualitzem la apgina amb la resposta.
                    window.location.reload(false);
                }
            }).catch((data) => {
                if(DEBUG){
                    console.log("Error");
                    console.log(data);
                }
            });
        }, false)
    });
}

var wish_buttons = document.getElementsByName('WISH_BUTTON');
if (wish_buttons !== undefined) {
    wish_buttons.forEach(function (button, i, a){


        button.addEventListener("click", function () {

            var addWish = !button.classList.contains("negated");
            var method = addWish ? "POST" : "DELETE";

            if (DEBUG) {
                if(addWish){
                    console.log("Wishing game " + button.dataset.gameid)
                }else{
                    console.log("Removing wish from game "+ button.dataset.gameid)
                }
            }

            server.request("/user/wishlist/" + button.dataset.gameid,method).then((resp) => {
                if(resp.status === 200){
                    // All ok. Buy just fine.
                    // Actualitzem el boto a Owned.
                    if (addWish){
                        button.classList.add('negated')

                    }else{
                        button.classList.remove('negated')

                        // Si estem a l'apartat de wishlist. Fem fade away.
                        if(window.location.pathname === "/user/wishlist") {
                            fadeAwayFromChildItem(button.parentElement.parentElement.parentElement.parentElement);
                        }
                    }
                }else{
                    // ha pasat algo, actualitzem la apgina amb la resposta.
                    document.body.innerHTML = resp;
                }
            }).catch((data) => {
                if(DEBUG){
                    console.log("Error");
                    console.log(data);
                }
            });
        }, false)
    });
}