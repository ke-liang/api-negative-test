<?php
/**
 * Created by PhpStorm.
 * User: AaronXu
 * Date: 5/11/16
 * Time: 11:55 AM
 */

namespace PingppTest;
require 'Util.php';

// 默认都是 pinpula 环境的测试数据
class Pingpp
{
    /**
     * 灰度发布的版本号，默认为 null
     * @var null
     */
    public static $greyVersion = null;

    /**
     * @var string The base URL for the Pingpp API.
     */
    public static $apiBaseUrl = null;

    /**
     * @var string The Pingpp API key to be used for requests.
     */
    public static $apiKey;

    /**
     * @var string The Pingpp liveMode to be used for requests.
     */
    public static $liveMode = true;//默认测试 live 模式

    /**
     * @var string The private key path to be used for signing requests.
     */
    public static $privateKeyPath = "../res/privatekey.key";

    /**
     * @var string The PEM formatted private key to be used for signing requests.
     */
    public static $privateKey;
}