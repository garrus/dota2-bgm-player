<?php
$binFile = __DIR__ . DIRECTORY_SEPARATOR . 'albums.bin';
$mtime = max(filemtime($binFile), filemtime(__FILE__));
if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
    $lastModified = DateTime::createFromFormat(DATE_RFC1123, $_SERVER['HTTP_IF_MODIFIED_SINCE']);
    if ($lastModified && $lastModified->getTimestamp() >= $mtime) {
        header('HTTP/1.1 304 Not Modified');
        die;
    }
}

require __DIR__ . '/includes/common.php';
/** @var Album[] $albums */
$albums = unserialize(file_get_contents($binFile));

header('Content-Type: text/html;charset=utf8');
header('Cache-Control: max-age=10');
header('Validate: must-validate');
header('Last-Modified: ' . date(DATE_RFC1123, $mtime));

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <link type="text/css" rel="stylesheet" href="https://v3.bootcss.com/dist/css/bootstrap.min.css">
    <style type="text/css">
        .piece-title {
            margin-left: 10px;
            color: lightslategray;
            font-size: 12px;
        }

        .piece {
            line-height: 28px;
            padding-right: 10px;
        }

        .piece:hover {
            background-color: aliceblue;
        }

        .piece.paused .piece-title,
        .piece.playing .piece-title {
            color: #8a6d3b; /* rgb(138, 109, 59) */
            font-weight: bold;
        }

        .piece-action {
            color: lightseagreen;
            font-size: 11px;
            width: 2.3em;
        }

        .piece-action > i {
            cursor: pointer;
        }

        .piece.paused [data-action=rewind],
        .piece.playing [data-action=rewind],
        .piece.paused .piece-progress,
        .piece.playing .piece-progress,
        .piece.playing [data-action=pause] {
            display: inline-block;
        }

        .piece [data-action=rewind],
        .piece [data-action=pause],
        .piece .piece-progress,
        .piece.playing [data-action=play] {
            display: none;
        }

        .piece-info {
            display: inline-block;
            margin-right: 10px;
            font-size: 11px;
            color: gray;
        }

        .piece-progress:after {
            content: " / ";
            color: silver;
        }

        .bottom-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            height: 80px;
            width: 300px;
            background-image: linear-gradient(0deg, rgba(100, 200, 200, 1) 0%, rgba(180, 205, 255, 0.7) 100%);
            border: 1px solid rgb(180, 205, 255);
            border-bottom-color: rgb(100, 200, 200);
            border-radius: 3px 3px 3px 0;
            padding: 10px 30px;
            line-height: 30px;
            color: #333;
            font-size: 15px;
        }

        #volume-rail {
            display: inline-block;
            width: 100px;
            height: 6px;
            margin-bottom: 4px;
            background-color: silver;
            border-radius: 10px;
            box-shadow: inset 0 0 3px grey;
            cursor: pointer;
        }
        #volume-block {
            display: inline-block;
            width: 12px;
            height: 12px;
            position: relative;
            cursor: pointer;
            box-shadow: inset 0 0 5px white;
            border-radius: 12px;
            left: -112px;
            top: -1px;
            background-color: red;
            opacity: 0;
        }
        #volume-control:hover #volume-block {
            opacity: 1;
        }
        
        #mute-btn {
            cursor: pointer;
            display: inline-block;
        }
        #mute-btn > * {
            display: none;
        }
        #mute-btn.volume-high > [rel=volume-high],
        #mute-btn.volume-off > [rel=volume-off],
        #mute-btn.volume-low > [rel=volume-low]{
            display: inline-block;
        }
        #play-mode-control {
            cursor: pointer;
        }
        #play-mode-control > * {
            display: none;
        }
        #play-mode-control > .active {
            display: inline-block;
        }

        #play-control {
            cursor: pointer;
        }
        #playing-title {
            display: block;
            height: 30px;
        }


    </style>
