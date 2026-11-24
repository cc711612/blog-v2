// blog CI/CD（usongrat Jenkins → GHCR → k3s），比照 notify 的流程
// 流程：agent 上測試 → usongrat 上 build image → 推送 GHCR → Slack 通知待核准 → 人工核准 → 透過受限 SSH 呼叫 k3s 主機上的 deploy-k3s
//
// Test 仍在 jenkins-agent-php 跑（composer install + php artisan test，與舊流程相同）。
// build / push 在 agent 做不到（沒有 docker、不掛 docker.sock），經 blog-deploy-ssh（forced command 綁 usongrat 的
// deploy-app blog，需支援 build|push <40 碼 sha>）交給 usongrat：
//   build <sha>：git archive <sha> | docker build --platform linux/arm64 --target php / nginx（主機 aarch64 原生）
//   push <sha> ：推送 ghcr.io/cc711612/blog-v2-php:<完整 sha> 與 ghcr.io/cc711612/blog-v2-nginx:<完整 sha>；GHCR 已有就略過（冪等）
// 部署經 blog-k3s-deploy-ssh（forced command 綁 k3s 主機的 deploy-k3s，key 限定 app=blog，只接受 blog deploy <sha> 與 blog status）：
//   blog deploy <sha>：取該 sha 的 deploy/helm/blog，helm upgrade --wait --rollback-on-failure（migrate 為 pre-upgrade hook），
//                      失敗由 Helm 自動回滾；成功後檢查 Pod 都是新 image 且 Ready（readiness = nginx → Laravel /up）

// 送 Slack 通知；commit message 會跳脫 & < >，避免注入 Slack 格式（例如 <!channel>）
def notifySlack(String kind) {
    // 核准階段已針對拒絕 / 逾時 / 中止發過專屬通知，build 結束時不再重複發 ABORTED 那一則
    if (kind == 'result' && env.APPROVAL_ABORT_NOTIFIED == 'true') {
        return
    }
    node('php') {
        withCredentials([string(credentialsId: 'slack-webhook', variable: 'SLACK_URL')]) {
            withEnv(["KIND=${kind}", "RESULT=${currentBuild.currentResult}", "APPROVER=${env.APPROVER ?: ''}"]) {
                sh '''
                    php -r '
                        $esc = fn ($s) => str_replace(["&", "<", ">"], ["&amp;", "&lt;", "&gt;"], (string) $s);
                        $url = (string) getenv("BUILD_URL");
                        $title = getenv("JOB_NAME")." #".getenv("BUILD_NUMBER")." ".substr((string) getenv("DEPLOY_SHA"), 0, 7)." ".$esc(getenv("COMMIT_MSG"));
                        if (getenv("KIND") === "approval") {
                            $text = ":raised_hand: *等待部署核准* → ".getenv("PROD_HOST")."\n".$title."\n<".$url."input/|點此核准或拒絕>（30 分鐘內）";
                        } elseif (getenv("KIND") === "approved") {
                            $by = (string) getenv("APPROVER");
                            $text = ":white_check_mark: *已核准，開始部署* → ".getenv("PROD_HOST")."\n".$title.($by !== "" ? "\n核准人：".$esc($by) : "");
                        } elseif (getenv("KIND") === "rejected") {
                            $text = ":no_entry_sign: *已拒絕部署* → ".getenv("PROD_HOST")."\n".$title." (<".$url."|build log>)";
                        } elseif (getenv("KIND") === "timeout") {
                            $text = ":hourglass: *核准逾時，未部署* → ".getenv("PROD_HOST")."\n".$title." (<".$url."|build log>)";
                        } elseif (getenv("KIND") === "aborted") {
                            $text = ":double_vertical_bar: *核准階段已中止（拒絕或手動取消），未部署* → ".getenv("PROD_HOST")."\n".$title." (<".$url."|build log>)";
                        } else {
                            $map = ["SUCCESS" => [":white_check_mark:", "部署成功"], "ABORTED" => [":double_vertical_bar:", "未部署（取消或核准逾時）"]];
                            [$icon, $word] = $map[getenv("RESULT")] ?? [":x:", "失敗"];
                            $text = $icon." ".$word." — ".$title." (<".$url."|build log>)";
                        }
                        echo json_encode(["text" => $text], JSON_UNESCAPED_UNICODE);
                    ' | curl -sf -X POST -H 'Content-Type: application/json' --data @- "$SLACK_URL" >/dev/null || true
                '''
            }
        }
    }
}

// usongrat（docker bridge gateway）：build / push
def hostCmd = 'ssh -o StrictHostKeyChecking=yes -o BatchMode=yes -o LogLevel=ERROR deploy@172.17.0.1'
// k3s 主機（私網）：deploy；host key 釘在 /etc/ssh/ssh_known_hosts（usongrat 的 /opt/jenkins/secrets/known_hosts）
def k3sCmd = 'ssh -o StrictHostKeyChecking=yes -o BatchMode=yes -o LogLevel=ERROR deploy@10.0.0.164'

