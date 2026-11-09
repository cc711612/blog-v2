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
    node('php') {
        withCredentials([string(credentialsId: 'slack-webhook', variable: 'SLACK_URL')]) {
            withEnv(["KIND=${kind}", "RESULT=${currentBuild.currentResult}"]) {
                sh '''
                    php -r '
                        $esc = fn ($s) => str_replace(["&", "<", ">"], ["&amp;", "&lt;", "&gt;"], (string) $s);
                        $url = (string) getenv("BUILD_URL");
                        $title = getenv("JOB_NAME")." #".getenv("BUILD_NUMBER")." ".substr((string) getenv("DEPLOY_SHA"), 0, 7)." ".$esc(getenv("COMMIT_MSG"));
                        if (getenv("KIND") === "approval") {
                            $text = ":raised_hand: *等待部署核准* → ".getenv("PROD_HOST")."\n".$title."\n<".$url."input/|點此核准或拒絕>（30 分鐘內）";
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
                input message: "部署 ${env.DEPLOY_SHA.take(7)}「${env.COMMIT_MSG}」到 ${env.PROD_HOST}？", ok: '部署', submitter: 'cc711612@gmail.com'
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
