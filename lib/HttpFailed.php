<?php
/**
 * Created by PhpStorm.
 * User: AaronXu
 * Date: 2019/4/23
 * Time: 3:45 PM
 */

namespace PingppTest;
require 'HttpRequest.php';

//用于测试 对外接口 的异常场景的请求
class HttpFailed
{
    private static $request_method = null;

    /**
     * 缺少某一个必填字段的情况下发送请求
     * @param $rawBody , 字段值是 condition 的原始请求参数的关联数组
     * @param $method , 请求方法
     * @param $url ,请求的 url
     * @throws \Exception
     */
    public static function requestWithoutRequiredField($rawBody, $method, $url)
    {
        self::$request_method = $method;
        echo '<p style="color: blue">====================缺少必填的请求====================</p>';
        $defaultValueBody = self::setDefaultValue($rawBody, true);
        $requestBodys = self::missingRequiredFields($defaultValueBody);
        foreach ($requestBodys as $key => $requestBody) {
            echo "==========缺少必填参数 $key==========\n";
            $errorRes = HttpRequest::curlHttpRequestFailedForAPI(false, strtoupper($method), $url, $requestBody);
            if (mb_stripos($errorRes['error']['message'], '缺少请求参数') === false) {
                print_r('<p style="color: red">缺少必填参数 ' . $key . ' 时验证失败</p>');
            }
        }
    }

    /**
     * 必填字段值取最小值的情况下发送请求
     * @param $rawBody , 字段值是 condition 的原始请求参数的关联数组
     * @param $method , 请求方法
     * @param $url ,请求的 url
     * @throws \Exception
     */
    public static function requestWithMinFieldValue($rawBody, $method, $url)
    {
        self::$request_method = $method;
        echo '<p style="color: blue">====================必填字段值最小长度的请求====================</p>';
        $minValueBody = self::setMinValue($rawBody);
        $positiveRes = HttpRequest::curlHttpRequestFailedForAPI(false, strtoupper($method), $url, $minValueBody, 'positive');
    }

    /**
     * 字段值取最大值的情况下发送请求
     * @param $rawBody , 字段值是 condition 的原始请求参数的关联数组
     * @param $method , 请求方法
     * @param $url ,请求的 url
     * @throws \Exception
     */
    public static function requestWithMaxFieldValue($rawBody, $method, $url)
    {
        self::$request_method = $method;
        echo '<p style="color: blue">====================字段值最大长度的请求====================</p>';
        $maxValueBody = self::setMaxValue($rawBody);
        $positiveRes = HttpRequest::curlHttpRequestFailedForAPI(false, strtoupper($method), $url, $maxValueBody, 'positive');
    }

