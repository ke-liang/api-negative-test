<?php
/**
 * Created by PhpStorm.
 * User: AaronXu
 * Date: 5/13/19
 * Time: 9:49 AM
 */
require '../lib/Util.php';

$dir = '../config/jsonTmpl/';//json template 的保存目录
if (isset($_POST['btn_save'])) {
    $errors = array();
    if (empty($_POST['path'])) {
        echo "Please input relative path!";
        exit(0);
    }
    if (empty($_POST['jsonTmplName'])) {
        echo "Please set the JSON template name first!";
        exit(0);
    }
    if (empty($_POST['body'])) {
        echo "你要保存的内容为空!";
        exit(0);
    }

    $jsonTmplName = strtolower($_POST['jsonTmplName']);
    $existingFileNames = array_values(\PingppTest\Util::getFileNames($dir, '.json'));
    if (in_array($jsonTmplName, $existingFileNames)) {
        echo "confirm_001";//表示 "你确定要覆盖原来的文件吗？";
        exit(0);
    } else {
        $urlMap = json_decode(file_get_contents($dir . "../urlMap.json"), true);
        $urlMap[$jsonTmplName] = preg_replace('/app_[a-zA-Z0-9]{16}/', 'APP_ID', $_POST['path']);
        file_put_contents('../config/urlMap.json', json_encode($urlMap, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));//保存 url

        $jsonFile = fopen($dir . strtolower($_POST['jsonTmplName']) . ".json", "w") or die("Unable to open file!");
        fwrite($jsonFile, preg_replace('/app_[a-zA-Z0-9]{16}/', 'APP_ID', $_POST['body']));//保存 json template
    }
}

if (isset($_POST['result']) && $_POST['result']) {
    if (empty($_POST['body'])) {
        echo "你要保存的内容为空!";
        exit(0);
    }
    $jsonFile = fopen($dir . strtolower($_POST['jsonTmplName']) . ".json", "w") or die("Unable to open file!");
    fwrite($jsonFile, $_POST['body']);
}
?>