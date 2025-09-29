<?php
// セキュリティチェック
if (!defined('SECURE_ACCESS')) {
    exit('Direct access not allowed');
}

// config.phpがまだ読み込まれていない場合のみ読み込み
if (!defined('GEMINI_API_KEY')) {
    // サーバー環境を判定してconfig_server.phpを優先
    if (file_exists(__DIR__ . "/config_server.php")) {
        require_once(__DIR__ . "/config_server.php");
    } else {
        require_once(__DIR__ . "/config.php");
    }
}

function analyzeImagesForPersonalPreferences($imagePaths) {
    // デバッグ情報を追加
    error_log("=== Gemini API Call ===");
    error_log("API Key defined: " . (defined('GEMINI_API_KEY') ? 'Yes' : 'No'));
    error_log("API Key length: " . (defined('GEMINI_API_KEY') ? strlen(GEMINI_API_KEY) : 0));
    error_log("API URL: " . (defined('GEMINI_API_URL') ? GEMINI_API_URL : 'Not defined'));
    error_log("Image count: " . count($imagePaths));
    
    $parts = [
        [
            "text" => "これらの画像を総合的に分析して、この人の興味・関心、価値観、ライフスタイルを詳しく分析してください。

以下の形式で分析結果を出力してください：

最も強く表れているのは、**「[一番顕著な特徴]」**ということです。[具体的な根拠]

それを踏まえて、具体的には以下のような興味・関心をお持ちだと推測します。

① [カテゴリ名]
[具体的な分析内容と根拠となる画像の特徴]

② [カテゴリ名]
[具体的な分析内容と根拠となる画像の特徴]

③ [カテゴリ名]
[具体的な分析内容と根拠となる画像の特徴]

④ [カテゴリ名]
[具体的な分析内容と根拠となる画像の特徴]

重要な指示：
- 画像から読み取れる具体的な要素（被写体、場所、活動、色彩、構図など）を根拠として使用
- この人の価値観やライフスタイルまで踏み込んで分析
- 家族、趣味、旅行、食事、自然、文化、アート、ファッションなど様々な角度から考察
- 単なる画像の説明ではなく、人となりが伝わる温かみのある分析にする
- 日本語で自然な文章で回答する"
        ]
    ];
    
    // 画像データを追加
    foreach ($imagePaths as $path) {
        if (file_exists($path)) {
            $imageData = base64_encode(file_get_contents($path));
            $mimeType = mime_content_type($path);
            
            $parts[] = [
                "inline_data" => [
                    "mime_type" => $mimeType,
                    "data" => $imageData
                ]
            ];
        }
    }
    
    $payload = [
        "contents" => [
            [
                "parts" => $parts
            ]
        ],
        "generationConfig" => [
            "temperature" => 0.7,
            "topP" => 0.8,
            "maxOutputTokens" => 8192
        ]
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, GEMINI_API_URL . '?key=' . GEMINI_API_KEY);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_error($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        error_log("Curl Error: " . $error);
        return false;
    }
    
    curl_close($ch);
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            return $result['candidates'][0]['content']['parts'][0]['text'];
        } else {
            error_log("Unexpected API response: " . $response);
            return false;
        }
    } else {
        error_log("HTTP Error $httpCode: " . $response);
        return false;
    }
}

// 分析結果からキーワードを抽出する関数
function extractKeywordsFromAnalysis($analysisText) {
    $keywords = [];
    
    // 家族関連キーワード
    if (preg_match('/(家族|お子様|子供|ファミリー)/u', $analysisText)) {
        $keywords[] = '家族連れ';
        $keywords[] = '子供向け';
    }
    
    // アウトドア・自然関連
    if (preg_match('/(自然|アウトドア|公園|山|海|川|緑)/u', $analysisText)) {
        $keywords[] = '自然';
        $keywords[] = 'アウトドア';
        $keywords[] = '公園';
    }
    
    // 文化・観光関連
    if (preg_match('/(神社|寺|文化|観光|歴史|美術館|博物館)/u', $analysisText)) {
        $keywords[] = '文化財';
        $keywords[] = '観光地';
        $keywords[] = '歴史';
    }
    
    // 体験型・アクティビティ
    if (preg_match('/(体験|アクティビティ|テーマパーク|イベント|ワークショップ)/u', $analysisText)) {
        $keywords[] = '体験型';
        $keywords[] = 'アクティビティ';
        $keywords[] = 'テーマパーク';
    }
    
    // カフェ・グルメ関連
    if (preg_match('/(カフェ|コーヒー|食|グルメ|レストラン|料理)/u', $analysisText)) {
        $keywords[] = 'カフェ';
        $keywords[] = 'グルメ';
        $keywords[] = 'レストラン';
    }
    
    // アート・クリエイティブ
    if (preg_match('/(アート|芸術|デザイン|写真|クリエイティブ)/u', $analysisText)) {
        $keywords[] = 'アート';
        $keywords[] = 'ギャラリー';
        $keywords[] = 'クリエイティブ';
    }
    
    // ショッピング・ファッション
    if (preg_match('/(ショッピング|買い物|ファッション|おしゃれ)/u', $analysisText)) {
        $keywords[] = 'ショッピング';
        $keywords[] = 'ファッション';
    }
    
    return array_unique($keywords);
}

// テスト用関数（開発時のデバッグに使用）
function testGeminiConnection() {
    $testImagePath = __DIR__ . "/upload/test.jpg"; // テスト用画像パス
    
    if (file_exists($testImagePath)) {
        $result = analyzeImagesForPersonalPreferences([$testImagePath]);
        if ($result) {
            echo "✅ Gemini API接続成功\n";
            echo "分析結果: " . substr($result, 0, 100) . "...\n";
            return true;
        }
    }
    
    echo "❌ Gemini API接続失敗\n";
    return false;
}
?>