    /**
     * 使用不正确的字段值做请求
     * @param $rawBody , 字段值是 condition 的原始请求参数的关联数组
     * @param $method
     * @param $url
     * @throws \Exception
     */
    public static function requestWithWrongFieldValue($rawBody, $method, $url)
    {
        self::$request_method = $method;
        echo '<p style="color: blue">====================参数值无效的请求====================</p>';
        $defaultValueBody = self::setDefaultValue($rawBody);
        foreach ($rawBody as $key => $condition) {
            $requestBody = $defaultValueBody;//防止第一层多次遍历后所有值都无效
            if (is_array($rawBody[$key])) {
                foreach ($rawBody[$key] as $secondKey => $secondCondition) {//json 第二层
                    $requestBody = $defaultValueBody;//防止第二层多次遍历后所有值都无效
                    $secondKeyValues = $rawBody[$key][$secondKey];//第二层字段的值
                    if (is_array($secondKeyValues)) {//当第二层为数组时进行第三层的赋值
                        foreach ($secondKeyValues as $thirdKey => $thirdCondition) {//json 第三层
                            $requestBody = $defaultValueBody;//防止第三层多次遍历后所有值都无效
                            $thirdKeyValues = $rawBody[$key][$secondKey][$thirdKey];//第三层字段的值
                            if (is_array($thirdKeyValues)) {//当第三层为数组时进行第四层的赋值
                                foreach ($thirdKeyValues as $forthKey => $forthCondition) {
                                    $requestBody = $defaultValueBody;//防止第三层多次遍历后所有值都无效
                                    $forthWrongValues = self::setWrongFieldValueByConditions($forthCondition);
                                    for ($i = 0; $i < count($forthWrongValues); $i++) {
                                        $requestBody[$key][$secondKey][$thirdKey][$forthKey] = $forthWrongValues[$i];//第四层字段的值不再判断是不是数组
                                        echo "==========参数 $key.$secondKey.$thirdKey.$forthKey 无效的值请求==========\n";
                                        self::sendRequest($method, $url, $requestBody);
                                    }
                                }
                            } else {
                                $thirdWrongValues = self::setWrongFieldValueByConditions($thirdCondition);
                                for ($i = 0; $i < count($thirdWrongValues); $i++) {
                                    $requestBody[$key][$secondKey][$thirdKey] = $thirdWrongValues[$i];
                                    echo "==========参数 $key.$secondKey.$thirdKey 无效的值请求==========\n";
                                    self::sendRequest($method, $url, $requestBody);
                                }
                            }
                        }
                    } else {
                        $secondWrongValues = self::setWrongFieldValueByConditions($secondCondition);
                        for ($i = 0; $i < count($secondWrongValues); $i++) {
                            $requestBody[$key][$secondKey] = $secondWrongValues[$i];
                            echo "==========参数 $key.$secondKey 无效的值请求==========\n";
                            self::sendRequest($method, $url, $requestBody);
                        }
                    }
                }
            } else {
                $wrongValues = self::setWrongFieldValueByConditions($condition);
                for ($i = 0; $i < count($wrongValues); $i++) {
                    $requestBody[$key] = $wrongValues[$i];
                    echo "==========参数 $key 无效的值请求==========\n";
                    self::sendRequest($method, $url, $requestBody);
                }
            }
        }
    }

    /**
     * 发送 API 请求，并校验结果
     * @param $httpMethod
     * @param $url
     * @param $requestBody
     * @throws \Exception
     */
    public static function sendRequest($httpMethod, $url, $requestBody)
    {
        $errorRes = HttpRequest::curlHttpRequestFailedForAPI(false, strtoupper($httpMethod), $url, $requestBody);
        if ('invalid_request_error' != $errorRes['error']['type']) {
            print_r('<p style="color: red">字段值错误时验证失败，期望的 extra.type = invalid_request_error</p>');
        }
    }

