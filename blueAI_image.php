<?php
if (!file_exists('/etc/blueAI_settings.json')) {
    echo "/etc/blueAI_config.json not found\n";
    exit;
}

$settings = json_decode(file_get_contents('/etc/blueAI_settings.json'), true);
$imgtmp   = '/tmp/blueAI_tmp.jpg';
$ifile   = $_REQUEST['img']; # 1593733445_front.20200702_184405158.jpg
$file    = substr($ifile, 11);
$ofile   = "{$settings['aiinput']}/" . str_replace('.txt', '.jpg', $file);

if (!file_exists("{$settings['workdir']}/{$ifile}")) {
    echo "!!! {$ifile}<br>file missing: {$settings['workdir']}/{$ifile}";
    exit;
}

if (!file_exists($ofile)) {
    echo "!!! {$file}<br>file missing: {$ofile}";
    exit;
}

$detected = json_decode(file_get_contents("{$settings['workdir']}/{$ifile}"), true);
$image    = imagecreatefromstring(file_get_contents($ofile));
$red      = imagecolorallocate($image, 255, 0, 0);
$matches  = [];

if (isset($detected['aiResult']['predictions']) && !empty($detected['aiResult']['predictions']))
    foreach ($detected['aiResult']['predictions'] as $p)
        imagerectangle($image, $p['x_min'], $p['y_min'], $p['x_max'], $p['y_max'], $red);
        
imagepng($image, $imgtmp);

echo base64_encode(file_get_contents($imgtmp)) . '|_|';

foreach ($detected['detected'] as $obj => $conf)
    $matches[] = $obj . ': ' . implode(', ', $conf);

echo implode(', ', $matches);

if (isset($_REQUEST['debug']) && 'yes' == $_REQUEST['debug']) 
    echo '<pre>' . print_r($detected,true);

unlink($imgtmp);
