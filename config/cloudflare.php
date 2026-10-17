<?php

/**
 * Cloudflare 邊緣 IP 範圍（來源：https://www.cloudflare.com/ips-v4 、 /ips-v6，2026-10-17 取得）。
 * 只用來判斷「這個請求確實經過 Cloudflare」，進而採信 CF-Connecting-IP，見 ResolveCloudflareClientIp。
 * Cloudflare 偶爾會調整範圍，請定期對照官方清單更新。
 */
return [
    'ips' => [
        '173.245.48.0/20',
        '103.21.244.0/22',
        '103.22.200.0/22',
        '103.31.4.0/22',
        '141.101.64.0/18',
        '108.162.192.0/18',
        '190.93.240.0/20',
        '188.114.96.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '162.158.0.0/15',
        '104.16.0.0/13',
        '104.24.0.0/14',
        '172.64.0.0/13',
        '131.0.72.0/22',
        '2400:cb00::/32',
        '2606:4700::/32',
        '2803:f800::/32',
        '2405:b500::/32',
        '2405:8100::/32',
        '2a06:98c0::/29',
        '2c0f:f248::/32',
    ],

    /*
    | 文章更新後自動清除 Cloudflare 邊緣快取（POST /zones/{zone_id}/purge_cache，body {"files":[...]}）。
    | 預設關閉；token 只給 Zone → Cache Purge 權限、只限此 zone。
    | batch_size：官方文件 Free/Pro/Business 單次最多 100 個 URL（Enterprise 500），超過會分批送。
    | 文件：https://developers.cloudflare.com/cache/how-to/purge-cache/
    */
    'purge' => [
        'enabled' => (bool) env('CLOUDFLARE_PURGE_ENABLED', false),
        'zone_id' => env('CLOUDFLARE_ZONE_ID'),
        'api_token' => env('CLOUDFLARE_API_TOKEN'),
        'endpoint' => 'https://api.cloudflare.com/client/v4',
        'batch_size' => 100,
        'timeout' => 5,
        'retries' => 2,
    ],
];
