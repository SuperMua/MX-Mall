<?php
/**
 * 彩虹易支付SDK封装类（MD5签名）
 * 基于官方SDK EpayCore.class.php 改写
 */
namespace NexusMall\Payments;

class Epay {
    private $pid;
    private $key;
    private $apiurl;
    private $submit_url;
    private $api_url;

    public function __construct($config) {
        $this->apiurl = rtrim($config['apiurl'] ?? '', '/');
        $this->pid = $config['pid'] ?? '';
        $this->key = $config['key'] ?? '';
        $this->submit_url = $this->apiurl . '/submit.php';
        $this->api_url = $this->apiurl . '/api.php';
    }

    /**
     * 发起支付（获取跳转URL）
     */
    public function getPayLink($params) {
        $param = [
            'pid'          => $this->pid,
            'type'         => $params['type'] ?? 'wxpay',
            'out_trade_no' => $params['out_trade_no'],
            'notify_url'   => $params['notify_url'] ?? '',
            'return_url'   => $params['return_url'] ?? '',
            'name'         => $params['name'] ?? '商品支付',
            'money'        => $params['money'],
        ];

        $param = $this->buildRequestParam($param);
        $url = $this->submit_url . '?' . http_build_query($param);
        return $url;
    }

    /**
     * 异步回调验证（读取 $_GET）
     */
    public function verifyNotify() {
        if (empty($_GET)) return false;

        $sign = $this->getSign($_GET);

        if ($sign === $_GET['sign']) {
            return true;
        }
        return false;
    }

    /**
     * 同步回调验证
     */
    public function verifyReturn() {
        if (empty($_GET)) return false;

        $sign = $this->getSign($_GET);

        if ($sign === $_GET['sign']) {
            return true;
        }
        return false;
    }

    /**
     * 查询订单
     */
    public function queryOrder($trade_no) {
        $url = $this->api_url . '?act=order&pid=' . $this->pid . '&key=' . $this->key . '&trade_no=' . $trade_no;
        $response = $this->getHttpResponse($url);
        $arr = json_decode($response, true);
        return $arr;
    }

    /**
     * 退款
     * @return array ['success' => bool, 'msg' => string]
     */
    public function refund($trade_no, $money = null) {
        $param = [
            'pid'     => $this->pid,
            'key'     => $this->key,
            'trade_no'=> $trade_no,
        ];
        if ($money !== null && $money > 0) {
            $param['money'] = $money;
        }
        $url = $this->api_url . '?act=refund';
        $response = $this->getHttpResponse($url, http_build_query($param));
        $arr = json_decode($response, true);

        if ($arr && isset($arr['code']) && $arr['code'] == 1) {
            return ['success' => true, 'msg' => $arr['msg'] ?? '退款成功'];
        }
        return ['success' => false, 'msg' => $arr['msg'] ?? ($arr['error'] ?? '退款请求失败')];
    }

    // ===== 私有方法 =====

    private function buildRequestParam($param) {
        $mysign = $this->getSign($param);
        $param['sign'] = $mysign;
        $param['sign_type'] = 'MD5';
        return $param;
    }

    /**
     * 计算签名（与官方SDK完全一致）
     * ksort → 拼接 k=v& → 去掉末尾& → 末尾拼接key → md5()
     */
    private function getSign($param) {
        ksort($param);
        reset($param);
        $signstr = '';

        foreach ($param as $k => $v) {
            if ($k != 'sign' && $k != 'sign_type' && $v != '') {
                $signstr .= $k . '=' . $v . '&';
            }
        }
        $signstr = substr($signstr, 0, -1);
        $signstr .= $this->key;
        $sign = md5($signstr);
        return $sign;
    }

    private function getHttpResponse($url, $post = false, $timeout = 10) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $httpheader[] = "Accept: */*";
        $httpheader[] = "Accept-Language: zh-CN,zh;q=0.8";
        $httpheader[] = "Connection: close";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($post) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
}
