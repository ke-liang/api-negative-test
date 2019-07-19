<?php
/**
 * Created by PhpStorm.
 * User: AaronXu
 * Date: 5/11/16
 * Time: 1:36 PM
 */

namespace PingppTest;
require 'Pingpp.php';

class HttpRequest
{
    /**
     * 请求带签名的对外接口
     * @param $isEnglish , false 表示报错信息为中文,true 表示报错信息为英文
     * @param string $method , 请求方法
     * @param $url , 除去host部分的url,切记是以"/"开始的
     * @param null $bodyArr , array 格式的 body 请求体
     * @param string $testType , 如果不是 negative 类型，则校验 http 状态码必须是 200
     * @return mixed
     * @throws \Exception
     */
    public static function curlHttpRequestFailedForAPI($isEnglish, $method, $url, $bodyArr = null, $testType = 'negative')
    {
        $method = strtolower($method);

        $headers[] = 'Authorization: Bearer ' . Pingpp::$apiKey;
        $headers[] = 'Content-type: application/json;charset=UTF-8';

        if (is_bool($isEnglish) === true && $isEnglish) {
            $headers[] = 'Accept-Language:en-US,en';//使用英文报错信息;
        }

        $request_TimeStamp = time();
        $headers[] = 'Pingplusplus-Request-Timestamp:' . $request_TimeStamp;

        if ((null !== $bodyArr) && ($method == 'post' || $method == 'put')) {
            $headers[] = 'Pingplusplus-Signature: ' . Util::genSignatureForAPI(json_encode($bodyArr), $url, $request_TimeStamp);
        } else {
            if ((null != $bodyArr && is_array($bodyArr)) && ($method == 'get' || $method == 'delete')) {
                $headers[] = 'Pingplusplus-Signature: ' . Util::genSignatureForAPI(null, $url . http_build_query($bodyArr), $request_TimeStamp);
            } elseif ((null != $bodyArr) && (!is_array($bodyArr)) && ($method == 'get' || $method == 'delete')) {
//                $bodyArr不是数组,只是一个字符串
                $headers[] = 'Pingplusplus-Signature: ' . Util::genSignatureForAPI(null, $url . $bodyArr, $request_TimeStamp);
            } elseif ((null == $bodyArr) && ($method == 'put' || $method == 'get' || $method == 'delete')) {
                $headers[] = 'Pingplusplus-Signature: ' . Util::genSignatureForAPI(null, $url, $request_TimeStamp);
            }
        }

        $absURL = Pingpp::$apiBaseUrl . $url; //组成完整的url
        $headers[] = 'Expect:';//request body 过大时会返回 http 状态 100 和 200 两条("HTTP/1.1 100 Continue" 和 "HTTP/1.1 200 OK")

        $handle = curl_init();//初始化CURL句柄
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);//设为TRUE把curl_exec()结果转化为字串，而不是直接输出
        curl_setopt($handle, CURLINFO_HEADER_OUT, 1); //设置为True可以通过 curl_getinfo 获取请求头信息
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers); //设置头信息

        if ($method == 'get') {
            if (is_array($bodyArr)) {
                curl_setopt($handle, CURLOPT_URL, $absURL . http_build_query($bodyArr));
            } else {
                curl_setopt($handle, CURLOPT_URL, $absURL . $bodyArr);
            }
        } elseif ($method == 'post' || $method == 'put') {
            $data = json_encode($bodyArr);
            curl_setopt($handle, CURLOPT_URL, $absURL);//设置请求的URL
            if ($method == 'post') {
                curl_setopt($handle, CURLOPT_POST, 1);//post提交方式
            } else {
                curl_setopt($handle, CURLOPT_CUSTOMREQUEST, "PUT");//设置请求方式
            }
            curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
        } elseif ($method == 'delete') {
            curl_setopt($handle, CURLOPT_URL, $absURL);//设置请求的URL
            curl_setopt($handle, CURLOPT_CUSTOMREQUEST, "DELETE");//设置请求方式
        } else {
            throw new \Exception("Unrecognized method $method");
        }

        $response = curl_exec($handle);//运行curl

        if (!curl_errno($handle)) {
            $info = curl_getinfo($handle);
            print_r("Took " . $info['total_time'] . " seconds(total_time) to send a request to " . $info['url'] . " and http status code is " . $info['http_code'] . "\n");
            if (strtolower($testType) === 'negative') {
                if ($info['http_code'] !== 400 && $info['http_code'] !== 404) {//不是 400 也不是 404 的时候报错
                    print_r("请求参数为：\n");
                    Util::formatPrint_r($bodyArr);
                    print_r("请求响应为：\n" . $response . "\n");
                    throw new \Exception("Http 状态码不正确，期望的是 400 或者 404！\n");//找不到的时候报 404，其它报 400
                }
            } else {
                if ($info['http_code'] !== 200) {
                    print_r("请求参数为：\n");
                    Util::formatPrint_r($bodyArr);
                    print_r("请求响应为：\n" . $response . "\n");
                    throw new \Exception("Http 状态码不正确，期望的是 200！\n");
                }
            }
        } else {
            print_r("Curl error: error code is " . curl_errno($handle) . "; " . curl_error($handle) . "\n");
            print_r($absURL . "\n");
            if ($bodyArr !== null) {
                Util::formatPrint_r($bodyArr);
            }
            Util::formatPrint_r(curl_getinfo($handle));
        }

        curl_close($handle);
        print_r("请求响应为：\n" . $response . "\n");
        return json_decode($response, true);
    }

