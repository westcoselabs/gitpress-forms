param(
    [string]$WordPressSource = "$env:USERPROFILE\Local Sites\test\app\public",
    [int]$HttpPort = 9410,
    [int]$MysqlPort = 9406
)
$ErrorActionPreference = 'Stop'
$taskPlugin = Split-Path $PSScriptRoot -Parent
$taskRuntime = Join-Path $taskPlugin '.runtime'
$taskWordPress = Join-Path $taskRuntime 'wordpress'
$taskServices = Join-Path $env:APPDATA 'Local\lightning-services'
$taskPhpService = Get-ChildItem -LiteralPath $taskServices -Directory | Where-Object Name -Like 'php-8.*' | Sort-Object Name | Select-Object -Last 1
$taskMysqlService = Get-ChildItem -LiteralPath $taskServices -Directory | Where-Object Name -Like 'mysql-8.*' | Sort-Object Name | Select-Object -Last 1
$taskPhp = Join-Path $taskPhpService.FullName 'bin\win64'
$taskMysql = Join-Path $taskMysqlService.FullName 'bin\win64'
New-Item -ItemType Directory -Path $taskWordPress -Force | Out-Null
$taskMarker = Join-Path $taskWordPress '.gitpress-test-sandbox'
if (!(Test-Path -LiteralPath $taskMarker)) {
    if (Test-Path -LiteralPath (Join-Path $taskWordPress 'wp-config.php')) { throw 'Refusing to overwrite an existing WordPress configuration.' }
    Set-Content -LiteralPath $taskMarker -Value 'Isolated GitPress Forms test site. Contains no production data.'
    foreach ($taskDir in @('wp-admin','wp-includes')) { Copy-Item -LiteralPath (Join-Path $WordPressSource $taskDir) -Destination $taskWordPress -Recurse }
    Get-ChildItem -LiteralPath $WordPressSource -File | Where-Object { $_.Name -match '^(index|wp-[a-z-]+)\.php$' -and $_.Name -ne 'wp-config.php' } | ForEach-Object { Copy-Item -LiteralPath $_.FullName -Destination $taskWordPress }
    New-Item -ItemType Directory -Path (Join-Path $taskWordPress 'wp-content\themes') -Force | Out-Null
    Copy-Item -LiteralPath (Join-Path $WordPressSource 'wp-content\themes\twentytwentyfive') -Destination (Join-Path $taskWordPress 'wp-content\themes') -Recurse
}
New-Item -ItemType Directory -Path (Join-Path $taskWordPress 'wp-content\plugins') -Force | Out-Null
$taskLink = Join-Path $taskWordPress 'wp-content\plugins\gitpress-forms'
if (!(Test-Path -LiteralPath $taskLink)) { New-Item -ItemType Junction -Path $taskLink -Target $taskPlugin | Out-Null }
$taskData = Join-Path $taskRuntime 'mysql'
if (!(Test-Path -LiteralPath (Join-Path $taskData 'auto.cnf'))) {
    New-Item -ItemType Directory -Path $taskData -Force | Out-Null
    & (Join-Path $taskMysql 'bin\mysqld.exe') --no-defaults --initialize-insecure "--basedir=$taskMysql" "--datadir=$taskData" --console
    if ($LASTEXITCODE) { throw 'Could not initialize isolated MySQL.' }
}
if (!(Test-NetConnection -ComputerName 127.0.0.1 -Port $MysqlPort -InformationLevel Quiet -WarningAction SilentlyContinue)) {
    $taskMysqlArguments = @('--no-defaults', "--basedir=`"$taskMysql`"", "--datadir=`"$taskData`"", "--port=$MysqlPort", '--bind-address=127.0.0.1', '--mysqlx=0', '--console')
    $taskDbProcess = Start-Process -FilePath (Join-Path $taskMysql 'bin\mysqld.exe') -ArgumentList $taskMysqlArguments -WindowStyle Hidden -PassThru -RedirectStandardOutput (Join-Path $taskRuntime 'mysql-stdout.log') -RedirectStandardError (Join-Path $taskRuntime 'mysql-stderr.log')
    Set-Content -LiteralPath (Join-Path $taskRuntime 'mysql.pid') -Value $taskDbProcess.Id
    Start-Sleep -Seconds 3
}
& (Join-Path $taskMysql 'bin\mysql.exe') --no-defaults --host=127.0.0.1 "--port=$MysqlPort" --user=root --execute='CREATE DATABASE IF NOT EXISTS gitpress_forms_test CHARACTER SET utf8mb4;'
if ($LASTEXITCODE) { throw 'Could not create test database.' }
$taskPhpIni = Join-Path $taskRuntime 'php.ini'
$taskExt = (Join-Path $taskPhp 'ext') -replace '\\','/'
@"
extension_dir="$taskExt"
extension=mysqli
extension=openssl
extension=mbstring
extension=fileinfo
extension=curl
extension=sodium
extension=gd
extension=zip
memory_limit=256M
upload_max_filesize=12M
post_max_size=16M
date.timezone=UTC
display_errors=Off
log_errors=On
error_log="$($taskRuntime -replace '\\','/')/php-error.log"
"@ | Set-Content -LiteralPath $taskPhpIni
$taskConfig = @'
<?php
define('DB_NAME', 'gitpress_forms_test');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_HOST', '127.0.0.1:__MYSQL_PORT__');
define('DB_CHARSET', 'utf8mb4');
$table_prefix = 'gpf_test_';
define('WP_HOME', 'http://127.0.0.1:__HTTP_PORT__');
define('WP_SITEURL', WP_HOME);
define('WP_DEBUG', true);
define('WP_DEBUG_DISPLAY', false);
define('WP_DEBUG_LOG', true);
define('DISABLE_WP_CRON', true);
define('AUTH_KEY', 'isolated-gitpress-test-site-auth-key');
define('SECURE_AUTH_KEY', 'isolated-gitpress-test-secure-key');
define('LOGGED_IN_KEY', 'isolated-gitpress-test-login-key');
define('NONCE_KEY', 'isolated-gitpress-test-nonce-key');
define('AUTH_SALT', 'isolated-gitpress-test-auth-salt');
define('SECURE_AUTH_SALT', 'isolated-gitpress-test-secure-salt');
define('LOGGED_IN_SALT', 'isolated-gitpress-test-login-salt');
define('NONCE_SALT', 'isolated-gitpress-test-nonce-salt');
if (!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/');
require_once ABSPATH . 'wp-settings.php';
'@
$taskConfig.Replace('__MYSQL_PORT__', [string]$MysqlPort).Replace('__HTTP_PORT__', [string]$HttpPort) | Set-Content -LiteralPath (Join-Path $taskWordPress 'wp-config.php')
& (Join-Path $taskPhp 'php.exe') -c $taskPhpIni (Join-Path $PSScriptRoot 'native-test-install.php')
if ($LASTEXITCODE) { throw 'WordPress test installation failed.' }
$taskInfo = @{ php = (Join-Path $taskPhp 'php.exe'); ini = $taskPhpIni; wordpress = $taskWordPress; url = "http://127.0.0.1:$HttpPort" }
$taskInfo | ConvertTo-Json | Set-Content -LiteralPath (Join-Path $taskRuntime 'native.json')
Write-Output "Isolated WordPress ready at $taskWordPress. Start with npm run dev:native."
