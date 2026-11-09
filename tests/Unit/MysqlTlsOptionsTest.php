<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

/**
 * config/database.php 的 mysql options：MYSQL_ATTR_SSL_CA / MYSQL_ATTR_SSL_VERIFY_SERVER_CERT 只在有設定時帶入，
 * 沒設定時維持原本的空陣列（明文連線，usongrat 現況）。k3s chart 的 dbTLS 依賴這個行為。
 */
#[RequiresPhpExtension('pdo_mysql')]
class MysqlTlsOptionsTest extends TestCase
{
    private const KEYS = ['MYSQL_ATTR_SSL_CA', 'MYSQL_ATTR_SSL_VERIFY_SERVER_CERT'];

    protected function tearDown(): void
    {
        foreach (self::KEYS as $key) {
            $this->setEnv($key, null);
        }
        parent::tearDown();
    }

    private function setEnv(string $key, ?string $value): void
    {
        if ($value === null) {
            unset($_ENV[$key], $_SERVER[$key]);
            putenv($key);

            return;
        }
        $_ENV[$key] = $_SERVER[$key] = $value;
        putenv("$key=$value");
    }

    private function mysqlOptions(): array
    {
        return (require base_path('config/database.php'))['connections']['mysql']['options'];
    }

    public function test_no_tls_env_keeps_options_empty(): void
    {
        $this->assertSame([], $this->mysqlOptions());
    }

    public function test_ca_only_enables_tls_without_touching_verification(): void
    {
        $this->setEnv('MYSQL_ATTR_SSL_CA', '/etc/blog/db-ca/ca.pem');

        $this->assertSame([\PDO::MYSQL_ATTR_SSL_CA => '/etc/blog/db-ca/ca.pem'], $this->mysqlOptions());
    }

    public function test_verify_server_cert_false_is_passed_through(): void
    {
        $this->setEnv('MYSQL_ATTR_SSL_CA', '/etc/blog/db-ca/ca.pem');
        $this->setEnv('MYSQL_ATTR_SSL_VERIFY_SERVER_CERT', 'false');

        $this->assertSame([
            \PDO::MYSQL_ATTR_SSL_CA => '/etc/blog/db-ca/ca.pem',
            \PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        ], $this->mysqlOptions());
    }

    public function test_empty_values_are_ignored(): void
    {
        $this->setEnv('MYSQL_ATTR_SSL_CA', '');
        $this->setEnv('MYSQL_ATTR_SSL_VERIFY_SERVER_CERT', '');

        $this->assertSame([], $this->mysqlOptions());
    }
}
