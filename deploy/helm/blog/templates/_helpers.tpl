{{- define "blog.name" -}}
{{- default .Chart.Name .Values.nameOverride | trunc 63 | trimSuffix "-" -}}
{{- end -}}

{{- define "blog.fullname" -}}
{{- if .Values.fullnameOverride -}}
{{- .Values.fullnameOverride | trunc 63 | trimSuffix "-" -}}
{{- else -}}
{{- $name := default .Chart.Name .Values.nameOverride -}}
{{- if contains $name .Release.Name -}}
{{- .Release.Name | trunc 63 | trimSuffix "-" -}}
{{- else -}}
{{- printf "%s-%s" .Release.Name $name | trunc 63 | trimSuffix "-" -}}
{{- end -}}
{{- end -}}
{{- end -}}

{{- define "blog.labels" -}}
helm.sh/chart: {{ printf "%s-%s" .Chart.Name .Chart.Version | replace "+" "_" | trunc 63 | trimSuffix "-" }}
{{ include "blog.selectorLabels" . }}
app.kubernetes.io/version: {{ .Values.image.tag | quote }}
app.kubernetes.io/managed-by: {{ .Release.Service }}
{{- end -}}

{{- define "blog.selectorLabels" -}}
app.kubernetes.io/name: {{ include "blog.name" . }}
app.kubernetes.io/instance: {{ .Release.Name | quote }}
{{- end -}}

{{/* 只選到 web Pod（php-fpm + nginx；Service、PDB、anti-affinity、deploy-k3s 的檢查使用） */}}
{{- define "blog.webSelectorLabels" -}}
{{ include "blog.selectorLabels" . }}
app.kubernetes.io/component: web
{{- end -}}

{{- define "blog.redisSelectorLabels" -}}
{{ include "blog.selectorLabels" . }}
app.kubernetes.io/component: redis
{{- end -}}

{{- define "blog.redisName" -}}
{{- printf "%s-redis" (include "blog.fullname" .) | trunc 63 | trimSuffix "-" -}}
{{- end -}}

{{- define "blog.serviceAccountName" -}}
{{- default (include "blog.fullname" .) .Values.serviceAccount.name -}}
{{- end -}}

{{/* image tag 必填，不提供 latest 預設；$component 為 php 或 nginx */}}
{{- define "blog.image" -}}
{{- $tag := required "image.tag is required (full commit SHA); refusing to default to latest" .root.Values.image.tag -}}
{{- if eq (toString $tag) "latest" -}}
{{- fail "image.tag must not be 'latest'" -}}
{{- end -}}
{{- $repo := index .root.Values.image .component "repository" -}}
{{- printf "%s:%s" $repo $tag -}}
{{- end -}}

{{/* 既有 Secret 名稱必填，chart 不產生 Secret */}}
{{- define "blog.secretName" -}}
{{- required "secret.existingSecret is required (name of a pre-created Secret holding APP_KEY, DB_PASSWORD, REDIS_PASSWORD)" .Values.secret.existingSecret -}}
{{- end -}}

{{/* Deployment 與 migrate Job 共用的環境來源（ConfigMap 在前、Secret 在後：同名 key 以 Secret 為準） */}}
{{- define "blog.envFrom" -}}
- configMapRef:
    name: {{ .configMapName }}
- secretRef:
    name: {{ include "blog.secretName" .root }}
{{- end -}}

{{- define "blog.dbHost" -}}
{{- required "db.host is required" .Values.db.host -}}
{{- end -}}

{{- define "blog.redisHost" -}}
{{- if .Values.redis.enabled -}}
{{- include "blog.redisName" . -}}
{{- else -}}
{{- required "redis.externalHost is required when redis.enabled=false" .Values.redis.externalHost -}}
{{- end -}}
{{- end -}}

