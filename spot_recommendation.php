<?php
session_start();
include("funcs.php");
sschk();

$user_id = $_SESSION["user_id"];
$region = $_POST['region'] ?? '';
$pdo = db_conn();

// 地域が選択されていない場合はエラー
if (empty($region)) {
    $_SESSION['upload_errors'] = ["地域を選択してください。"];
    redirect("preference_result.php");
}

// ユーザーの分析結果とキーワードを取得
$stmt = $pdo->prepare("SELECT analysis_result, keywords FROM user_analysis WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1");
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$userdata = $stmt->fetch();

if (!$userdata) {
    $_SESSION['upload_errors'] = ["分析結果が見つかりません。まず画像分析を行ってください。"];
    redirect("image_upload.php");
}

$keywords = json_decode($userdata['keywords'], true) ?? [];
$analysisText = $userdata['analysis_result'];

// Gemini APIでスポット推薦を生成
$recommendedSpots = generateSpotRecommendationsWithGemini($region, $keywords, $analysisText);

echo "<!-- Recommended Spots Count: " . count($recommendedSpots) . " -->";
echo "<!-- Recommended Spots Data: " . print_r($recommendedSpots, true) . " -->";


?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <title><?php echo htmlspecialchars($region); ?>のおすすめスポット - emoStock</title>
    <link rel="stylesheet" href="css/main.css">
    <style>
        /* 既存のスタイルの後に追加 */

        body {
            overflow: visible !important;
            max-height: none !important;
            height: auto !important;
        }

        .spot-description {
            white-space: pre-line;
            line-height: 1.7;
        }

        .spot-reason {
            white-space: pre-line;
            line-height: 1.6;
        }
        /* 上記を追加 */

        .recommendation-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .region-header {
            background:#f5f2ed;
            color:black;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .region-title {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .region-subtitle {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .spots-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin: 30px 0;
        }
        
        .spot-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid #e1e8ed;
        }
        
        .spot-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        
        .spot-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 8px;
        }
        
        .spot-description {
            color: #666;
            line-height: 1.7;
            margin-bottom: 15px;
        }
        
        .spot-reason {
            background: #f8f9ff;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #007bff;
            font-size: 14px;
        }
        
        .reason-label {
            font-weight: bold;
            color: #007bff;
            margin-bottom: 8px;
        }
        
        .keywords-match {
            background: #e8f5e8;
            color: #2d5a2d;
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 12px;
            display: inline-block;
            margin: 3px;
        }
        
        .analysis-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            border: 1px solid #dee2e6;
        }
        
        .summary-title {
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .action-buttons {
            text-align: center;
            margin: 40px 0;
        }
        
        .btn {
            padding: 12px 25px;
            margin: 0 10px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background:#f5f2ed;
            color: black;
        }
        
        .btn-secondary {
            background:#f5f2ed;
            color: black;
        }
        
        .btn:hover {
            text-decoration: none;
            transform: translateY(-2px);
        }
        
        .btn-primary:hover {
            background:#545b62;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #545b62;
            color: white;
        }
        
        .loading-message {
            text-align: center;
            padding: 50px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="recommendation-container">
        <div class="region-header">
            <div class="region-title"><?php echo htmlspecialchars($region); ?>のおすすめスポット</div>
            <div class="region-subtitle">あなたの好み分析結果を基にセレクトしました</div>
        </div>
        
        <div class="analysis-summary">
            <div class="summary-title">あなたの好みキーワード</div>
            <div>
                <?php foreach ($keywords as $keyword): ?>
                    <span class="keywords-match"><?php echo htmlspecialchars($keyword); ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="spots-grid">
            <?php if (!empty($recommendedSpots)): ?>
                <?php foreach ($recommendedSpots as $spot): ?>
                    <div class="spot-card">
                        <div class="spot-title"><?php echo htmlspecialchars($spot['title']); ?></div>
                        <div class="spot-description"><?php echo nl2br(htmlspecialchars($spot['description'])); ?></div>
                        <?php if (!empty($spot['reason'])): ?>
                            <div class="spot-reason">
                                <div class="reason-label">おすすめの理由</div>
                                <?php echo htmlspecialchars($spot['reason']); ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($spot['matching_keywords'])): ?>
                            <div style="margin-top: 10px;">
                                <?php foreach ($spot['matching_keywords'] as $keyword): ?>
                                    <span class="keywords-match"><?php echo htmlspecialchars($keyword); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="loading-message">
                    <h3>スポット情報を取得中...</h3>
                    <p>現在、<?php echo htmlspecialchars($region); ?>のおすすめスポットを検索しています。</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="action-buttons">
            <a href="preference_result.php" class="btn btn-secondary">分析結果に戻る</a>
            <a href="select.php" class="btn btn-primary">メニューに戻る</a>
        </div>
    </div>
</body>
</html>

<?php
function generateSpotRecommendationsWithGemini($region, $keywords, $analysisText) {
    // セキュリティフラグを設定
    if (!defined('SECURE_ACCESS')) {
        define('SECURE_ACCESS', true);
    }
    
    // config.phpを読み込み（Gemini API設定）
    require_once(__DIR__ . "/config.php");
    
    // キーワードを文字列に変換
    $keywordsString = implode(', ', $keywords);
    
    // コスト削減版の簡潔なプロンプト
    $prompt = "{$region}の3スポットをJSON形式で：
        {\"spots\":[{\"title\":\"名前\",\"description\":\"説明\",\"reason\":\"理由\"}]}
        キーワード: {$keywordsString}";
    $payload = [
        "contents" => [
            [
                "parts" => [
                    [
                        "text" => $prompt
                    ]
                ]
            ]
        ],
        "generationConfig" => [
            "temperature" => 0.7,
            "topP" => 0.8,
            "maxOutputTokens" => 4096  // 2048から1024に変更
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // デバッグ情報を表示
    echo "<!-- API Debug Info -->";
    echo "<!-- HTTP Status: " . $httpCode . " -->";
    echo "<!-- Raw Response: " . htmlspecialchars($response) . " -->";
    
    if (curl_error($ch)) {
        echo "<!-- CURL Error: " . curl_error($ch) . " -->";
        curl_close($ch);
        return getFallbackSpots($region, $keywords);
    }
    
    curl_close($ch);
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        echo "<!-- JSON Decode Result: " . htmlspecialchars(print_r($result, true)) . " -->";
        
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            $responseText = $result['candidates'][0]['content']['parts'][0]['text'];
            echo "<!-- Response Text: " . htmlspecialchars($responseText) . " -->";
            
            // JSONを抽出（```json ``` のコードブロックの場合も対応）
            $jsonPattern = '/```json\s*(.*?)\s*```/s';
            if (preg_match($jsonPattern, $responseText, $matches)) {
                echo "<!-- Found JSON in code block -->";
                $jsonText = $matches[1];
            } else {
                echo "<!-- No code block found, using full response -->";
                $jsonText = $responseText;
            }
            
            echo "<!-- Extracted JSON: " . htmlspecialchars($jsonText) . " -->";
            $spotsData = json_decode($jsonText, true);
            echo "<!-- Parsed Spots: " . htmlspecialchars(print_r($spotsData, true)) . " -->";
            
            // 修正：柔軟なデータ処理
            if ($spotsData && isset($spotsData['spots']) && is_array($spotsData['spots'])) {
                // 不足しているフィールドを補完
                foreach ($spotsData['spots'] as &$spot) {
                    if (!isset($spot['reason'])) {
                        $spot['reason'] = 'あなたの好みに合う要素が含まれているため';
                    }
                    if (!isset($spot['matching_keywords'])) {
                        $spot['matching_keywords'] = array_slice($keywords, 0, 2);
                    }
                }
                
                echo "<!-- API Success: Found " . count($spotsData['spots']) . " spots -->";
                return $spotsData['spots'];
            } else {
                echo "<!-- API Parse Failed: spotsData structure invalid -->";
            }
        } else {
            echo "<!-- No response text found -->";
        }
    } else {
        echo "<!-- HTTP Error: " . $httpCode . " -->";
    }
    
    // APIが失敗した場合はフォールバックデータを返す
    echo "<!-- Using fallback spots -->";
    return getFallbackSpots($region, $keywords);
}

function getFallbackSpots($region, $keywords) {
    // APIが失敗した場合のフォールバックデータ
    $fallbackSpots = [
        [
            'title' => $region . 'の人気観光スポット',
            'description' => 'この地域で最も人気の観光地です。多くの方に愛され続けている魅力的なスポットです。',
            'reason' => 'あなたの好みに合う要素が含まれているため',
            'matching_keywords' => array_slice($keywords, 0, 2)
        ],
        [
            'title' => $region . 'の文化施設',
            'description' => '地域の歴史や文化を学べる貴重な施設です。新しい発見と感動が待っています。',
            'reason' => '文化的な体験を好まれる傾向があるため',
            'matching_keywords' => array_slice($keywords, 0, 2)
        ],
        [
            'title' => $region . 'の自然スポット',
            'description' => '美しい自然に囲まれた癒しのスポットです。心地よい時間を過ごせます。',
            'reason' => '自然の中での時間を大切にされているため',
            'matching_keywords' => array_slice($keywords, 0, 2)
        ]
    ];
    
    return $fallbackSpots;
}
?>