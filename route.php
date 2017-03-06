<?php
/**
 * Created by PhpStorm.
 * User: huyaowen
 * Date: 2017/3/6
 * Time: 15:35
 */

$uri = $_SERVER['REQUEST_URI'];
$docRoot = $_SERVER['DOCUMENT_ROOT'];

$requestFile = mb_convert_encoding(urldecode($uri), 'GBK', 'UTF-8');
$filename = $docRoot. str_replace('/', DIRECTORY_SEPARATOR, $requestFile);

if ($requestFile === '/index.php' || $requestFile === '/') {
	include __DIR__. DIRECTORY_SEPARATOR. 'index.php';
	exit;
}

if (is_file($filename)) {
    if (preg_match('~^/album/~', $requestFile)) {
        header('Content-Type: audio/mp3');
        header('Cache-Control: Max-Age=691200');
        header('Expires: Web, 01 Mar 2099 08:16:31 GMT');
        header('Content-Length: '. filesize($filename));
        readfile($filename);
        exit;
    }

	header('HTTP/1.1 403 Forbidden');
	exit;
}

header('HTTP/1.1 404 NOT FOUND');
exit;