</head>
<body>
<div style="margin: 20px 30px;">
    <div class="row">
        <?php
        foreach ($albums as $index => $album):?>
            <div class="col-lg-4">
                <div class="panel panel-<?= ['info', 'success', 'warning', 'danger', 'primary'][$index % 5] ?>">
                    <div class="panel-heading album-title"><?= $album->name ?></div>
                    <div class="panel-body">
                        <ol class="album" data-index="<?= $index ?>" data-title="<?= $album->name?>">
                            <?php foreach (array_slice($album->pieces, 0) as $no => $piece): ?>
                                <li class="piece" data-index="<?= $no ?>" data-title="<?= $piece->name?>">
                                    <span class="piece-title"><?= $piece->name ?></span>

                                    <span class="piece-action pull-right">
                                        <i data-action="play" class="glyphicon glyphicon-play"></i>
                                        <i data-action="pause" class="glyphicon glyphicon-pause"></i>
                                        <i data-action="rewind" class="glyphicon glyphicon-repeat"></i>
                                    </span>

                                    <span class="piece-info pull-right">
                                        <span class="piece-progress"></span>
                                        <span class="piece-duration"></span>
                                    </span>
                                    <?php foreach ($piece->tracks as $track):
                                        ?>
                                        <audio class="track-audio" preload="auto"
                                               data-total-layer="<?= $piece->getTrackNum() ?>"
                                               data-layer="<?= $track->layer ?>"
                                               src="<?= str_replace([__DIR__ . DS, DS], ['', '/'], $album->path . DS . $track->rawFilename) ?>"></audio>
                                    <?php endforeach; ?>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="bottom-bar">
    <div id="playing-title"></div>
    <span id="volume-control">
        <span id="mute-btn" class="volume-high">
            <i rel="volume-off" class="glyphicon glyphicon-volume-off"></i>
            <i rel="volume-low" class="glyphicon glyphicon-volume-down"></i>
            <i rel="volume-high" class="glyphicon glyphicon-volume-up"></i>
        </span>
        <span id="volume-bar">
            <span id="volume-rail"></span>
            <span id="volume-block" draggable="true"></span>
        </span>
    </span>
    <span id="play-mode-control">
        <i data-mode="1" class="glyphicon glyphicon-refresh active" title="顺序播放"></i>
        <i data-mode="2" class="glyphicon glyphicon-random" title="随机播放"></i>
        <i data-mode="3" class="glyphicon glyphicon-repeat" title="单曲重复"></i>
        <i data-mode="4" class="glyphicon glyphicon-list-alt" title="歌单重复"></i>
    </span>
    <span id="play-control">
        <i data-action="play-next" class="glyphicon glyphicon-step-forward" title="下一曲"></i>
    </span>
</div>