    /**
     * 将每一个必填字段都缺少一次，返回一个 key-value 的关联数组，可用 foreach 方式去遍历请求
     * @param $defaultValueBody , 一个包含必填字段的正常请求数组，目前仅支持两层
     * @return array
     */
    public static function missingRequiredFields($defaultValueBody)
    {
        $missingFieldsMap = array();
        $requestKeys = array_keys($defaultValueBody);
        for ($i = 0; $i < count($requestKeys); $i++) {//第一次必填字段缺少的处理
            $requestBody = $defaultValueBody;//最完整的请求 body
            $firstMissingField = $requestKeys[$i];
            $firstKeyValues = $requestBody[$firstMissingField];//第一层字段的值
            if (is_array($firstKeyValues)) {//判断第一层的值是不是数组，是数组的话则要进行第二层解析
                $requestBody = $defaultValueBody;
                $secondKeys = array_keys($firstKeyValues);
                for ($j = 0; $j < count($secondKeys); $j++) {//第二层必填字段缺少的处理
                    $requestBody = $defaultValueBody;//最完整的请求 body
                    $firstKeyValues = $requestBody[$firstMissingField];//第一层字段的值
                    $secondMissingField = $secondKeys[$j];
                    $secondKeyValues = $firstKeyValues[$secondMissingField];//第二层字段的值
                    if (is_array($secondKeyValues)) {//判断第二层的值是不是数组，是数组的话则要进行第二层解析
                        $thirdKeys = array_keys($secondKeyValues);
                        for ($z = 0; $z < count($thirdKeys); $z++) {//第三层必填字段缺少的处理
                            $requestBody = $defaultValueBody;//最完整的请求 body
                            $firstKeyValues = $requestBody[$firstMissingField];//第一层字段的值
                            $secondKeyValues = $firstKeyValues[$secondMissingField];//第二层字段的值
                            $thirdMissingField = $thirdKeys[$z];
                            $thirdKeyValues = $secondKeyValues[$thirdMissingField];//第三层字段的值
                            if (is_array($thirdKeyValues)) {//判断第三层的值是不是数组，是数组的话则要进行第三层解析
                                $forthKeys = array_keys($thirdKeyValues);
                                for ($m = 0; $m < count($forthKeys); $m++) {//第四层必填字段缺少的处理
                                    $requestBody = $defaultValueBody;//最完整的请求 body
                                    $firstKeyValues = $requestBody[$firstMissingField];//第一层字段的值
                                    $secondKeyValues = $firstKeyValues[$secondMissingField];//第二层字段的值
                                    $thirdKeyValues = $secondKeyValues[$thirdMissingField];//第三层字段的值
                                    $forthMissingField = $forthKeys[$z];//第四层字段，不做判断其值是否为数组，不再往下解析
                                    unset($thirdKeyValues[$forthMissingField]);
                                    $requestBody[$firstMissingField][$secondMissingField][$thirdMissingField] = $thirdKeyValues;
                                    $missingFieldsMap[$firstMissingField . '.' . $secondMissingField . '.' . $thirdMissingField . '.' . $forthMissingField] = $requestBody;
                                }
                                if (count($forthKeys) > 1) {//加一次该字段为 [] 的请求
                                    $requestBody[$firstMissingField][$secondMissingField][$thirdMissingField] = [];
                                    $missingFieldsMap[$firstMissingField . '.' . $secondMissingField . '.' . $thirdMissingField . '=[]'] = $requestBody;
                                }
                            } else {
                                unset($secondKeyValues[$thirdMissingField]);
                                $requestBody[$firstMissingField][$secondMissingField] = $secondKeyValues;
                                $missingFieldsMap[$firstMissingField . '.' . $secondMissingField . '.' . $thirdMissingField] = $requestBody;
                            }
                        }
                        if (count($thirdKeys) > 1) {//加一次该字段为 [] 的请求
                            $requestBody[$firstMissingField][$secondMissingField] = [];
                            $missingFieldsMap[$firstMissingField . '.' . $secondMissingField . '=[]'] = $requestBody;
                        }
                    } else {
                        unset($firstKeyValues[$secondMissingField]);
                        $requestBody[$firstMissingField] = $firstKeyValues;
                        $missingFieldsMap[$firstMissingField . '.' . $secondMissingField] = $requestBody;
                    }
                }
                if (count($secondKeys) > 1) {//加一次该字段为 [] 的请求
                    $requestBody[$firstMissingField] = [];
                    $missingFieldsMap[$firstMissingField . '=[]'] = $requestBody;
                }
            } else {
                unset($requestBody[$firstMissingField]);
                $missingFieldsMap[$firstMissingField] = $requestBody;
            }
        }
        if (count($requestKeys) > 1) {//真个请求 body 置为 [] 的请求
            $requestBody = [];
            $missingFieldsMap['[]'] = $requestBody;
        }
        return $missingFieldsMap;
    }

    /**
     * 给每个字段赋常规值
     * @param $rawBody , 数组
     * @param bool $allRequired , 默认值为 false 为所有字段设置默认值，否则就认为仅为必填字段设置默认值，即表示仅测试必填字段
     * @return array
     * @throws \Exception
     */
    private static function setDefaultValue($rawBody, $allRequired = false)
    {
        $defaultValueBody = array();
        foreach ($rawBody as $key => $conditions) {
            if (is_array($conditions)) {
                $defaultValueBody[$key] = self::setDefaultValue($conditions, $allRequired);
            } else {
                $conditionArr = self::trimArray(explode(',', $conditions));
                $paramType = strtolower($conditionArr[0]);
                if ($allRequired && substr($paramType, 0, 2) === 'o_') {
                    unset($defaultValueBody[$key]);//去除可选字段
                    continue;
                }
                substr($paramType, 0, 2) === 'o_' ? $paramType = substr($paramType, 2) : null;//把类型前面的 o_ 去除
                switch ($paramType) {
                    case 'int':
                    case 'timestamp':
                        $defaultValueBody[$key] = intval($conditionArr[2]);
                        break;
                    case 'string':
                    case 'string_int':
                    case 'email':
                    case 'url':
                    case 'date':
                        $defaultValueBody[$key] = $conditionArr[2];
                        break;
                    case 'boolean':
                        $conditionArr[2] === "true" ? $defaultValueBody[$key] = true : $defaultValueBody[$key] = false;
                        break;
                    default:
                        throw new \Exception("$key 字段设置了还没有定义的字段类型！\n");
                        break;
                }
            }
        }//给每个字段赋常规值
        return $defaultValueBody;
    }