//    ===============内部接口并发===============

    /**
     * 用于内部接口的测试，以及不需要 API 签名的接口
     * @param $threadCount , 定义将要创建多少个线程来并发请求
     * @param $absURL , 完整的 URL
     * @param $requestBodyArr
     * @throws \Exception
     * @return array
     */
    public static function innerAPIPOSTByMultiThreads($threadCount, $absURL, $requestBodyArr)
    {
        $curlArr = $headers = array();

        //create the multiple cURL handle
        $mh = curl_multi_init();

        //一个用来判断操作是否仍在执行的标识的引用。
        $active = null;
        //得到是几维数组
//        $dimesion=Util::getMaxDimension($arr);

        for ($i = 0; $i < $threadCount; $i++) {
            $curlArr[$i] = curl_init();
            curl_setopt($curlArr[$i], CURLOPT_RETURNTRANSFER, 1);//设为TRUE把curl_exec()结果转化为字串，而不是直接输出
            curl_setopt($curlArr[$i], CURLOPT_POST, 1);//post提交方式
            if (is_array($absURL)) {
                curl_setopt($curlArr[$i], CURLOPT_URL, $absURL[$i]);//设置请求的URL
            } else {
                curl_setopt($curlArr[$i], CURLOPT_URL, $absURL);//设置请求的URL
            }
            if (is_array($requestBodyArr)) {
                curl_setopt($curlArr[$i], CURLOPT_POSTFIELDS, http_build_query($requestBodyArr[$i]));
            } else {
                curl_setopt($curlArr[$i], CURLOPT_POSTFIELDS, http_build_query($requestBodyArr));
            }

            //向curl批处理会话中添加单独的curl句柄
            curl_multi_add_handle($mh, $curlArr[$i]);
        }

        //execute the handles
        //curl_multi_exec — 运行当前 cURL 句柄的子连接
        do {
            $mrc = curl_multi_exec($mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active && $mrc == CURLM_OK) {
            if (curl_multi_select($mh) != -1) {
                do {
                    $mrc = curl_multi_exec($mh, $active);
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }
        }
        $responseArr = array();
        /**
         * curl_multi_getcontent-如果设置了CURLOPT_RETURNTRANSFER，则返回获取的输出的文本流
         * curl_multi_remove_handle-移除curl批处理句柄资源中的某个句柄资源
         */
        for ($i = 0; $i < $threadCount; $i++) {
            $info = curl_getinfo($curlArr[$i]);
//            print_r($info['request_header']."\n");
            print_r("Took " . $info['total_time'] . " seconds to send a request to " . urldecode($info['url']) . " and http status code is " . $info['http_code'] . "\n");
            print_r("Thread#" . $i . " content is \n" . curl_multi_getcontent($curlArr[$i]) . "\n");
            curl_multi_remove_handle($mh, $curlArr[$i]);
            $responseArr[] = curl_multi_getcontent($curlArr[$i]) . "\n";//值为 string 类型的 数组
            curl_close($curlArr[$i]);
        }
        //关闭一组cURL句柄
        curl_multi_close($mh);
        return $responseArr;
    }

//    ===============其它接口请求===============

    /**
     * Head 头请求
     * @param $absURL
     * @return mixed
     * @throws \Exception
     */
    public static function curlHttpHeadRequest($absURL)
    {
        $handle = curl_init();//初始化CURL句柄
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);//设为TRUE把curl_exec()结果转化为字串，而不是直接输出
        curl_setopt($handle, CURLOPT_URL, $absURL);//设置请求的URL
        curl_setopt($handle, CURLOPT_NOBODY, true);
        curl_exec($handle);//运行curl
        $info = null;
        if (!curl_errno($handle)) {
            $info = curl_getinfo($handle);
            print_r(json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
            print_r("Took " . $info['total_time'] . " seconds to send a request to " . $info['url'] . " and http code is " . $info['http_code'] . "\n");
        } else {
            print_r("Curl error: " . curl_error($handle) . "\n");
        }
        curl_close($handle);

        return $info;
    }

    /**
     * 简单的 POST 请求，body 为 json 格式
     * @param $absURL
     * @param $body
     * @param $contentType
     * @return mixed
     * @throws \Exception
     */
    public static function curlHttpPOSTRequest($absURL, $body, $contentType = 'json')
    {
        $handle = curl_init();//初始化CURL句柄
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);//设为TRUE把curl_exec()结果转化为字串，而不是直接输出
        curl_setopt($handle, CURLOPT_URL, $absURL);//设置请求的URL

        if ($contentType == 'json') {
            $headers[] = 'Content-type: application/json;charset=UTF-8';
        } elseif ($contentType == 'xml') {
            $headers[] = 'Content-type: text/xml;charset=UTF-8';
        } elseif ($contentType == 'form') {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded;charset=utf-8';
        }

        if (Pingpp::$greyVersion != null) {
            $headers[] = 'X-Deploy-Version: ' . Pingpp::$greyVersion;//设置灰度测试的版本号
        }

        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers); //设置头信息

        curl_setopt($handle, CURLOPT_POSTFIELDS, $body);

        $response = curl_exec($handle);//运行curl
        $info = null;
        if (!curl_errno($handle)) {
            $info = curl_getinfo($handle);
            print_r("Took " . $info['total_time'] . " seconds to send a request to " . $info['url'] . " and http code is " . $info['http_code'] . "\n");
        } else {
            print_r("Curl error: " . curl_error($handle) . "\n");
        }
        curl_close($handle);

        print_r($response . "\n");
        return $info;
    }
}