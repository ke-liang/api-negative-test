<?php
/**
 * Created by PhpStorm.
 * User: AaronXu
 * Date: 5/13/19
 * Time: 9:49 AM
 */
require '../lib/HttpFailed.php';

//$inputData = file_get_contents("php://input"); //获取前段请求的数据
//$inputData = json_decode($inputData, true);

if (isset($_POST['btn_submit'])) {
    $body = null;
    if (empty($_POST['host'])) {
        echo 'Please select a host url, namely base url!';
        exit(0);
    }
    if (empty($_POST['path'])) {
        echo 'Please input relative path!';
        exit(0);
    }
    if (empty($_POST['method'])) {
        echo 'Please select http method in [POST, PUT, GET List]!';
        exit(0);
    }
    if (empty($_POST['apiKey'])) {
        echo 'Please input api key!';
        exit(0);
    }

    if (empty($_POST['body'])) {
        echo '即使是 GET 请求也要传参数!';
        exit(0);
    }
    $body = json_decode($_POST['body'], true);

    $method = $apiKey = $path = null;
    $method = $_POST['method'];
    $path = $_POST['path'];
    $testType = strtolower($_POST['testType']);
    \PingppTest\Pingpp::$apiBaseUrl = $_POST['host'];
    PingppTest\Pingpp::$apiKey = $_POST['apiKey'];

    try {
        echo '<pre style="margin: 5%; white-space: pre-wrap;word-wrap: break-word">';
        if ($testType === 'wrong') {
            \PingppTest\HttpFailed::requestWithWrongFieldValue($body, $method, $path);
        } elseif ($testType === 'required') {
            \PingppTest\HttpFailed::requestWithoutRequiredField($body, $method, $path);
        } elseif ($testType === 'maxvalue') {
            \PingppTest\HttpFailed::requestWithMaxFieldValue($body, $method, $path);//字段值最大长度
        } elseif ($testType === 'minvalue') {
            \PingppTest\HttpFailed::requestWithMinFieldValue($body, $method, $path);//必填字段取最小值
        }
        echo "</pre>";
    } catch (Exception $e) {
        echo '<p style="white-space: pre-wrap;word-wrap: break-word;color: red">';
        print_r($e->getMessage());
        echo "</p>";
    }
}
?>