    /**
     * 为每个字段设置最大值
     * @param $rawBody , 数组
     * @throws \Exception
     * @return array
     */
    private static function setMaxValue($rawBody)
    {
        $maxValueBody = array();
        foreach ($rawBody as $key => $conditions) {
            if (is_array($conditions)) {
                $maxValueBody[$key] = self::setMaxValue($conditions);
            } else {
                $conditions = str_replace('～', '~', $conditions);//将中文全角的 ～ 修改为英文半角的 ~，以免接下来解析失败
                $conditionArr = self::trimArray(explode(',', $conditions));
                $paramType = strtolower($conditionArr[0]);
                $paramValue = $conditionArr[1];
                substr($paramType, 0, 2) === 'o_' ? $paramType = substr($paramType, 2) : null;//把类型前面的 o_ 去除
                if (stripos($paramType, 'string') !== false) {
                    if (stripos($paramValue, '~') !== false) {//表明是取值范围
                        $rang = explode('~', $paramValue);
                        $paramType == 'string_int' ? $maxValueBody[$key] = Util::getNumber($rang[1]) : $maxValueBody[$key] = Util::getString($rang[1]);
                    } elseif (stripos($paramValue, 'f_') !== false) {//表明是取特定的值如 f_name，也可以表示取特定长度的值，如 f_20
                        $maxValueBody[$key] = $conditionArr[2];
                    } elseif (stripos($paramValue, 'max_') !== false || stripos($paramValue, 'min_') !== false) {//表明是最大/小长度的值
                        $paramType == 'string_int' ? $maxValueBody[$key] = Util::getNumber(substr($paramValue, 4)) : $maxValueBody[$key] = Util::getString(substr($paramValue, 4));
                    } else {
                        throw new \Exception("$paramValue 是错误的取值范围类型或者还未支持！\n");
                    }
                } elseif ($paramType === 'int' || $paramType === 'timestamp') {
                    if (stripos($paramValue, '~') !== false) {//表明是取值范围
                        $rang = explode('~', $paramValue);
                        $maxValueBody[$key] = intval($rang[1]);
                    } elseif (stripos($paramValue, 'f_') !== false) {//表明是取特定的值
                        $maxValueBody[$key] = intval(substr($paramValue, 2));
                    } elseif (stripos($paramValue, 'max_') !== false || stripos($paramValue, 'min_') !== false) {//表明是最大值
                        $maxValueBody[$key] = intval(substr($paramValue, 4));
                    } else {
                        throw new \Exception("$paramValue 是错误的取值范围类型或者还未支持！\n");
                    }
                } elseif ($paramType === 'url') {//只有取值长度范围
                    if (stripos($paramValue, '~') !== false) {//表明是取值范围
                        $rang = explode('~', $paramValue);
                        //"https://baidu.com/query?id=" 长度是 27
                        $rang[1] >= 27 ? $maxValueBody[$key] = 'https://baidu.com/query?id=' . Util::getString($rang[1] - 27) :
                            $maxValueBody[$key] = $conditionArr[2];
                    } else {
                        throw new \Exception("$paramValue 是错误的取值范围类型或者还未支持！\n");
                    }
                } elseif ($paramType === 'email') {//只有取值长度范围
                    if (stripos($paramValue, '~') !== false) {//表明是取值范围
                        $rang = explode('~', $paramValue);
                        $rang[1] > 10 ? $maxValueBody[$key] = Util::getString($rang[1] - 10) . '@gmail.com' : $maxValueBody[$key] = $conditionArr[2];
                    } else {
                        throw new \Exception("$paramValue 是错误的取值范围类型或者还未支持！\n");
                    }
                } elseif ($paramType === 'boolean') {
                    if (stripos($paramValue, 'f_') !== false) {//表明是取特定的值
                        $conditionArr[2] === "true" ? $minValueBody[$key] = true : $minValueBody[$key] = false;
                    } else {
                        throw new \Exception("$paramValue 只支持设置固定值，如 f_xxx！\n");
                    }
                } elseif ($paramType === 'date') {
                    if (stripos($paramValue, 'f_') !== false) {//表明是取特定的值
                        $minValueBody[$key] = substr($paramValue, 2);
                    } else {
                        throw new \Exception("$paramValue 只支持设置固定值，如 f_xxx！\n");
                    }
                }
            }
        }//给每个字段赋常规值

        return $maxValueBody;
    }

