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
        }

        .piece-action > i {
            cursor: pointer;
        }

        .piece.paused .piece-progress,
        .piece.playing .piece-progress,
        .piece.playing .piece-pause {
            display: inline-block;
        }

        .piece .piece-pause,
        .piece .piece-progress,
        .piece.playing .piece-play {
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
                        <ol class="album" data-album-index="<?= $index ?>">
                            <?php foreach (array_slice($album->pieces, 0) as $no => $piece): ?>
                                <li class="piece" data-piece-index="<?= $no ?>">
                                    <span class="piece-title"><?= $piece->name ?></span>

                                    <span class="piece-action pull-right">
                                        <i class="glyphicon glyphicon-play piece-play"></i>
                                        <i class="glyphicon glyphicon-pause piece-pause"></i>
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

<script src="https://cdn.bootcss.com/jquery/1.12.4/jquery.min.js"></script>
<script type="text/javascript">

    (function (w) {
        w.DEBUG = false;
        var $playingPiece;

        $(".album").delegate(".piece-play", "click", function () {
            playMusic($(this).parentsUntil(".piece").last().parent());
        }).delegate(".piece-pause", "click", function () {
            pauseMusic($(this).parentsUntil(".piece").last().parent());
        });

        function pauseMusic($piece) {
            $piece.toggleClass("playing", false).toggleClass("paused", true);
            $piece.children("audio").each(function (i, a) {
                a.pause();
            });
        }

        function playMusic($piece) {

            if ($playingPiece) {
                if ($playingPiece.data("piece-index") != $piece.data("piece-index")) {
                    $playingPiece.toggleClass("playing", false).toggleClass("paused", false);
                    $playingPiece.attr("style", "");
                    $playingPiece.children("audio").each(function (i, a) {
                        if (a.currentTime > 0) {
                            a.currentTime = 0;
                            a.pause();
                        }
                    });
                }
            }

            $piece.children("audio").each(function (i, a) {
                a.play();
            });
            $piece.toggleClass("playing", true).toggleClass("paused", false);
            $playingPiece = $piece;
        }

        function updateProgress() {
            if ($playingPiece && $playingPiece.hasClass("playing")) {
                var a = $playingPiece.find("audio").eq(0)[0];
                var currentDuration = a.currentTime;
                var totalDuration = a.duration;
                $playingPiece.find(".piece-progress").text(formatTimeDuration(currentDuration));

                // 设置背景渐变
                $playingPiece.attr("style", "background-image:" + calcProgressBackground(currentDuration, totalDuration));
            }
        }

        function calcProgressBackground(cur, total) {
            var percent = cur * 100 / total;
            var elapsed = "rgb(227, 227, 200)";
            var left = "rgb(245, 245, 220)";
            return `linear-gradient(90deg, ${elapsed} 0%, ${elapsed} ${percent}%, ${left} ${percent}%, ${left} 100%)`;
        }

        setInterval(updateProgress, 50);

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

        $("audio").bind("canplaythrough", function (e) {
            $(this).parent().find(".piece-duration").eq(0).text(formatTimeDuration(this.duration));
        }).bind("timeupdate", function (e) {
            if (this.previousElementSibling.tagName == "AUDIO") return;
            w.DEBUG && console.log(this.src, this.duration, this.currentTime);

            if (this.currentTime == this.duration) {
                var $thisPiece = $(this).parent();
                var $nextPiece = $thisPiece.next(".piece");
                if (!$nextPiece.length) {
                    var $albumList = $(".album");
                    var r = Math.floor(Math.random() * $albumList.length);
                    $nextPiece = $albumList.eq(r).children(".piece").first();
                }

                setTimeout(function(){
                    playMusic($nextPiece);
                }, 1000);
            }
        });
    })(window);

</script>
</body>
</html>