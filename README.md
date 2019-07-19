### 项目背景
- 测试接口时经常需要逐一测试每一个必填字段缺失和每一个字段值不正确的情况，这样的异常情况非常之多，为了提高测试效率写了这个脚本。
- 可以用于开发自定义自测环境

### 测试的场景
- 必填字段缺失
- 字段值不正确
- 必填字段值去最小值
- 字段值取最大值

### 使用范围
- 仅适用于测试我们的对外接口的，因为接口标准比较高，所以此脚本可以仅仅做最基本且一致的检查即可
- 仅适用于请求 body 为 json 格式的接口测试

### 使用说明
- 目前最深仅解析到第四层，如下请求原始数据示例：
```
$rawBody = array(
            'class_info' => array(
                "class_name" => "string,1~64,order123no",
                "students_count" => "int,1~100,10",
                'class_address' => array(
                    array(
                        'city' => 'string,1~100,ShangHai',
                        'prov' => 'string,1~100,ShangHai',
                        'ip' => 'string,s_127.0.0.1,127.0.0.1',
                        'email' => 'email,-,education@smic.com'
                    )
                )
            ),
            "class_author" => "string,1~64,Aaron",
        );
```
- 第一层为 class\_info，class\_author；
- 第二层为 class\_name，students\_count，class\_address
- 第三层，这里的第三层其 key 其实是 0
- 第四层为 city，prov

**注意第四层字段的值不能再是数组**

- $rawBody 中每个字段的值都要遵循如下格式填写
    - "字段类型,字段取值范围/固定值,正常值"，比如 "string,1\~64,order123no", 逗号后面不要有空格，如果是选填字段则是 "string,1\~64,order123no"
    - condition 中的符号全是英文半角，不要写层中文全角(虽然做了 ~ 和 ～ 的兼容)
    - condition 中支持的数据类型为 string, string_int, int, timestamp, url, email, boolean，且都是小写，其中 ip 按照 string 处理
        - string 支持字段取值范围(1~64)/固定长度(f_20)/固定值(f_name)/最大长度(max_20)/最小长度(min_1) 五种
        - string_int 支持的范围同 string，表示的是仅数字字符串的 string 类型
        - int/timestamp 支持字段取值范围/最大数值/最小数值 三种
        - url/email 仅支持取值范围，用来测试其最大和最小长度
        - boolean 只需要填写 f_xx 固定值用来测试通用的无效值
        - date 只需要填写 f_xx 固定值用来测试通用的无效值
    - 字段取值范围用 1~64 中表示，固定长度用 f_20 表示，固定值用 f_xxx 表示(多个固定值的时候取其中一个即可)
    - 表示固定值/长度的字母解释：f 表示 fixed，max 表示最大值，min 表示最小值，且必须是小写
    - 正常值是指可以正常调用接口的值，每个字段写任意一个正常值即可
- requestWithoutRequiredField 此方法只能用于测试必填字段缺失的情况，不可以包含选填字段，也不能包含条件性必填的字段
- requestWithWrongFieldValue 此方法用来测试所有字段值错误的情况
- wrongXXXValues 方法内的无效值可以根据自己的业务进行补充
- setDefaultValue 方法是用来为每一个字段设置你填写的正常值，以便于在其它字段缺失或者值不正确时做请求，防止影响被测试的字段
- missingRequiredFields 方法是将每一个必填字段都设置为缺少一次，最终返回一个 key-value 的关联数组，以便于用 foreach 方式去遍历请求，测试每一个必填字段缺失的场景

### 自定义自测环境
- PHP 环境
- config/env.json 替换你的 APP_ID、API_KEY 和 HOST
- res 文件夹下更换成自己的公私钥，公钥配在 Dashboard 上，私钥用于签名
- lib/HttpFailed.php 中的 wrongXXXVaules 可以增减字段异常值，一般情况下不用修改
- 如果需要增加 JSON 模板，可按如下方法操作：
    - config 文件夹下添加模板，文件名要能自我解释是什么接口
    - config/urlMap.json 中添加 key-value，key 为上一步添加的 JSON 文件名，value 为 url
- 一键保存自定义的 JSON 模板