    /**
     * 为每个字段设置最小值
     * @param $rawBody , 数组
     * @throws \Exception
     * @return array
     */
    private static function setMinValue($rawBody)
    {
        $minValueBody = array();
        foreach ($rawBody as $key => $conditions) {
            if (is_array($conditions)) {
                $minValueBody[$key] = self::setMinValue($conditions);
            } else {
                $conditions = str_replace('～', '~', $conditions);//将中文全角的 ～ 修改为英文半角的 ~，以免接下来解析失败
                $conditionArr = self::trimArray(explode(',', $conditions));
                $paramType = strtolower($conditionArr[0]);
                substr($paramType, 0, 2) === 'o_' ? $paramType = substr($paramType, 2) : null;//把类型前面的 o_ 去除
                $paramValue = $conditionArr[1];
                if (stripos($paramType, 'string') !== false) {
                    if (stripos($paramValue, '~') !== false) {//表明是取值范围
                        $rang = explode('~', $paramValue);
                        $paramType == 'string_int' ? $minValueBody[$key] = Util::getNumber($rang[0]) : $minValueBody[$key] = Util::getString($rang[0]);
                    } elseif (stripos($paramValue, 'f_') !== false) {//表明是取特定的值如 f_name，也可以表示取特定长度的值，如 f_20
                        $minValueBody[$key] = $conditionArr[2];
                    } elseif (stripos($paramValue, 'max_') !== false || stripos($paramValue, 'min_') !== false) {//表明是最大/小长度的值
                        $paramType == 'string_int' ? $minValueBody[$key] = Util::getNumber(substr($paramValue, 4)) : $minValueBody[$key] = Util::getString(substr($paramValue, 4));
                    } else {
                        throw new \Exception("$paramValue 是错误的取值范围类型或者还未支持！\n");
                    }
                } elseif ($paramType === 'int' || $paramType === 'timestamp') {
                    if (stripos($paramValue, '~') !== false) {//表明是取值范围
                        $rang = explode('~', $paramValue);
                        $minValueBody[$key] = intval($rang[0]);
                    } elseif (stripos($paramValue, 'f_') !== false) {//表明是取特定的值
                        $minValueBody[$key] = intval(substr($paramValue, 2));
                    } elseif (stripos($paramValue, 'max_') !== false || stripos($paramValue, 'min_') !== false) {//表明是最大值
                        $minValueBody[$key] = intval(substr($paramValue, 4));
                    } else {
                        throw new \Exception("$paramValue 是错误的取值范围类型或者还未支持！\n");
                    }
                } elseif ($paramType === 'url' || $paramType === 'email' || $paramType === 'date') {//只有取值长度范围
                    $minValueBody[$key] = $conditionArr[2];
                } elseif ($paramType === 'boolean') {
                    if (stripos($paramValue, 'f_') !== false) {//表明是取特定的值
                        $conditionArr[2] === "true" ? $minValueBody[$key] = true : $minValueBody[$key] = false;
                    } else {
                        throw new \Exception("$paramValue 只支持设置固定值，如 f_xxx！\n");
                    }
                }
            }
        }//给每个字段赋常规值

        return $minValueBody;
    }

