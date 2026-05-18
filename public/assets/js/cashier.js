/**
 * MX-Mall - Cashier Page JS
 * Payment submit, result polling
 */

const CashierApp = {
    tradeNo: '',
    tpl: '',
    orderData: null,
    wxOpenid: '',

    setData(tradeNo, tpl, orderData, wxOpenid) {
        this.tradeNo = tradeNo;
        this.tpl = tpl;
        this.orderData = orderData;
        this.wxOpenid = wxOpenid || '';
    },

    renderOrderInfo() {
        if (!this.orderData) return;
        const order = this.orderData;
        const money = order.money || order.amount || 0;
        const tradeNo = order.out_trade_no || order.trade_no || this.tradeNo;
        const goodsName = order.product_name || order.subject || '商品订单';

        document.querySelectorAll('[id="pay-amount"]').forEach(el => {
            el.textContent = '¥' + parseFloat(money).toFixed(2);
        });
        document.querySelectorAll('[id="order-no"]').forEach(el => {
            el.textContent = tradeNo;
        });
        document.querySelectorAll('[id="goods-name"]').forEach(el => {
            el.textContent = goodsName;
        });
    },

    async submitPayment() {
        var btn = document.querySelector('a.btn-main:not(.btn-disabled), a.btn-pay:not(.btn-disabled), a.btn-pdd:not(.btn-disabled), a.btn-tb:not(.btn-disabled), a.btn-didi:not(.btn-disabled), a.btn-ctrip:not(.btn-disabled), a.btn-blue:not(.btn-disabled), a.btn-pay-cyan:not(.btn-disabled), a.btn-pay-now:not(.btn-disabled), a.btn-pay-friend:not(.btn-disabled), .cp-btn-pay, #btn-pay-submit');
        if (!btn || btn.disabled) return;

        var openid = this.wxOpenid || (window.__wxOpenid || '');

        if (window.__isWechat && !openid) {
            this.redirectToWxOauth();
            return;
        }

        try {
            btn.disabled = true;
            btn.textContent = '支付中...';

            const res = await fetch('/api/pay/submit', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    trade_no: this.tradeNo,
                    tpl: this.tpl,
                    openid: openid,
                })
            }).then(r => r.json());

            if (res.code === 0 && res.data) {
                if (res.data.jsapi_params) {
                    this.callWxJsapiPay(res.data.jsapi_params, btn);
                } else if (res.data.pay_url) {
                    window.location.href = res.data.pay_url;
                } else {
                    alert('支付方式异常');
                    btn.disabled = false;
                    btn.textContent = '确认支付';
                }
            } else {
                alert(res.msg || '支付发起失败');
                btn.disabled = false;
                btn.textContent = '确认支付';
            }
        } catch (e) {
            alert('网络错误，请重试');
            btn.disabled = false;
            btn.textContent = '确认支付';
        }
    },

    redirectToWxOauth() {
        var appid = window.__wxAppid || '';
        if (!appid) {
            alert('微信支付配置异常，请联系管理员');
            return;
        }

        var url = new URL(window.location.href);
        url.searchParams.delete('code');
        url.searchParams.delete('state');
        url.searchParams.set('auto_pay', '1');
        var redirectUri = encodeURIComponent(url.toString());

        var oauthBase = 'https://open.weixin.qq.com';
        if (window.__wxOauthMode === '1' && window.__wxOauthUrl) {
            oauthBase = window.__wxOauthUrl.replace(/\/$/, '');
        }

        var authUrl = oauthBase + '/connect/oauth2/authorize?appid=' + appid + '&redirect_uri=' + redirectUri + '&response_type=code&scope=snsapi_base&state=wxpay#wechat_redirect';

        window.location.replace(authUrl);
    },

    callWxJsapiPay(params, btn) {
        if (typeof WeixinJSBridge === 'undefined') {
            alert('请在微信中打开此页面完成支付');
            if (btn) { btn.disabled = false; btn.textContent = '确认支付'; }
            return;
        }

        WeixinJSBridge.invoke(
            'getBrandWCPayRequest',
            {
                appId: params.appId,
                timeStamp: params.timeStamp,
                nonceStr: params.nonceStr,
                package: params.package,
                signType: params.signType,
                paySign: params.paySign,
            },
            function(res) {
                if (res.err_msg === 'get_brand_wcpay_request:ok') {
                    if (typeof NexusApp !== 'undefined') {
                        NexusApp.toast('支付成功', 'success');
                    }
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                } else if (res.err_msg === 'get_brand_wcpay_request:cancel') {
                    if (btn) { btn.disabled = false; btn.textContent = '确认支付'; }
                } else {
                    alert('支付失败，请重试');
                    if (btn) { btn.disabled = false; btn.textContent = '确认支付'; }
                }
            }
        );
    },

    destroy() {}
};

window.addEventListener('beforeunload', () => {
    CashierApp.destroy();
});
