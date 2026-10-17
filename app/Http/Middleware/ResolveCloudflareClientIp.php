<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * 請求鏈是 Cloudflare → 主機 nginx → 容器 nginx → app。Cloudflare 邊緣 IP 不在 TrustProxies 清單，
 * 所以 request()->ip() 會是 Cloudflare 邊緣 IP，依 IP 的限流會變成「每個 CF 節點共用一個額度」。
 *
 * 這裡只在「確實經過 Cloudflare」時才採信 CF-Connecting-IP，否則任何人直連 origin 都能偽造 IP：
 *  1. 直接 peer（REMOTE_ADDR）必須是內網 proxy；
 *  2. X-Forwarded-For 去掉尾端的內網 proxy 後，最後一跳必須落在 Cloudflare IP 範圍
 *     （最後一跳是離 app 最近的受信 proxy 附加的，client 無法偽造）；
 *  3. CF-Connecting-IP 必須是合法 IP。
 * 符合時把 X-Forwarded-For 改寫成該 IP，讓 request()->ip() 取得真實訪客 IP。
 */
class ResolveCloudflareClientIp
{
    /** 與 bootstrap/app.php 的 trustProxies 共用：本機與 docker 內網 */
    public const INTERNAL_PROXIES = ['127.0.0.1', '::1', '172.16.0.0/12'];

    public function handle(Request $request, Closure $next): Response
    {
        $visitorIp = $this->verifiedCloudflareClientIp($request);

        if ($visitorIp !== null) {
            $request->headers->set('X-Forwarded-For', $visitorIp);
        }

        return $next($request);
    }

    private function verifiedCloudflareClientIp(Request $request): ?string
    {
        $peer = (string) $request->server->get('REMOTE_ADDR', '');
        if ($peer === '' || ! IpUtils::checkIp($peer, self::INTERNAL_PROXIES)) {
            return null;
        }

        $cfIp = trim((string) $request->headers->get('CF-Connecting-IP', ''));
        if ($cfIp === '' || filter_var($cfIp, FILTER_VALIDATE_IP) === false) {
            return null;
        }

        $hops = array_values(array_filter(array_map('trim', explode(',', (string) $request->headers->get('X-Forwarded-For', '')))));
        while ($hops !== [] && IpUtils::checkIp(end($hops), self::INTERNAL_PROXIES)) {
            array_pop($hops);
        }

        $edge = end($hops);
        if ($edge === false || filter_var($edge, FILTER_VALIDATE_IP) === false) {
            return null;
        }

        return IpUtils::checkIp($edge, (array) config('cloudflare.ips', [])) ? $cfIp : null;
    }
}
