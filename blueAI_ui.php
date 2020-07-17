<!DOCTYPE html>
<html>
<head>
<title>BlueAI</title>
<style>
body {
    background-color: #ccc;
    margin-top: 0;
}
h2 {
    font-family: arial;
    font-weight: 100;
}
table.bi {
    background-color: #eee;
}
table.bi tr td {
    width: 130px;
    text-align: center;
}
table.bi tr td td {
    width: 110px;
    text-align: center;
}
table.bi tr td td td {
    width: 22px;
}
table.bi tr td td td td {
    width: 333px;
}
.bshadow {
    box-shadow: 1px 7px 13px #888;
}
.hilite {
    background-color: #fff !important;
}
#aimage {
    box-shadow: 1px 7px 13px #888;
    border: 1px solid #333;
}
.def {
    cursor: pointer;
}
.grn {
    background-color: #DAECE5;
}
.sml {
    font-family: arial;
    font-size: 11px;
}
</style>
</head>
<?php
if (!file_exists('/etc/blueAI_settings.json')) {
    echo "/etc/blueAI_config.json not found\n";
    exit;
}

$settings   = json_decode(file_get_contents('/etc/blueAI_settings.json'), true);
$files      = array_diff(scandir($settings['workdir'], SCANDIR_SORT_DESCENDING), ['..', '.']);
$onlyalerts = isset($_REQUEST['onlyalerts']) && 'yes' == $_REQUEST['onlyalerts'] ? ' checked' : '';
$debug      = isset($_REQUEST['debug']) && 'yes' == $_REQUEST['debug'] ? ' checked' : '';
$firstOne   = '';
$ct         = 0;

echo "
<table width='100%'>
    <tr>
        <td width='33%' valign='top'>
            <table cellpadding='0' cellspacing='0'>
                <tr>
                    <td class='sml'>Alerts Only:</td>
                    <td class='sml'><input type='checkbox' id='onlyalerts' onclick='onlyalerts()'{$onlyalerts}></td>
                </tr>
                <tr>
                    <td class='sml' align='right'>Debug:</td>
                    <td class='sml'><input type='checkbox' id='debug' onclick='onlyalerts()'{$debug}></td>
                </tr>
            </table>
        </td>
        <td width='34%' align='center'><h2>BlueIA</h2></td>
        <td width='33%' valign='top' align='right'>
            <table cellpadding='0' cellspacing='0'>
                <tr>
                    <td class='sml'>ver: 20200704</td>
                </tr>
                <tr>
                    <td class='sml'><span id='time'></span></td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<br>
<table cellpadding=5>
    <tr>
        <td valign=top>
            <div style='height:600px;overflow:auto' class=bshadow>
                <table cellpadding='3' cellspacing='0' class='bi'>
";

foreach ($files as $file) {
    $d = json_decode(file_get_contents("{$settings['workdir']}/{$file}"), true);

    if (!empty($onlyalerts) && 'False Alert' == $d['msg'])
        continue;

    $fTime = date('m-d H:i:s', substr($file, 0, 11));
    $bName = substr(basename($file, '.txt'), 11);

    if (empty($firstOne))
        $firstOne = $file;

    if (file_exists("{$settings['aiinput']}/{$bName}.jpg")) {
        if ($ct++ < $settings['uiMaxRows']) {
            
            $cam = ucfirst(explode('.', $bName)[0]);
            $id  = $file == $firstOne ? ' id="firstOne"' : '';
            $clr = 'False Alert' == $d['msg'] ? '' : ' grn';

            echo "
<tr class='def{$clr}' onclick=\"showimg(this,'{$file}')\">
    <td>{$fTime}</td>
    <td {$id}>{$cam}</td>
    <td>{$d['msg']}</td>
</tr>
";
        }
    } else
        unlink("{$settings['workdir']}/{$file}");
}

echo "
                </table>
            </div>
        </td>
        <td></td>
        <td valign=top id='biimage'></td>
    </tr>
</table>
";
?>
<script language="javascript">
var prevRow;
var refresh;

function startTimer(duration, display) {
    var timer = duration, minutes, seconds;

    refresh = setInterval(function () {
        minutes = parseInt(timer / 60, 10)
        seconds = parseInt(timer % 60, 10);

        minutes = minutes < 10 ? "0" + minutes : minutes;
        seconds = seconds < 10 ? "0" + seconds : seconds;

        display.textContent = minutes + ":" + seconds;

        if (--timer < 0) {
            clearInterval(refresh);
            location.reload();
        }
    }, 1000);
}

function showimg (t,img) {
    var debug = document.getElementById('debug').checked ? 'yes' : 'no';

    document.getElementById('biimage').innerHTML = 'Processing...';
    
    if (typeof(prevRow) !== 'undefined')
        prevRow.classList.remove("hilite");

    prevRow = t;
    prevRow.classList.add("hilite");

    var xhReq = new XMLHttpRequest();
    xhReq.open("POST", "blueAI_image.php?img=" + img + "&debug=" + debug, false);
    xhReq.send(null);
    
    var serverResponse = xhReq.responseText;
    var tmp = serverResponse.split('|_|');

    if ('!!!' == tmp[0].substr(0,3))
        var stats = tmp[0];
    else
        var stats = img + '<br><img id="aimage" src="data:image/png;base64,' + tmp[0] + '"/><br><br>' + tmp[1];

    document.getElementById('biimage').innerHTML = stats;
}

function onlyalerts (t) {
    var state = document.getElementById('onlyalerts').checked ? 'yes' : 'no';
    var debug = document.getElementById('debug').checked ? 'yes' : 'no';
    location.href='blueAI_ui.php?onlyalerts=' + state + '&debug=' + debug;
}

window.onload = function () {
    document.getElementById('firstOne').click();
    startTimer(<?= $settings['uiAutoRefresh'] ?>, document.querySelector('#time'));
}
</script>
</html>
