<?php
// streamlink, sample https://yourdomain.com/?streamtype=hls&streamlink=123456789abc

// fetch args from link local or remote
if ($_GET) {
    $argument1 = $_GET['streamtype'];
    $argument2 = $_GET['streamlink'];
} else {
    $argument1 = $argv[1];
    $argument2 = $argv[2];
}

// setup inputstream
$inputline = "http://localhost:34400/stream/$argument2";	// format inputline
//
$bitratelow = "400k";	// lowwest bitrate
$bitratemed = "750k";	// medium  bitrate
$bitratehigh = "1100k";	// highest bitrate
//
$resolution = "854";	// resolution width, Aspect ratio is kept

// end config here
//

// set streamfile
$streamdata = 'master.m3u8';

// set controlfile if stream is already up and running
$controlfile = "$argument1.$argument2.streamactive";

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

// remove old controlfile to clear running hls stream
unset ($oldcontrolfile);
$oldcontrolfile = glob('hls.*.streamactive');
if (!empty($oldcontrolfile)) {
	exec ("rm hls.*.streamactive");
}

// write new control file
exec ("echo > $controlfile");

// remove manifest to process only on new one
if (file_exists($streamdata)) {
	unlink ($streamdata);
}

// end old ffmpeg stream
$ffmpegtype = shell_exec ("pgrep -fl $argument1");

if (fnmatch("*ffmpeg*", $ffmpegtype)) {
	$ffmpegtype = strtok($ffmpegtype, " ");
} else {
	unset ($ffmpegtype);
}

if (!empty($ffmpegtype)) {
	exec ("kill $ffmpegtype");
	unset ($ffmpegtype);
}

// run ffmpeg with arguments and settings
$cmd1 = escapeshellcmd ('ffmpeg -hwaccel vaapi -vaapi_device /dev/dri/renderD128 -hwaccel_output_format vaapi -hide_banner -loglevel error -i');
$cmd2 = escapeshellcmd ('-y -vf format=vaapi,hwupload,deinterlace_vaapi,scale_vaapi=');
$cmd3 = escapeshellcmd (':-1 -c:v h264_vaapi -c:a aac -ar 48000 -ac 2 -map 0:0 -map 0:1 -map 0:0 -map 0:1 -map 0:0 -map 0:1 -c:v:0 h264_vaapi -b:v:0');
$cmd4 = escapeshellcmd ('-c:v:1 h264_vaapi -b:v:1');
$cmd5 = escapeshellcmd ('-c:v:2 h264_vaapi -b:v:2');
$cmd6 = escapeshellcmd ('-c:a:0 aac -ar 48000 -ac 2 -b:a:0 128k -var_stream_map "v:0,a:0 v:1,a:1 v:2,a:2" -master_pl_name master.m3u8 -f hls -hls_time 4 -hls_list_size 10 -hls_flags delete_segments -hls_segment_filename "v%v/fileSequence%d.ts" v%v/prog_index.m3u8');

exec ("$cmd1 $inputline $cmd2$resolution$cmd3 $bitratelow $cmd4 $bitratemed $cmd5 $bitratehigh $cmd6 >/dev/null 2>/dev/null &");

// wait for Manifest or timeout 30 seconds (php.ini)
while (!file_exists($streamdata));

// relink to new HLS stream
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

// exec ("nohup sh test.sh >/dev/null 2>/dev/null &");

// exit here
exit();
}
?>
