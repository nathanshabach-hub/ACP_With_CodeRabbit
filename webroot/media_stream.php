<?php

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if ($method !== 'GET' && $method !== 'HEAD') {
	header('Allow: GET, HEAD');
	http_response_code(405);
	exit;
}

$fileName = basename((string)($_GET['file'] ?? ''));
if ($fileName === '' || $fileName === '.' || $fileName === '..') {
	http_response_code(404);
	exit;
}

$filePath = __DIR__.'/files/events_submissions/'.$fileName;
if (!is_file($filePath) || !is_readable($filePath)) {
	http_response_code(404);
	exit;
}

$fileSize = filesize($filePath);
$fileHandle = fopen($filePath, 'rb');
if ($fileSize === false || $fileHandle === false) {
	http_response_code(404);
	exit;
}

$start = 0;
$end = max(0, $fileSize - 1);
$statusCode = 200;
$rangeHeader = trim((string)($_SERVER['HTTP_RANGE'] ?? ''));
if ($rangeHeader !== '') {
	if (!preg_match('/^bytes=(\d*)-(\d*)$/', $rangeHeader, $matches) || ($matches[1] === '' && $matches[2] === '') || $fileSize <= 0) {
		fclose($fileHandle);
		header('Content-Range: bytes */'.$fileSize);
		http_response_code(416);
		exit;
	}

	if ($matches[1] === '') {
		$suffixLength = (int)$matches[2];
		if ($suffixLength <= 0) {
			fclose($fileHandle);
			header('Content-Range: bytes */'.$fileSize);
			http_response_code(416);
			exit;
		}
		$start = max(0, $fileSize - $suffixLength);
	} else {
		$start = (int)$matches[1];
	}

	if ($matches[2] !== '' && $matches[1] !== '') {
		$end = min((int)$matches[2], $fileSize - 1);
	}
	if ($start >= $fileSize || $start > $end) {
		fclose($fileHandle);
		header('Content-Range: bytes */'.$fileSize);
		http_response_code(416);
		exit;
	}
	$statusCode = 206;
}

$mimeType = 'application/octet-stream';
if (function_exists('finfo_open')) {
	$fileInfo = finfo_open(FILEINFO_MIME_TYPE);
	if ($fileInfo) {
		$detectedMimeType = finfo_file($fileInfo, $filePath);
		if (is_string($detectedMimeType) && $detectedMimeType !== '') {
			$mimeType = $detectedMimeType;
		}
		finfo_close($fileInfo);
	}
}

$contentLength = $fileSize > 0 ? $end - $start + 1 : 0;
http_response_code($statusCode);
header('Content-Type: '.$mimeType);
header('Content-Length: '.$contentLength);
header('Accept-Ranges: bytes');
header('Content-Disposition: inline; filename="'.str_replace(['"', "\r", "\n"], '', $fileName).'"');
header('X-Content-Type-Options: nosniff');
if ($statusCode === 206) {
	header('Content-Range: bytes '.$start.'-'.$end.'/'.$fileSize);
}

if ($method === 'HEAD' || $contentLength === 0) {
	fclose($fileHandle);
	exit;
}

while (ob_get_level() > 0) {
	ob_end_clean();
}
fseek($fileHandle, $start);
$remaining = $contentLength;
while ($remaining > 0 && !feof($fileHandle)) {
	$chunk = fread($fileHandle, min(65536, $remaining));
	if ($chunk === false || $chunk === '') {
		break;
	}
	echo $chunk;
	$remaining -= strlen($chunk);
	flush();
}
fclose($fileHandle);