// 核准階段被中止時，判斷原因並發 Slack：拒絕 / 逾時 / 其他中止。
// 先看 FlowInterruptedException 的 cause 類型（Rejection=按拒絕、ExceededTimeout=逾時）；
// sandbox 不允許讀 cause 時，退回用等待時間判斷是否逾時；仍判斷不出就發通用「已中止」。判斷失敗不得影響 build 結果。
def notifyApprovalAborted(e) {
    def kind = 'aborted'
    try {
        def causes = e.getCauses()
        def name = causes ? causes[0].getClass().getName() : ''
        if (name.endsWith('Rejection')) {
            kind = 'rejected'
        } else if (name.contains('ExceededTimeout')) {
            kind = 'timeout'
        }
    } catch (Exception ignored) {
        try {
            long started = (env.APPROVE_STARTED_MS ?: '0') as long
            if (started > 0 && (System.currentTimeMillis() - started) >= 29 * 60 * 1000L) {
                kind = 'timeout'
            }
        } catch (Exception ignored2) {
            kind = 'aborted'
        }
    }
    env.APPROVAL_ABORT_NOTIFIED = 'true'
    notifySlack(kind)
}

pipeline {
    agent none

    environment {
        APP         = 'blog'
        PROD_HOST   = 'roy.usongrat.tw'
        DEPLOY_CRED = 'blog-deploy-ssh'
        K3S_CRED    = 'blog-k3s-deploy-ssh'
    }

    options {
        timestamps()
        disableConcurrentBuilds()
        buildDiscarder(logRotator(numToKeepStr: '20'))
    }

    stages {
        stage('Test') {
            agent { label 'php' }
            options { timeout(time: 15, unit: 'MINUTES') }
            environment {
                COMPOSER_CACHE_DIR = '/home/jenkins/agent/.composer-cache'
            }
            steps {
                script {
                    env.DEPLOY_SHA = sh(returnStdout: true, script: 'git rev-parse HEAD').trim()
                    env.COMMIT_MSG = sh(returnStdout: true, script: 'git log -1 --format=%s').trim()
                }
                sh 'composer install --no-interaction --prefer-dist --no-progress'
                sh '''
                    cp .env.example .env
                    php artisan key:generate --force
                    php artisan test
                '''
            }
            post {
                always { cleanWs() }
            }
        }

        stage('Build image') {
            agent { label 'php' }
            options {
                skipDefaultCheckout()
                timeout(time: 20, unit: 'MINUTES')
            }
            steps {
                // 主機端 deploy-app 自行取得該 sha 的原始碼，agent 不需要 workspace
                sshagent(credentials: [env.DEPLOY_CRED]) {
                    sh "${hostCmd} build \"\$DEPLOY_SHA\""
                }
            }
        }

        stage('Push image') {
            agent { label 'php' }
            options {
                skipDefaultCheckout()
                timeout(time: 10, unit: 'MINUTES')
            }
            steps {
                // GHCR 憑證只在 usongrat root 的 docker login，不經過 Jenkins
                sshagent(credentials: [env.DEPLOY_CRED]) {
                    sh "${hostCmd} push \"\$DEPLOY_SHA\""
                }
            }
        }

        stage('Approve') {
            // 先發 Slack 附核准連結；等待期間不佔用 agent，30 分鐘沒按就中止
            options { timeout(time: 30, unit: 'MINUTES') }
            steps {
                notifySlack('approval')
                script {
                    try {
                        env.APPROVE_STARTED_MS = System.currentTimeMillis().toString()
                    } catch (Exception ignored) {
                        env.APPROVE_STARTED_MS = '0'
                    }
                    def answer
                    try {
                        // submitterParameter：回傳按下核准的使用者 ID，供 Slack 通知顯示
                        answer = input(message: "部署 ${env.DEPLOY_SHA.take(7)}「${env.COMMIT_MSG}」到 ${env.PROD_HOST}？", ok: '部署', submitter: 'cc711612@gmail.com', submitterParameter: 'APPROVER')
                    } catch (org.jenkinsci.plugins.workflow.steps.FlowInterruptedException e) {
                        notifyApprovalAborted(e)
                        throw e
                    }
                    env.APPROVER = (answer instanceof Map) ? ((answer['APPROVER'] ?: '') as String) : ((answer ?: '') as String)
                    notifySlack('approved')
                }
            }
        }

        stage('Deploy') {
            agent { label 'php' }
            options {
                skipDefaultCheckout()
                // deploy-k3s 內 helm --timeout 5m（含 migrate hook），加上 fetch 與部署後檢查
                timeout(time: 10, unit: 'MINUTES')
            }
            steps {
                sshagent(credentials: [env.K3S_CRED]) {
                    // 這把 key 在 k3s 主機被 forced command 綁定，只能執行 deploy-k3s blog（blog deploy <sha> / blog status）
                    sh "${k3sCmd} blog deploy \"\$DEPLOY_SHA\""
                }
            }
        }
    }

    post {
        always {
            notifySlack('result')
        }
    }
}