    /**
     * condition 的格式如："string,1~64,正常值"，分别表示 字段类型，字段范围(固定长度，长度范围，指定的值(f_xx))，正常情况下应该填写的值
     * @param $conditions , 一个格式如 "string,1~64,orderNo123" 的 string
     * @throws \Exception
     * @return array, 返回的根据 condition 而设置的无效数据的数组
     */
    private static function setWrongFieldValueByConditions($conditions)
    {
        $wrongValues = $value = array();
        $conditions = str_replace('～', '~', $conditions);//将中文全角的 ～ 修改为英文半角的 ~，以免接下来解析失败
        $conditionArr = self::trimArray(explode(',', $conditions));
        $paramType = strtolower($conditionArr[0]);
        $paramValue = $conditionArr[1];
        if (stripos($paramType, 'string') !== false) {//包括 string/o_string/string_int/o_string_int
            if (stripos($paramValue, '~') !== false) {//表明是取值范围
                $rang = explode('~', $paramValue);
                $value[0] = Util::getString($rang[1] + 1);
                if ($rang[0] > 1 && ($paramType === 'string' || $paramType === 'string_int')) {
                    $value[1] = Util::getString($rang[0] - 1);
                    $value[2] = Util::getNumber($rang[0] - 1);
                }
            } elseif (stripos($paramValue, 'f_') !== false) {//表明是取特定的值如 f_name，也可以表示取特定长度的值，如 f_20
                $value[0] = $conditionArr[2] . 'F';//无论是固定值还是固定长度都加个字符 F，也就是多一位
                if (is_numeric(substr($paramValue, 2)) && intval(substr($paramValue, 2)) > 1) {
                    $value[1] = Util::getString(substr($paramValue, 2) - 1);//如果是固定长度则再少一位
                    $value[2] = Util::getNumber(substr($paramValue, 2) - 1);
                }
            } elseif (stripos($paramValue, 'max_') !== false) {//表明是最大长度的值
                $value[0] = Util::getString(substr($paramValue, 4) + 1);
            } elseif (stripos($paramValue, 'min_') !== false || $paramType === 'string') {//表明是最小长度的值
                intval(substr($paramValue, 4)) > 0 ? $value[0] = Util::getString(substr($paramValue, 4) - 1) : null;
            } else {
                throw new \Exception("$paramValue 是错误的取值范围类型或者还未支持！\n");
            }
        } elseif ($paramType === 'int' || $paramType === 'o_int' || $paramType === 'timestamp' || $paramType === '0_timestamp') {
            if (stripos($paramValue, '~') !== false) {//表明是取值范围
                $rang = explode('~', $paramValue);
                $value[0] = rand($rang[1] + 1, $rang[1] + 10);//大于最大值
                if ($rang[0] > 1 && ($paramType === 'int' || $paramType === 'timestamp')) {//表明最小值不是 0，所以可以设置最小值为 0
                    $value[1] = rand(0, $rang[0] - 1);//小于最小值
                }
            } elseif (stripos($paramValue, 'f_') !== false) {//表明是取特定的值
                $value[0] = intval(substr($paramValue, 2)) + 1;
                if (intval(substr($paramValue, 2)) > 0) {
                    $value[1] = intval(substr($paramValue, 2)) - 1;//固定值 - 1
                }
            } elseif (stripos($paramValue, 'max_') !== false) {//表明是最大值
                $value[0] = intval(substr($paramValue, 4)) + 1;//最大值 + 1 即无效
            } elseif (stripos($paramValue, 'min_') !== false && ($paramType === 'int' || $paramType === 'timestamp')) {//表明是最小值且最小值是 > 0 的
                intval(substr($paramValue, 4)) > 0 ? $value[0] = intval(substr($paramValue, 4)) - 1 : null;//最小值 - 1 即无效
            } else {
                throw new \Exception("$paramValue 是错误的取值范围类型或者还未支持！\n");
            }
        } elseif ($paramType === 'url' || $paramType === 'o_url') {//只有取值长度范围
            if (stripos($paramValue, '~') !== false) {//表明是取值范围
                $rang = explode('~', $paramValue);
                //"https://baidu.com/query?id=" 长度是 27
                $rang[1] >= 27 ? $value[0] = 'https://baidu.com/query?id=' . Util::getString($rang[1] - 27 + 1) :
                    $value[0] = Util::getString($rang[1] + 1);
                if ($rang[0] > 1 && $paramType === 'url') {
                    $value[1] = Util::getString($rang[0] - 1);
                }
            } else {
                throw new \Exception("$paramValue 是错误的取值范围类型或者还未支持！\n");
            }
        } elseif ($paramType === 'email' || $paramType === 'o_email') {//只有取值长度范围
            if (stripos($paramValue, '~') !== false) {//表明是取值范围
                $rang = explode('~', $paramValue);
                $value[0] = Util::getString($rang[1] + 1);
                if ($rang[0] > 1 && $paramType === 'email') {
                    $value[1] = Util::getString($rang[0] - 1);
                }
            } else {
                throw new \Exception("$paramValue 是错误的取值范围类型或者还未支持！\n");
            }
        }

        substr($paramType, 0, 2) === 'o_' ? $paramType = substr($paramType, 2) : null;//把类型前面的 o_ 去除
        switch ($paramType) {
            case 'string':
            case 'string_int':
                $wrongValues = array_merge(self::wrongStringValues(), $value);
                break;
            case 'int':
                $wrongValues = array_merge(self::wrongIntValues(), $value);
                break;
            case 'timestamp':
                $wrongValues = array_merge(self::wrongTimeStampValues(), $value);
                break;
            case 'url':
                $wrongValues = array_merge(self::wrongURLValues(), $value);
                break;
            case 'email':
                $wrongValues = array_merge(self::wrongEmailValues(), $value);
                break;
            case 'boolean':
                $wrongValues = array_merge(self::wrongBooleanValues(), $value);
                break;
            case 'date'://日期格式
                $wrongValues = array_merge(self::wrongDateValues(), $value);
                break;
            default:
                throw new \Exception("$paramType 类型的字段是还未定义！\n");
                break;
        }
        return $wrongValues;
    }

