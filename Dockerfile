# syntax=docker/dockerfile:1
#
# blog-v2 production image（k3s 用）。兩個 target，同一份 build context（repo 根目錄）：
#   php   ：php-fpm + 程式碼 + vendor（--no-dev），USER www-data（82），opcache 開、不檢查時間戳
#   nginx ：只帶 public/ 靜態檔與 nginx 設定，以 uid 101 執行、listen 8080（非 root、可唯讀 root fs）
# 建置（主機 aarch64 原生 linux/arm64；Jenkins 經 usongrat deploy-app blog build 執行）：
#   git archive --format=tar <sha> | docker build --platform linux/arm64 --target php   -t blog-v2-php:<sha7>   -
#   git archive --format=tar <sha> | docker build --platform linux/arm64 --target nginx -t blog-v2-nginx:<sha7> -
# 本機開發仍用 deployment/local-blog（bind mount），與本檔無關。
# base image 一律釘精確版本（R11：交給 Renovate 追版）；版本與 deployment/local-blog/php/Dockerfile 對齊。

# ---------- vendor：composer install（無 dev 相依、無 scripts）----------
FROM composer:2.10.3 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
# composer image 的 PHP 沒有 intl 等擴充，平台需求在 php stage 由實際擴充滿足
RUN composer install \
      --no-dev \
      --no-scripts \
      --no-autoloader \
      --ignore-platform-reqs \
      --prefer-dist \
      --no-interaction \
      --no-progress
COPY . .
# 執行期不需要的檔案（stdin tar 當 context 時 .dockerignore 不一定生效，這裡明確刪除）
RUN rm -rf tests deploy deployment docs tools .env .env.* Dockerfile Jenkinsfile phpunit.xml .dockerignore .editorconfig .gitattributes .gitignore *.md \
    && find . -name '._*' -delete \
    && composer dump-autoload --no-dev --optimize --classmap-authoritative --no-scripts

# ---------- php：php-fpm runtime ----------
FROM php:8.2.35-fpm-alpine3.24 AS php

# 擴充與 deployment/local-blog/php/Dockerfile 相同（mbstring、pdo、opcache 已內建於 base image，不重複編譯）。
# 執行期只留共享函式庫；編譯工具裝在 .build-deps，裝完即移除
RUN apk add --no-cache icu-libs libzip tzdata \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers icu-dev libzip-dev zlib-dev \
    && docker-php-ext-install -j"$(nproc)" bcmath intl pdo_mysql zip \
    && pecl install redis --configureoptions 'enable-redis-igbinary="no" enable-redis-lzf="no" enable-redis-zstd="no" enable-redis-msgpack="no" enable-redis-lz4="no"' \
    && docker-php-ext-enable redis \
    && apk del .build-deps \
    && rm -rf /tmp/pear /usr/src/php.tar.* \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY deployment/k3s/php/zz-production.ini "$PHP_INI_DIR/conf.d/zz-production.ini"

WORKDIR /var/www/html
COPY --from=vendor --chown=root:root /app ./

# package:discover 產生 bootstrap/cache/packages.php / services.php（執行期 bootstrap/cache 為唯讀，所以在 build 時做）。
# 執行期可寫的只有 storage/framework/{cache,views,sessions}、storage/logs（chart 以 emptyDir 掛載），
# 這裡先建好並交給 www-data，讓不掛 emptyDir 的 docker run 也能動。
RUN rm -f .env \
    && php artisan package:discover --ansi \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/public \
    && chown -R www-data:www-data storage \
    && chown root:root /var/www/html && chmod 755 /var/www/html && chmod -R a+rX,go-w /var/www/html \
    && for m in bcmath intl mbstring pdo_mysql redis zip 'Zend OPcache'; do php -m | grep -qx "$m" || { echo "missing php extension: $m"; exit 1; }; done

USER www-data
EXPOSE 9000
CMD ["php-fpm"]

# ---------- nginx：靜態檔 + 反向代理到同 Pod 的 php-fpm ----------
FROM nginx:1.30.6-alpine3.24 AS nginx
# 官方 entrypoint 的 envsubst / IPv6 腳本會改寫 /etc/nginx/conf.d，唯讀 root fs 下不需要；直接用自己的設定
RUN rm -f /docker-entrypoint.d/*.sh /etc/nginx/conf.d/default.conf
COPY deployment/k3s/nginx/nginx.conf /etc/nginx/nginx.conf
COPY deployment/k3s/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=vendor /app/public /var/www/html/public
RUN chmod -R a+rX /var/www/html/public
USER 101
EXPOSE 8080
