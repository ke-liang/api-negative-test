<?php
/**
 * Created by PhpStorm.
 * User: AaronXu
 * Date: 12/8/15
 * Time: 9:37 PM
 */

namespace PingppTest;

class Util
{
    /**
     * override the privateKeyPath url with pinpula URL
     */
    public static function apiPrivateKeyPath()
    {
        Pingpp::$privateKeyPath = __DIR__ . "/../res/privatekey.key";
    }

    /**
     * override the apiBase url with mocked URL
     */
    public static function apiTestMockBaseURL()
    {
        Pingpp::$apiBaseUrl = 'https://3ptest.pinpula.com:8182';
    }

    /**
     * 数组转XML
     * @param $arr
     * @return string
     */
    public static function arrayToXmlForWx($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            if ($key == 'total_fee') {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">\n";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">\n";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }

    /**
     * 将 array 转为 xml
     * @param $arr
     * @param bool $root , 多维数组只有外层需要添加 <xml>
     * @return string
     */
    public static function arrayToXml($arr, $root = true)
    {
        if ($root) {
            $xml = "<xml>";
        } else {
            $xml = null;
        }
        foreach ($arr as $key => $val) {
            if (is_array($val)) {
                $xml .= "<" . $key . ">" . self::arrayToXml($val, false) . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            }
        }
        if ($root) {
            $xml .= "</xml>";
        } else {
            $xml .= null;
        }
        return $xml;
    }

    /**
     * 将 XML 转为 array
     * @param $xml
     * @return mixed
     */
    public static function xmlToArray($xml)
    {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $values;
    }

    /**
     * Generate a string with date time randomly
     */
    public static function getRandChar()
    {
        $str = null;
        $strPol = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
        $max = strlen($strPol) - 1;

        for ($i = 0; $i < 10; $i++) {
            $str .= $strPol[rand(0, $max)];//rand($min,$max)生成介于min和max两个数之间的一个随机整数
        }
//        $dt=strtotime(Util::dateYmdHisPRC());//strtotime() 函数将任何英文文本的日期时间描述解析为 Unix 时间戳。
        return "{$str}" . time();
    }

    /**
     * Generate a request_id like iar_pingpp + 18
     */
    public static function genRequestId()
    {
        $str = null;
        $strPol = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
        $max = strlen($strPol) - 1;

        for ($i = 0; $i < 18; $i++) {
            $str .= $strPol[rand(0, $max)];//rand($min,$max)生成介于min和max两个数之间的一个随机整数
        }
        return 'iar_Pingpp' . "{$str}";
    }

    /**
     * Generate a string with specified length
     */
    public static function getString($length = 20)
    {
        $str = null;
        $strPol = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
        $max = strlen($strPol) - 1;

        for ($i = 0; $i < $length; $i++) {
            $str .= $strPol[rand(0, $max)];//rand($min,$max)生成介于min和max两个数之间的一个随机整数
        }
//        $dt=strtotime(Util::dateYmdHisPRC());//strtotime() 函数将任何英文文本的日期时间描述解析为 Unix 时间戳。
        return $str;
    }

    /**
     * Generate a unicode string with specified length
     */
    public static function getUnicode($length = 20)
    {
        $str = null;
        $strPol = '聚合支付，一切才刚刚开始。你需要自由搭建灵活、强大的支付系统，来应对任何行业和场景的商业需求。'
            . '无论接入支付、管理交易、分析数据，还是在你的中添加余额、优惠券等支付相关功能，调用就能轻松实现，'
            . '随心定制属于自己的支付套件，同时享用安全、稳定、高性能的底层设施。看似复杂的支付系统，从此触手可及。';
        $max = mb_strlen($strPol) - 1;

        for ($i = 0; $i < $length; $i++) {
            $str .= mb_substr($strPol, rand(0, $max), 1);//rand($min,$max)生成介于min和max两个数之间的一个随机整数
        }
        return $str;
    }

    /**
     * Generate a number with specified length
     */
    public static function getNumber($length = 20)
    {
        $str = null;
        $strPol = "0123456789";
        $max = strlen($strPol) - 1;

        for ($i = 0; $i < $length; $i++) {
            $str .= $strPol[rand(0, $max)];//rand($min,$max)生成介于min和max两个数之间的一个随机整数
        }
        return $str;
    }

    /**
     * 公私钥处理方法（返回的连续不换行，没有头尾）
     * @param $pubKey
     * @return string
     */
    public static function formatKey($pubKey)
    {
        // 下面变量$pubKey为示例，只需要替换这个变量的内容即可
        $res = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($pubKey, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
        return $res;
    }

    /**
     * 用来判断一个list中的id是否有相同的
     * @param $data
     * @param $arr
     */
    public static function hasSameId($data, $arr)
    {
        if (in_array($data['id'], $arr)) {
            assert(0 == 1, 'There are the same id!' . $data['id']);
        } else {
            $arr[] = $data['id'];
        }
    }

    /**
     * Generate IPV4
     */
    public static function genIPV4()
    {
        return rand(0, 255) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(0, 255);
    }

    /**
     * 邮件, 短信和消息的签名函数
     * @param $params
     * @param $secret
     * @param bool|true $bool_to_int
     * @return string
     */
    //签名方法
    public static function signData($params, $secret, $bool_to_int = true)
    {
        ksort($params);
        $tmp = [];
        foreach ($params as $k => $v) {
            if (is_bool($v)) {
                $v = $bool_to_int ? ($v ? '1' : '0') : ($v ? 'true' : 'false');
            }
            $tmp[] = $k . '=' . $v;
        }
        $tmp[] = $secret;
        $str = implode('&', $tmp);
        return strtolower(sha1($str));
    }

    /**
     * module app 和 royalty 模块使用的签名方式，API 调用都是 json 格式
     * 签名采用uri+query+data三个数据来进行,对应module_key/signature通过uri传输,可以通过nosign=true来免签名
     * @param $uri , 请求的资源地址,不包括host和query string
     * @param $rawData ,
     * @param $module_secret
     * @param $method
     * @return string
     */
    public static function signDataForAppAndRoyalty($uri, $rawData, $module_secret, $method)
    {
        $signData = array(
            'uri' => $uri,
            'query' => '',//地址的query string部分,除开用于签名的module_key,nosign,signature三个参数,其余参数按key排序，重新组合成query string
            'data' => ''//请求体的 raw data （http_build_query）
        );
        if ($method == 'POST' || $method == 'PUT') {
            $signData['data'] = json_encode($rawData);//json 格式签名
        } elseif ($rawData != null) {
            $signData['query'] = self::implodeStr($rawData);
        }
        return strtolower(hash_hmac('sha256', self::implodeStr($signData), $module_secret));
    }

    /**
     * 先排序，之后将关联数组改为索引数组，最后用 implode 函数把数组元素组合为字符串
     * @param $data
     * @return string
     */
    public static function implodeStr($data)
    {
        ksort($data);
        $tmp = [];
        foreach ($data as $k => $v) {
            $tmp[] = $k . '=' . strval($v);// bool 类型的要先转为 0/1
        }
        $str = implode('&', $tmp);
        return $str;
    }

    /**
     * 先排序,之后拼接字符串 a=b&c=d
     * @param $jsonArr
     * @return string
     */
    public static function buildQuery($jsonArr)
    {
        ksort($jsonArr);
        $buildData = [];
        foreach ($jsonArr as $k => $v) {
            $buildData[] = $k . '=' . $v;
        }
        return implode('&', $buildData);
    }

    /**
     * 获取指定私钥文件中的内容
     * @return string
     */
    public static function getPrivateKey()
    {
        return file_get_contents(Pingpp::$privateKeyPath);
    }

    /**
     * 生成API签名的字符串
     * @param $request_BodyString , json string,即已被json_encode的json, 或者为null
     * @param null $request_URL , 除去host的url
     * @param null $request_TimeStamp , 头信息中的Pingplusplus-Request-Timestamp
     * @return string 用于签名的字符串
     * @throws \Exception
     */
    public static function genSignatureForAPI($request_BodyString = null, $request_URL = null, $request_TimeStamp = null)
    {
        $requestSignature = NULL;
        $privateKey = null;
        $finalSignData = $request_BodyString . $request_URL . $request_TimeStamp; //组成body+url+timestamp待签名的字符串
        $privateKey = Util::getPrivateKey();
        if ($privateKey) {
            $signResult = openssl_sign($finalSignData, $requestSignature, $privateKey, 'sha256');
            if (!$signResult) {
                throw new \Exception('Generate signature failed');
            }
        }
        return base64_encode($requestSignature);
    }

    /**
     * 返回当前时间，比如 20170101
     * @return string
     */
    public static function dateYmdHisPRC()
    {
        date_default_timezone_set('PRC');
//        list($tmp1, $tmp2) = explode(' ', microtime());
//        $msec =  (string)sprintf('%.0f', floatval($tmp1)* 1000);
//        return date('YmdHis').$msec;//毫秒级的时间戳
        return date('YmdHis');
    }

    /**
     * 返回毫秒级时间戳
     * @return string 毫秒时间戳
     */
    public static function getMilliSecondTime()
    {
        list($tmp1, $tmp2) = explode(' ', microtime());
        $msec = (string)sprintf('%.0f', (floatval($tmp1) + floatval($tmp2)) * 1000);
        return $msec;
    }

    /**
     * 返回当天 0 点的时间戳，比如 2017-06-14 00:00:00
     * @return string
     */
    public static function dateNowTimeStampPRC()
    {
        date_default_timezone_set('PRC');
        return strtotime(date('Y-m-d'));
    }

    /**
     * 输出规范的json编码的字符串
     * @param $dataArr
     */
    public static function formatPrint_r($dataArr)
    {
        print_r(json_encode($dataArr, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
    }

    /**
     * 返回数组的维度
     * @param $arr
     * @return mixed
     */
    public static function getMaxDimension($arr)
    {
        $al = array(0);
        function aL($arr, &$al, $level = 0)
        {
            if (is_array($arr)) {
                $level++;
                $al[] = $level;
                foreach ($arr as $v) {
                    aL($v, $al, $level);
                }
            }
        }

        aL($arr, $al);
        return max($al);
    }

    /**
     * if PECL_HTTP is not available use a fall back function
     *
     * thanks to ricardovermeltfoort@gmail.com
     * http://php.net/manual/en/function.http-parse-headers.php#112986
     * @param string $raw_headers raw headers
     * @return array
     */
    public static function parseHeaders($raw_headers)
    {
        if (function_exists('http_parse_headers')) {
            return http_parse_headers($raw_headers);
        } else {
            $key = '';
            $headers = array();
            $raw_headers = explode("\n", $raw_headers);
            foreach ($raw_headers as $i => $h) {
                $h = explode(':', $h, 2);
                if (isset($h[1])) {
                    if (!isset($headers[$h[0]])) {
                        $headers[$h[0]] = trim($h[1]);
                    } elseif (is_array($headers[$h[0]])) {
                        $headers[$h[0]] = array_merge($headers[$h[0]], array(trim($h[1])));
                    } else {
                        $headers[$h[0]] = array_merge(array($headers[$h[0]]), array(trim($h[1])));
                    }
                    $key = $h[0];
                } else {
                    if (substr($h[0], 0, 1) == "\t") {
                        $headers[$key] .= "\r\n\t" . trim($h[0]);
                    } elseif (!$key) {
                        $headers[0] = trim($h[0]);
                    }
                }
            }
            return $headers;
        }
    }

    /**
     * 将制定文件转化为文件流
     * @param $filePath , 附件路径
     * @return string
     */
    public static function createFileStream($filePath)
    {
        // 配置文件
        $handle = fopen($filePath, 'rb');
        $content = stream_get_contents($handle);// 对 PHP 5 及更高版本
        return $content;
    }

    /**
     * 将 post 请求转化为流文件
     * @param $postData
     * @param $filePath
     * @param $boundary
     * @return string
     */
    public static function setPostStream($postData, $filePath, $boundary)
    {
        $fileName = basename($filePath);
        // 配置文件
        $eol = "\r\n";
        $data = '';
        $attachment = $postData['attachments'];
        unset($postData['attachments']);

        foreach ($postData as $key => $value) {
            $data .= "--" . $boundary . "\r\n"
                . 'Content-Disposition: form-data; name="' . $key . '"' . $eol . $eol
                . $value . $eol;
        }

        //拼接 attachments 文件流
        $data .= '--' . $boundary . $eol;
        $data .= 'Content-Disposition: form-data; name="attachments"; filename="' . $fileName . '"' . $eol;
        $data .= 'Content-Type: ' . mime_content_type($filePath) . $eol . $eol;
        $data .= $attachment . $eol;
        $data .= "--" . $boundary . "--" . $eol;//这行前面的 "--" 减去后面的 "--" 即表示在头信息中的 boundary 的 "--"
        return $data;
    }

    /**
     * 获取指定目录下的文件名，以数组返回
     * @param $dir ,所要获取的指定目录
     * @param null $suffix ,需要去除的后缀名
     * @return array
     */
    public static function getFileNames($dir, $suffix = null)
    {
        $fileNames = array();
        if (is_dir($dir)) {
            if ($handle = opendir($dir)) {
                while (($file = readdir($handle)) !== false) {
                    if ($file != '.' && $file != '..') {
                        $suffix != null ? $fileNames[] = str_replace($suffix, '', $file) : $fileNames[] = $file;
                    }
                }
                closedir($handle);
            }
        }
        return $fileNames;
    }

    /**
     * 将图片转为 base64 string
     * @param $img_file , 图片名，如 ../img/horse.jpg
     * @return null|string
     */
    public static function imgToBase64($img_file)
    {
        $img_base64 = null;
        if (file_exists($img_file)) {
            $fileSize = filesize($img_file);//bytes
            $imgSize = $img_base64 = $img_type = null;
            if ($fileSize >= 1048576) {
                $imgSize = $fileSize / pow(1024, 2) . ' MB';//换算成单位为 MB
            } else {
                $imgSize = $fileSize / pow(1024, 1) . 'KB';//换算成单位为 KB
            }
            echo "Image($img_file) size is " . $imgSize . PHP_EOL;

            $img_info = getimagesize($img_file); // 取得图片的大小，类型等

            $fp = fopen($img_file, "r"); // 图片是否可读权限

            if ($fp) {
                $content = fread($fp, $fileSize);
                $file_content = chunk_split(base64_encode($content)); // base64编码
                switch ($img_info[2]) {           //判读图片类型
                    case 1:
                        $img_type = "gif";
                        break;
                    case 2:
                        $img_type = "jpg";
                        break;
                    case 3:
                        $img_type = "png";
                        break;
                }

                $img_base64 = 'data:image/' . $img_type . ';base64,' . $file_content;//合成图片的base64编码

            }
            fclose($fp);
        }

        return $img_base64; //返回图片的base64
    }

    /**
     * 将 base64 的 string 解码为指定的图片
     * @param $base64_string
     * @param $file
     */
    public static function base64ToImg($base64_string, $file)
    {
        $base64_string = explode(',', $base64_string); //截取data:image/png;base64, 这个逗号后的字符
        $data = base64_decode($base64_string[1]);//对截取后的字符使用base64_decode进行解码
        file_put_contents($file, $data); //写入文件并保存
    }
}
