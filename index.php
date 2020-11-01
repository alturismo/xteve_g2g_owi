<?php
// streamlink, sample https://yourdomain.com/?streamtype=hls&streamlink=123456789abc

// fetch args from link local or remote
if ($_GET) {
    $streamtype = $_GET['streamtype'];
    $streamlink = $_GET['streamlink'];
} else {
    $streamtype = $argv[1];
    $streamlink = $argv[2];
}

// setup inputstream
$inputline = "http://localhost:34400/stream/$streamlink";	// format inputline
//
$bitratelow = "400k";	// lowwest bitrate
$bitratemed = "750k";	// medium  bitrate
$bitratehigh = "1100k";	// highest bitrate
//
$resolution = "854";	// resolution width, Aspect ratio is kept

// end config here
//

// set streamfile and continue
unset ($streamdata);

if ($streamtype == "hls") {
	$streamdata = 'master.m3u8';
}
if ($streamtype == "dash") {
	$streamdata = 'dash.mpd';
}
if (empty($streamdata)) {
	exit ();
}

// set controlfile if stream is already up and running
$controlfile = "$streamtype.$streamlink.streamactive";

// check if stream already running
if (file_exists($controlfile)) {

// relink directly
if (file_exists($streamdata)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.basename($streamdata).'"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($streamdata));
    readfile($streamdata);
    exit;
}

// exit here
exit ();

} else {

// remove old controlfile to clear running stream
unset ($oldcontrolfile);
$oldcontrolfile = glob('*.streamactive');
if (!empty($oldcontrolfile)) {
	exec ("rm *.streamactive");
}

// write new control file
exec ("echo > $controlfile");

// end old ffmpeg stream
$ffmpegactive = shell_exec ("pgrep -fl $resolution");

if (fnmatch("*ffmpeg*", $ffmpegactive)) {
	$ffmpegactive = strtok($ffmpegactive, " ");
} else {
	unset ($ffmpegactive);
}

if (!empty($ffmpegactive)) {
	exec ("kill $ffmpegactive");
	unset ($ffmpegactive);
}

// remove manifest and temp files to process only on new one
shell_exec ("rm master.m3u8");
shell_exec ("rm dash.mpd");
shell_exec ("rm -R v*/*");

// run ffmpeg with arguments and settings
$cmd1 = escapeshellcmd ('ffmpeg -hwaccel vaapi -vaapi_device /dev/dri/renderD128 -hwaccel_output_format vaapi -hide_banner -loglevel error -i');
$cmd2 = escapeshellcmd ('-y -vf format=vaapi,hwupload,deinterlace_vaapi,scale_vaapi=');
if ($streamtype == "hls") {
	$cmd3 = escapeshellcmd (':-1 -c:v h264_vaapi -c:a aac -ar 48000 -ac 2 -map 0:0 -map 0:1 -map 0:0 -map 0:1 -map 0:0 -map 0:1 -c:v:0 h264_vaapi -b:v:0');
	$cmd4 = escapeshellcmd ('-c:v:1 h264_vaapi -b:v:1');
	$cmd5 = escapeshellcmd ('-c:v:2 h264_vaapi -b:v:2');
	$cmd6 = escapeshellcmd ('-c:a:0 aac -ar 48000 -ac 2 -b:a:0 128k -var_stream_map "v:0,a:0 v:1,a:1 v:2,a:2" -master_pl_name master.m3u8 -f hls -hls_time 4 -hls_list_size 10 -hls_flags delete_segments -hls_segment_filename "v%v/fileSequence%d.ts" v%v/prog_index.m3u8');
}
if ($streamtype == "dash") {
	$cmd3 = escapeshellcmd (':-1 -c:v h264_vaapi -c:a aac -ar 48000 -ac 2 -map 0:0 -map 0:0 -map 0:0 -map 0:1 -b:v:0');
	$cmd4 = escapeshellcmd ('-b:v:1');
	$cmd5 = escapeshellcmd ('-b:v:2');
	$cmd6 = escapeshellcmd ('-c:a:0 aac -ar 48000 -ac 2 -b:a:0 128k -init_seg_name v$RepresentationID$/init-stream$RepresentationID$-$Bandwidth$.m4s -media_seg_name v$RepresentationID$/chunk-stream$RepresentationID$-$Bandwidth$-$Number%05d$.m4s -use_template 1 -use_timeline 1 -seg_duration 4 -adaptation_sets "id=0,streams=v id=1,streams=a" -f dash dash.mpd');
}

exec ("$cmd1 $inputline $cmd2$resolution$cmd3 $bitratelow $cmd4 $bitratemed $cmd5 $bitratehigh $cmd6 >/dev/null 2>/dev/null &");

// wait for Manifest or timeout 30 seconds (php.ini)
while (!file_exists($streamdata));

// relink to new stream
if (file_exists($streamdata)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.basename($streamdata).'"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($streamdata));
    readfile($streamdata);
    exit;
}

// exit here
exit();
}
?>