    /**
     * 最基本的无效的数据
     * @return array
     */
    private static function wrongBasicValues()
    {
        if (strtoupper(self::$request_method) === 'GET') {
            return array(
                array("butgwe")
            );
        } else {
            return array(
                array(),
                array(null),
                array("butgwe")
            );
        }
    }

    /**
     * 无效的 string
     * @return array
     */
    private static function wrongStringValues()
    {
        $more = array();
        return array_merge(self::wrongBasicValues(), $more);
    }

    /**
     * 无效的正整数
     * @return array
     */
    private static function wrongIntValues()
    {
        $more = array(
            -1,
            "NoTInt"
        );
        return array_merge(self::wrongBasicValues(), $more);
    }

    /**
     * 无效的 timestamp
     * @return array
     */
    private static function wrongTimeStampValues()
    {
        $more = array(
            -1,
            "NoTInt"
        );
        return array_merge(self::wrongBasicValues(), $more);
    }

    /**
     * 无效的 url
     * @return array
     */
    private static function wrongURLValues()
    {
        $more = array(
            '\\pingxx.com'
        );
        return array_merge(self::wrongBasicValues(), $more);
    }

    /**
     * 无效的 email address
     * @return array
     */
    private static function wrongEmailValues()
    {
        $more = array(
            'test126.com',
            '@smic.com',
            'education@',
            'edu\/@cation@smic.com'
        );
        return array_merge(self::wrongBasicValues(), $more);
    }

    /**
     * 无效的 boolean
     * @return array
     */
    private static function wrongBooleanValues()
    {
        $more = array(
            'boolean',
            '</~#'
        );
        return array_merge(self::wrongBasicValues(), $more);
    }

    /**
     * 无效的 date
     * @return array
     */
    private static function wrongDateValues()
    {
        $more = array(
            '2019-05/09',
            '201905/09',
            '2019/05-09:16:16:16',
            '</~#'
        );
        return array_merge(self::wrongBasicValues(), $more);
    }

    /**
     * 去除数组中的每个值的前后空格
     * 具体可以参考 https://www.php.net/array_map
     * @param $Input
     * @return array|string
     */
    public static function trimArray($Input)
    {
        if (!is_array($Input))
            return trim($Input);
        return array_map(array(__CLASS__, 'trimArray'), $Input);
    }
}