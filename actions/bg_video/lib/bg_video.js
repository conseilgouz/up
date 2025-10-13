/* Initialisation videoBackground pour bg_video */

//loop fix

 document.getElementsByTagName('video')[0].onended = function () {
    this.load();
    this.play();
};


//init videoBackground for html video
// $('.html-video').videoBackground();

//init youtube
var tag = document.createElement('script');

tag.src = "https://www.youtube.com/iframe_api";
var firstScriptTag = document.getElementsByTagName('script')[0];
firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

var ytPlayers = [];

onYouTubePlayerAPIReady = function () {

    var players = document.querySelectorAll('.youtube-video');


    for (var i = 0; i < players.length; i++) {
        var player = new YT.Player(players[i], {
            playerVars: {
                'autoplay': 1,
                'loop': 1,
                'rel': 0,
                'showinfo': 0,
                'controls': 0,
                'modestbranding': 1,
                'playlist': players[i].getAttribute("data-yt-id")

            },
            videoId: players[i].getAttribute("data-yt-id"),
            events: {
                'onReady': onPlayerReady
            }

        });

        ytPlayers.push(player);
    }
};

function onPlayerReady(event) {

    event.target.mute();


    //init videoBackground for youtube video
    jQuery('.youtube-video').videoBackground();

}