<script src="https://cdn.bootcss.com/jquery/1.12.4/jquery.min.js"></script>
<script type="text/javascript">

    (function (w) {

        var player = (function(){

            var p = {
                DEBUG: false,
                $currentPieceElem: null, // 当前（最近）播放的曲目的li元素
                isPlaying: false, // 是否播放中
                currentAlbumIndex: null, // 当前（最近）播放的曲目的歌单索引
                currentPieceIndex: null, // 当前（最近）播放的曲目的索引
                _updateProgressIntv: 0,
                volume: 1, // 音量。范围：0 ~ 1
                volumeBeforeMute: 0, // 静音前的音量
                playMode: 1, // 播放模式。1: 顺序播放；2：随机播放；3：单曲重复；4：列表重复
                autoPlayDelay: 800, // 自动播放的延迟（毫秒）

                play($piece, rewind, delay){

                    if (typeof delay == "number" && delay > 0) {
                        setTimeout(function(){p.play($piece, rewind);}, delay);
                        return;
                    }

                    if (this.isPlaying) {
                        if (this.currentPieceIndex != $piece.data("index")) {
                            this.$currentPieceElem.toggleClass("playing", false).toggleClass("paused", false);
                            this.$currentPieceElem.attr("style", "");
                            this.$currentPieceElem.children("audio").each(function (i, a) {
                                if (a.currentTime > 0) {
                                    a.currentTime = 0;
                                    a.pause();
                                }
                            });
                        }
                    }

                    var volume = this.volume;
                    $piece.children("audio").each(function (i, a) {
                        if (rewind) {
                            a.currentTime = 0;
                        }
                        a.volume = volume;
                        a.play();
                    });
                    $piece.toggleClass("playing", true).toggleClass("paused", false);

                    this.$currentPieceElem = $piece;
                    this.currentPieceIndex = $piece.data("index");
                    this.currentAlbumIndex = $piece.parent().data("index");
                    this.isPlaying = true;
                    
                    $("#playing-title").text($piece.parent().data("title") + " - " + $piece.data("title"));
                    
                    this.startUpdateProgress();
                },

                onPlayEnd(){
                    if (this.isPlaying) {
                        this.playNext(this.autoPlayDelay);
                    }
                },

                playNext(delay){
                    switch (this.playMode) {
                        case 1: // 顺序播放：
                            this.playNextNormal(delay);
                            break;
                        case 2:
                            this.playRandom(delay);
                            break;
                        case 3:
                            this.playAgain(delay);
                            break;
                        case 4:
                            this.playNextInAlbum(delay);
                            break;
                        default:
                            console.warn("Unexpected play mode value: " + this.playMode);
                            break;
                    }
                },

                playNextNormal(delay){
                    if (this.$currentPieceElem) {
                        var $thisPiece = this.$currentPieceElem;
                        var $nextPiece = $thisPiece.next(".piece");
                        if (!$nextPiece.length) {
                            var $albumList = $(".album");
                            var r = Math.floor(Math.random() * $albumList.length);
                            $nextPiece = $albumList.eq(r).children(".piece").first();
                        }
                        this.play($nextPiece, undefined, delay);
                    } else {
                        this.play($(".album").first().children(".piece").first(), undefined, delay);
                    }
                },

                playNextInAlbum(delay){
                    if (this.$currentPieceElem) {
                        var $thisPiece = this.$currentPieceElem;
                        var $nextPiece = $thisPiece.next(".piece");
                        if (!$nextPiece.length) {
                            $nextPiece = $thisPiece.siblings().first();
                        }
                        this.play($nextPiece, undefined, delay);
                    } else {
                        this.play($(".album").first().children(".piece").first(), undefined, delay);
                    }
                },

                playRandom(delay){
                    var $albumList = $(".album");
                    var r = Math.floor(Math.random() * $albumList.length);
                    var $pieceList = $albumList.eq(r).children(".piece");
                    r = Math.floor(Math.random() * $pieceList.length);
                    var $nextPiece = $pieceList.eq(r);
                    this.play($nextPiece, undefined, delay);
                },

                playAgain(delay){
                    if (this.$currentPieceElem) {
                        this.play(this.$currentPieceElem, undefined, delay);
                    } else {
                        this.play($(".album").first().children(".piece").first(), undefined, delay);
                    }
                },

                pause(){
                    if (this.isPlaying) {
                        this.isPlaying = false;
                        this.$currentPieceElem.toggleClass("playing", false).toggleClass("paused", true);
                        this.$currentPieceElem.children("audio").each(function (i, a) {
                            a.pause();
                        });

                        this.stopUpdateProgress();
                        setTimeout(this.updateProgress.bind(this), 1);
                    }
                },

                stopUpdateProgress() {
                    if (this._updateProgressIntv) {
                        clearInterval(this._updateProgressIntv);
                        this._updateProgressIntv = 0;
                    }
                },

                startUpdateProgress() {
                    if (!this._updateProgressIntv) {
                        this._updateProgressIntv = setInterval(this.updateProgress.bind(this), 50);
                    }
                },

                updateProgress(){
                    if (this.isPlaying) {
                        var a = this.$currentPieceElem.find("audio").eq(0)[0];
                        var currentDuration = a.currentTime;
                        var totalDuration = a.duration;
                        this.$currentPieceElem.find(".piece-progress").text(formatTimeDuration(currentDuration));
                        // 设置背景渐变
                        this.$currentPieceElem.attr("style", "background-image:" + calcProgressBackground(currentDuration, totalDuration));
                    }
                },

                adjustVolume(volume){
                    if (this.volume != volume) {
                        this.volume = volume;
                        if (this.isPlaying) {
                            this.$currentPieceElem.find("audio").each(function(i, a){a.volume = volume;});
                        }
                    }
                },

                mute() {
                    if (this.volume > 0) {
                        this.volumeBeforeMute = this.volume;
                        this.adjustVolume(0);
                    }
                },
                unmute() {
                    if (this.volume == 0) {
                        this.adjustVolume(this.volumeBeforeMute);
                    }
                },

                setPlayMode(mode){
                    mode = parseInt(mode);
                    if (mode >= 1 && mode <= 4) {
                        this.playMode = mode;
                    } else {
                        console.warn("Unsupported play mode ", mode);
                    }
                }
            };

            $(".album").delegate(".piece-action", "click", function (e) {
                var $piece = $(this).parent();
                switch (e.target.dataset.action) {
                    case "play":
                        p.play($piece);
                        break;
                    case "pause":
                        p.pause();
                        break;
                    case "rewind":
                        p.play($piece, true);
                        break;
                    default:
                        break;
                }
            });

            $("audio").bind("canplaythrough", function (e) {
                $(this).parent().find(".piece-duration").eq(0).text(formatTimeDuration(this.duration));
                $(this).unbind("canplaythrough");
            }).bind("timeupdate", function (e) {
                if (this.previousElementSibling.tagName == "AUDIO") return;
                w.DEBUG && console.log(this.src, this.duration, this.currentTime);
                if (this.currentTime == 0) {
                    return;
                }

                if (this.currentTime == this.duration) {
                    p.onPlayEnd();
                }
            });

            $("#volume-rail").bind("click", function(e){
                var volume = e.offsetX / 100;
                console.log(e.offsetX, e.target.id);
                if (volume < 0) {
                    volume = 0;
                } else if (volume > 1) {
                    volume = 1;
                }
                p.adjustVolume(volume);
                updateVolumeDisplay(volume);
            });

            $("#volume-block").bind("click", function(e){
                var volume = p.volume + (e.offsetX - 6)/100;
                console.log(e.offsetX, e.target.id);
                if (volume < 0) {
                    volume = 0;
                } else if (volume > 1) {
                    volume = 1;
                }
                p.adjustVolume(volume);
                updateVolumeDisplay(volume);
            });
            $("#mute-btn").bind("click", function(e){
                if ($(this).hasClass("volume-off")) {
                    p.unmute();
                } else {
                    p.mute();
                }
                updateVolumeDisplay(p.volume);
            });

            $("#play-mode-control").bind("click", function(){
                var $icons = $(this).children();
                var $curActive = $icons.filter(".active");
                $curActive.removeClass("active");

                var $next = $curActive.next();
                if ($next.length == 0) {
                    $next = $icons.eq(0);
                }
                $next.addClass("active");
                p.setPlayMode($next.data("mode"));
            });

            $("#play-control").delegate("i", "click", function(){
                var action = $(this).data("action");
                switch(action){
                    case "play-next":
                        p.playNext(0);
                        break;
                }
            });

            function updateVolumeDisplay(volume) {
                $("#volume-block").css("left", -108 + volume * 96);
                $("#volume-rail").attr("style", "background-image: " + calcProgressBackground(volume, 1, "rgba(255, 0, 0, 0.7)", "rgba(233, 233, 233, 0.7)"));
                $("#mute-btn").toggleClass("volume-off", volume == 0)
                    .toggleClass("volume-high", volume > 0.6)
                    .toggleClass("volume-low", volume <= 0.6 && volume > 0);
            }

            updateVolumeDisplay(1);
        })();

        w.DEBUG = false;

        function calcProgressBackground(cur, total, elapsed = "rgb(235, 232, 208)", left = "rgb(245, 245, 225)") {
            var percent = cur * 100 / total;
            return `linear-gradient(90deg, ${elapsed} 0%, ${elapsed} ${percent}%, ${left} ${percent}%, ${left} 100%)`;
        }

        function formatTimeDuration(duration) {
            duration = Math.floor(duration);
            var min = Math.floor(duration / 60);
            var sec = duration % 60;
            if (min < 10) {
                min = "0" + min;
            }
            if (sec < 10) {
                sec = "0" + sec;
            }
            return `${min}:${sec}`;
        }


    })(window);

</script>
</body>
</html>