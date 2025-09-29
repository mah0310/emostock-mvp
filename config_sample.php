<?php
// これはテンプレートです。
// 本番環境では config.php をこの内容に沿って作成してください。
$db_name = "your_database_name";
$db_id   = "your_username";
$db_pw   = "your_password";
$db_host = "localhost"; // 例：localhost または mysql.example.com

// 本番環境では config_server.php をこの内容に沿って作成してください。
$db_name = "";//データベース名
$db_id   = "";//アカウント名（さくらコントロールパネルに表示されています）
$db_pw   = "";//パスワード(さくらサーバー最初にDB作成する際に設定したパスワード)
$db_host = "";//例）mysql**db.ne.jp


// どちらの場合も以下を付記してください。
// Gemini API設定
define('GEMINI_API_KEY', 'your_gemini_api_key_here');
define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent');
// セキュリティフラグ
if (!defined('SECURE_ACCESS')) {
    define('SECURE_ACCESS', true);
}
?>