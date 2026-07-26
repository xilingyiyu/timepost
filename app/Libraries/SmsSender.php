<?php
// 阿里云短信发送器（纯 cURL，无 SDK 依赖）
// 文档: https://help.aliyun.com/document_detail/101414.html

namespace App\Libraries;

class SmsSender
{
    private string $accessKey;
    private string $secretKey;
    private string $sign;
    private string $templateCode;
    private bool $enabled;

    public function __construct()
    {
        $this->accessKey    = env('SMS_ACCESS_KEY', '');
        $this->secretKey    = env('SMS_SECRET_KEY', '');
        $this->sign         = env('SMS_SIGN', '时光邮局');
        $this->templateCode = env('SMS_TEMPLATE_CODE', '');
        // 未配置则进入"模拟模式"，仅写日志不真发
        $this->enabled = $this->accessKey !== '' && $this->secretKey !== '' && $this->templateCode !== '';
    }

    /**
     * 发送短信
     * @return array ['ok'=>bool, 'msg_id'=>string, 'error'=>string]
     */
    public function send(string $phone, string $content, string $viewUrl = ''): array
    {
        if (!$this->enabled) {
            // 模拟模式：直接返回成功
            return ['ok' => true, 'msg_id' => 'MOCK_' . time(), 'error' => '', 'mock' => true];
        }
        if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
            return ['ok' => false, 'msg_id' => '', 'error' => '手机号格式错误'];
        }

        // 短信内容参数（按你的模板变量调整）
        $params = [
            'content' => mb_substr($content, 0, 30),
            'url'     => $viewUrl ?: config('app.url'),
        ];

        try {
            $result = $this->callApi([
                'PhoneNumbers'  => $phone,
                'SignName'       => $this->sign,
                'TemplateCode'   => $this->templateCode,
                'TemplateParam'  => json_encode($params, JSON_UNESCAPED_UNICODE),
            ]);
            if (($result['Code'] ?? '') === 'OK') {
                return ['ok' => true, 'msg_id' => $result['BizId'] ?? '', 'error' => ''];
            }
            return ['ok' => false, 'msg_id' => '', 'error' => $result['Message'] ?? '未知错误'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'msg_id' => '', 'error' => $e->getMessage()];
        }
    }

    public function isEnabled(): bool { return $this->enabled; }

    private function callApi(array $params): array
    {
        $apiParams = array_merge([
            'RegionId'      => 'cn-hangzhou',
            'Format'        => 'JSON',
            'Version'       => '2017-05-25',
            'AccessKeyId'   => $this->accessKey,
            'SignatureMethod' => 'HMAC-SHA1',
            'Timestamp'     => gmdate('Y-m-d\TH:i:s\Z'),
            'SignatureVersion' => '1.0',
            'SignatureNonce'=> bin2hex(random_bytes(8)),
            'Action'        => 'SendSms',
        ], $params);

        // 签名
        ksort($apiParams);
        $canonical = '';
        foreach ($apiParams as $k => $v) {
            $canonical .= '&' . $this->encode($k) . '=' . $this->encode($v);
        }
        $stringToSign = 'GET&' . $this->encode('/') . '&' . $this->encode(substr($canonical, 1));
        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $this->secretKey . '&', true));
        $apiParams['Signature'] = $signature;

        $url = 'https://dysmsapi.aliyuncs.com/?' . http_build_query($apiParams);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $body = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($body === false) throw new \RuntimeException('SMS API 请求失败: ' . $err);
        return json_decode($body, true) ?: [];
    }

    private function encode(string $s): string
    {
        return rawurlencode($s);
    }
}
