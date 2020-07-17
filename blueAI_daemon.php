<?php
/*
--- Deepstack ---

Objects: Person, Bicycle, Motorcycle, Car, Truck, Bus, Boat, Airplane, Cat, Dog, Bird, Horse, Sheep, Cow, Bear

Sample Response:
    Array (
        [success] => 1
        [predictions] => Array (
                [0] => Array (
                        [confidence] => 0.77902365
                        [label] => potted plant
                        [y_min] => 117
                        [x_min] => 382
                        [y_max] => 210
                        [x_max] => 445
                    )
                [1] => Array (
                        [confidence] => 0.5557666
                        [label] => bench
                        [y_min] => 121
                        [x_min] => 442
                        [y_max] => 263
                        [x_max] => 561
*/

function logger ($entry) {
    foreach (explode("\n", trim($entry, "\n")) as $t) {
        $logLine = date('Ymd H:i:s ') . trim($t, "\n") . "\n";
        echo $logLine;
    }
}

function cleanWorkDir () {
    global $settings, $lastCleaned;

    logger('Cleaning blueAI work directory');
    
    $lastCleaned = date('H:i');
    $cleaned     = false;

    foreach (glob($settings['workdir'] . '/*.txt') as $tfile) {
        $bn = substr(basename($tfile, '.txt'), 11) . '.jpg'; # 1593953909_patio.20200705_075829135.txt
        $fn = "{$settings['aiinput']}/{$bn}";

        if (!file_exists($fn)) {
            logger('  removing: ' . $bn);
            unlink($tfile);
            $cleaned = true;
        }
    }

    if (!$cleaned)
        logger('  nothing to remove');
}

chdir(__DIR__);

if (!file_exists('/etc/blueAI_settings.json')) {
    echo "/etc/blueAI_settings.json not found\n";
    exit;
}

$caughtUp     = false;
$settings     = json_decode(file_get_contents('/etc/blueAI_settings.json'), true);
$alertObj     = explode(',', strtolower($settings['alertObjects']));
$lock_file    = '/tmp/blueAI.lock';
$fp           = fopen($lock_file, 'c+');
$lastCleaned  = '';
$biTriggerApi = parse_url($settings['blueIris']);

parse_str($biTriggerApi['query'], $biTriggerCam);

date_default_timezone_set($settings['timezone']);

if (!flock($fp, LOCK_EX | LOCK_NB)) {
    echo "Error: could not obtain the lock on {$lock_file}!\n";
    exit(1);
}

cleanWorkDir();

while (true) {
    logger('Checking BlueIris for images');

    $files = glob($settings['aiinput'] . '/*.jpg'); # Get BlueIris images

    # Ignore already processed images

    if (file_exists('blueAI_lastImage.txt')) {
        $oldest = (int) file_get_contents('blueAI_lastImage.txt');
     
        foreach ($files as $ix => $nam)
            if (filemtime($nam) <= $oldest)
                unset($files[$ix]);
    }

    usort($files, function($a, $b) {
        return filemtime($a) < filemtime($b);
    });

    # Process images

    $filesIDX = count($files) - 1;

    while (!empty($files) && $filesIDX > -1) {
        $file     = $files[$filesIDX];
        $fileTime = filemtime($file);
        $fstub    = basename($file, '.jpg');
        $fstub2   = explode('.', $fstub);

        logger("Checking image: {$file} #" . ($filesIDX + 1));

        if (file_exists("inf/{$fileTime}_{$fstub}.txt")) {
            logger("  image already processed - skipping");
        } else {
            $imgFile = new \CURLFile($file);
            $ch      = curl_init();
            
            curl_setopt($ch, CURLOPT_URL, $settings['deepstack']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, ['image' => $imgFile]);
            curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($ch, CURLOPT_FAILONERROR, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 4);

            $response   = curl_exec($ch);
            $aiResult   = json_decode($response, true);
            $msg        = 'False Alert';
            $biResp     = '';
            $biResp2    = '';
            $cuMsg      = $caughtUp ? '' : ' - catching up';
            $validAlert = [];

            if (false === $response) {
                logger('No reponse from deepstack');
                sleep(3);
                continue;
            }

            if (!isset($aiResult['predictions']) || empty($aiResult['predictions']))
                logger('  False Alert' . $cuMsg); # deepstack detected no objects
            else {
                foreach ($aiResult['predictions'] as $air)
                    if (in_array(strtolower($air['label']), $alertObj) && ($air['confidence'] * 100) > $settings['confidence'])
                        $validAlert[$air['label']][] = round($air['confidence'] * 100, 0) . '%';

                if (!empty($validAlert)) {
                    $msg     = implode(', ', array_keys($validAlert));
                    $camera  = $fstub2[0];
                    $biAPI   = str_replace('@', $camera, $settings['blueIris']);

                    if ($caughtUp) {
                        $biResp  = file_get_contents($biAPI);
                        $biResp2 = "triggerd HD camera: {$camera}";
                        require 'blueAI_triggered.php';
                    } else
                        $biResp  = 'not triggering BI';
                } 

                logger('  ' . $msg . $biResp2 . $cuMsg);
            }

            $enc = json_encode(['detected' => $validAlert,
                                'msg'      => $msg, 
                                'aiResult' => $aiResult,
                                'blueIris' => $biResp2 . $cuMsg]);

            file_put_contents("inf/{$fileTime}_{$fstub}.txt", $enc);
        }

        file_put_contents('blueAI_lastImage.txt', $fileTime);

        --$filesIDX;
    }
    
    $caughtUp = true;

    if (7 == date('i') && $lastCleaned != date('H:i'))
        cleanWorkDir();

    logger('Sleeping...');
    sleep(1);
}
