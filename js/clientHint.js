var xhr = null;
var appId = null;

/**
 * 发送 http 请求内容
 */
function showHint() {
    xhr = GetXmlHttpObject();
    if (xhr == null) {
        alert("Browser does not support HTTP Request");
        return;
    }
    var url = "./src/handleRequest.php";

    var host = document.getElementById("host").innerText;
    var path = document.getElementById("path").value;
    var method = document.getElementById("method").value;
    var btn_submit = document.getElementById("btn_submit").value;
    var apiKey = document.getElementById("apiKey").value;
    var testType = document.getElementById("testType").value;
    var body = document.getElementById("body").value;
    var sendDataString = "testType=" + testType + "&host=" + host + "&path=" + path + "&method=" + method + "&btn_submit=" + btn_submit + "&apiKey=" + apiKey
        + "&body=" + encodeURIComponent(body);

    xhr.onreadystatechange = stateChanged;
    xhr.open("POST", url, true);
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.send(sendDataString);//参数可以是xml或者字符串，json等; body 中有 '+' 时会出现乱码，用 encodeURIComponent 编码就可以了
}

/**
 * 保存 json 内容作为 json template 文件
 */
function saveJSONTmpl() {
    xhr = GetXmlHttpObject();
    if (xhr == null) {
        alert("Browser does not support HTTP Request");
        return;
    }
    var url = "./src/saveJSONTmpl.php";

    var path = document.getElementById("path").value;
    var method = document.getElementById("method").value;
    var jsonTmplName = document.getElementById("jsonTmplName").value;
    var btn_save = document.getElementById("btn_save").value;
    var body = document.getElementById("body").value;
    var sendDataString = "&path=" + path + "&method=" + method + "&jsonTmplName=" + jsonTmplName + "&btn_save=" + btn_save + "&body=" + encodeURIComponent(body);

    if (isEmpty(jsonTmplName)) {
        alert("请务必先填写文件名！")
    } else {
        xhr.onreadystatechange = stateChanged;
        xhr.open("POST", url, true);
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhr.send(sendDataString);//参数可以是xml或者字符串，json等; body 中有 '+' 时会出现乱码，用 encodeURIComponent 编码就可以了
    }
}

function stateChanged() {
    if (xhr.readyState == 4 || xhr.readyState == "complete") {
        var result = null;
        switch (xhr.responseText) {
            case "confirm_001":
                result = confirm("你确定要覆盖原来的文件吗？");
                $.post("saveJSONTmpl.php", {
                        result: result,
                        jsonTmplName: document.getElementById("jsonTmplName").value,
                        body: document.getElementById("body").value
                    }
                )
                ;
                break;
            default:
                document.getElementById("txtHint").innerHTML = xhr.responseText;
                break;
        }
    }
}

function GetXmlHttpObject() {
    try {
        // Firefox, Opera 8.0+, Safari
        xhr = new XMLHttpRequest();
    }
    catch (e) {
        // Internet Explorer
        try {
            xhr = new ActiveXObject("Msxml2.XMLHTTP");
        }
        catch (e) {
            xhr = new ActiveXObject("Microsoft.XMLHTTP");
        }
    }
    return xhr;
}

//判断字符是否为空的方法
function isEmpty(obj) {
    if (typeof obj == "undefined" || obj == null || obj == "") {
        return true;
    } else {
        return false;
    }
}

/**
 * 设置基本的测试环境
 */
$.getJSON("config/env.json", function (data) {
    var configs = JSON.parse(JSON.stringify(data));
    $("#apiKey").val(configs["API_KEY"]);//设置配置文件中的 API_KEY
    $("#host").html(configs["HOST"]);//设置配置文件中的 HOST
    appId = configs["APP_ID"];//赋值配置文件中的 APP_ID
});

/**
 * 根据 urlMap.json 配置的接口名称设置常用接口名选项
 */
$.getJSON("config/urlMap.json", function (data) {
    var urlMapKeys = Object.keys(data);
    var str = "<option selected value='charge'>Charge</option>";
    for (var i = 1; i < urlMapKeys.length; i++) {
        str = str + '<option value=' + urlMapKeys[i] + ">" + urlMapKeys[i].slice(0, 1).toUpperCase() + urlMapKeys[i].slice(1) + "</option>";
    }
    $("#normalAPI").html(str);
});

//根据下拉选项赋值不同的 path
$("#normalAPI").on("change", function () {
    var urlKey = $(this).val();
    $.getJSON("config/urlMap.json", function (data) {
        var configs = JSON.parse(JSON.stringify(data));
        $("#path").val(configs[urlKey].replace("APP_ID", appId));
    })
});

//根据下拉选项展示不同的接口 json 内容
// JSON.stringify(style,null, 2) // 缩进2个空格
// JSON.stringify(style,null, '\t') // 按tab缩进
$("#normalAPI").on("change", function () {
    $.getJSON("config/jsonTmpl/" + $(this).val() + ".json", function (data) {
        var jsonStr = JSON.stringify(data, null, '\t');
        $("#body").val(jsonStr.replace("APP_ID", appId));
    })
});

// JSON template 内容改变后立马格式化显示
$("#body").change(function () {
    var jsonStr = $("#body").val();
    $("#body").val(JSON.stringify(JSON.parse(jsonStr), null, '\t'));
});

/**
 * 鼠标移入后显示弹出框
 */
$('#jsonTmplName').mouseenter(function () {
    $("[data-toggle='popover']").popover('show');
});

/**
 * 鼠标离开后隐藏弹出框
 */
$('#jsonTmplName').mouseleave(function () {
    $("[data-toggle='popover']").popover('hide');
});