{{/* 一般 ConfigMap 與 migrate hook ConfigMap 共用的內容 */}}
{{- define "blog.configData" -}}
{{- $reserved := list "DB_HOST" "REDIS_HOST" "MYSQL_ATTR_SSL_CA" "MYSQL_ATTR_SSL_VERIFY_SERVER_CERT" -}}
DB_HOST: {{ include "blog.dbHost" . | quote }}
REDIS_HOST: {{ include "blog.redisHost" . | quote }}
{{- range $k, $v := .Values.env }}
{{- if not (has $k $reserved) }}
{{ $k }}: {{ $v | toString | quote }}
{{- end }}
{{- end }}
{{- if .Values.dbTLS.enabled }}
MYSQL_ATTR_SSL_CA: {{ printf "%s/%s" .Values.dbTLS.mountPath .Values.dbTLS.caKey | quote }}
{{- if not .Values.dbTLS.verifyServerCert }}
MYSQL_ATTR_SSL_VERIFY_SERVER_CERT: "false"
{{- end }}
{{- end }}
{{- end -}}

{{/* DB CA 的 ConfigMap 名稱；suffix 為空（web）或 "-migrate"（hook 專用） */}}
{{- define "blog.dbCAConfigMapName" -}}
{{- if .root.Values.dbTLS.existingConfigMap -}}
{{- .root.Values.dbTLS.existingConfigMap -}}
{{- else -}}
{{- printf "%s%s-db-ca" (include "blog.fullname" .root) .suffix -}}
{{- end -}}
{{- end -}}

{{/* CA PEM 內容。MySQL 自動產生的 ca.pem 結尾帶一個 NUL byte，YAML 不接受，先去掉 */}}
{{- define "blog.dbCACert" -}}
{{- .Values.dbTLS.caCert | replace "\x00" "" | trim -}}
{{- end -}}

{{/* dbTLS 開啟時檢查設定；caCert 與 existingConfigMap 必須擇一 */}}
{{- define "blog.dbTLSValidate" -}}
{{- if .Values.dbTLS.enabled -}}
{{- if and .Values.dbTLS.caCert .Values.dbTLS.existingConfigMap -}}
{{- fail "dbTLS: set either caCert or existingConfigMap, not both" -}}
{{- end -}}
{{- if not (or .Values.dbTLS.caCert .Values.dbTLS.existingConfigMap) -}}
{{- fail "dbTLS.enabled requires dbTLS.caCert or dbTLS.existingConfigMap" -}}
{{- end -}}
{{- end -}}
{{- end -}}

{{/* php 可寫目錄（唯讀 root fs 下以 emptyDir 提供）：container 層 mounts */}}
{{- define "blog.phpWritableMounts" -}}
- name: php-tmp
  mountPath: /tmp
- name: storage-views
  mountPath: /var/www/html/storage/framework/views
- name: storage-cache
  mountPath: /var/www/html/storage/framework/cache
- name: storage-sessions
  mountPath: /var/www/html/storage/framework/sessions
- name: storage-logs
  mountPath: /var/www/html/storage/logs
{{- end -}}

{{/* 同上：pod 層 volumes */}}
{{- define "blog.phpWritableVolumes" -}}
- name: php-tmp
  emptyDir:
    sizeLimit: 64Mi
- name: storage-views
  emptyDir:
    sizeLimit: 64Mi
- name: storage-cache
  emptyDir:
    sizeLimit: 64Mi
- name: storage-sessions
  emptyDir:
    sizeLimit: 16Mi
- name: storage-logs
  emptyDir:
    sizeLimit: 64Mi
{{- end -}}

{{/* DB CA volume mount（container 層）；只在 dbTLS.enabled 時 include */}}
{{- define "blog.dbTLSVolumeMount" -}}
- name: db-ca
  mountPath: {{ .Values.dbTLS.mountPath }}
  readOnly: true
{{- end -}}

{{/* DB CA volume（pod 層）；只在 dbTLS.enabled 時 include，suffix 同 blog.dbCAConfigMapName */}}
{{- define "blog.dbTLSVolume" -}}
- name: db-ca
  configMap:
    name: {{ include "blog.dbCAConfigMapName" . }}
    items:
      - key: {{ .root.Values.dbTLS.caKey }}
        path: {{ .root.Values.dbTLS.caKey }}
{{- end -}}

{{/* php container 的 securityContext（共用 containerSecurityContext + 執行身分） */}}
{{- define "blog.containerSecurityContext" -}}
{{- $sc := deepCopy .root.Values.containerSecurityContext -}}
{{- $_ := set $sc "runAsUser" (int .user) -}}
{{- $_ := set $sc "runAsGroup" (int .group) -}}
{{- toYaml $sc -}}
{{- end -}}
