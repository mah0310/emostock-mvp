<?php
session_start();
include("funcs.php");
sschk();

$user_id = $_SESSION["user_id"];
$pdo = db_conn();

// 最新の分析結果を取得
$stmt = $pdo->prepare("SELECT analysis_result, keywords, created_at FROM user_analysis WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1");
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$result = $stmt->fetch();

$analysisResult = $result ? $result['analysis_result'] : null;
$keywords = $result ? json_decode($result['keywords'], true) : [];
$analysisDate = $result ? $result['created_at'] : null;

// 成功メッセージの取得・削除
$successMessage = $_SESSION['analysis_success'] ?? null;
unset($_SESSION['analysis_success']);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <title>あなたの好み分析結果 - emoStock</title>
    <link rel="stylesheet" href="css/main.css">
    <style>
        body {
            overflow: visible !important;
            max-height: none !important;
            height: auto !important;
        }

        .analysis-result {
            overflow: visible !important;
            max-height: none !important;
            height: auto !important;
        }

        .analysis-text {
            white-space: pre-line; /* pre-wrapからpre-lineに変更 */
            line-height: 2;        /* 行間を広げる */
        }

        .analysis-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .success-message {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
            font-weight: bold;
        }
        
        .analysis-result {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 15px;
            margin: 20px 0;
            line-height: 1.8;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .analysis-result h3 {
            color: #333;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .analysis-meta {
            background: #e9ecef;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #666;
        }
        
        .keywords-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            border: 1px solid #dee2e6;
        }
        
        .keyword-tag {
            display: inline-block;
            background: #f5f2ed;
            color: black;
            font-weight: bold;
            padding: 5px 12px;
            border-radius: 15px;
            margin: 3px;
            font-size: 13px;
        }
        
        .recommendation-section {
            background:#f5f2ed;
            color: black;
            padding: 30px;
            border-radius: 15px;
            margin: 30px 0;
            text-align: center;
        }
        
        .region-select {
            margin: 20px 0;
        }
        
        .region-select select {
            padding: 10px 15px;
            border: 2px solid white;
            border-radius: 8px;
            font-size: 16px;
            background: white;
            color: #333;
            min-width: 200px;
        }
        
        .recommend-btn {
            background:#e4b7a0;
            border: 2px solid white;
            color: black;
            padding: 15px 30px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
        }
        
        .recommend-btn:hover {
            background: white;
            color:#e4b7a0;
        }
        
        .recommend-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .action-buttons {
            text-align: center;
            margin: 30px 0;
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
        
        .btn-secondary {
            background: #f5f2ed;
            color: black;
        }
        
        .btn-secondary:hover {
            background: #545b62;
            color: white;
            text-decoration: none;
        }
        
        .btn-primary {
            background: #f5f2ed;
            color: black;
        }
        
        .btn-primary:hover {
            background: #545b62;
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="analysis-container">
        <h1 style="text-align: center; color: #333; margin-bottom: 30px;">あなたの好み分析結果</h1>
        
        <?php if ($successMessage): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($analysisResult): ?>
            <div class="analysis-meta">
                分析実行日時: <?php echo date('Y年m月d日 H:i', strtotime($analysisDate)); ?>
                <?php if (!empty($keywords)): ?>
                    | 抽出キーワード数: <?php echo count($keywords); ?>個
                <?php endif; ?>
            </div>
            
            <div class="analysis-result">
                <h3>分析結果</h3>
                <div class="analysis-text">
                    <?php 
                    $safeText = htmlspecialchars($analysisResult, ENT_QUOTES, 'UTF-8');
                    $safeText = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $safeText);
                    echo $safeText;
                    ?>
                </div>
            </div>
            
            <?php if (!empty($keywords)): ?>
                <div class="keywords-section">
                    <h4>抽出されたキーワード</h4>
                    <div>
                        <?php foreach ($keywords as $keyword): ?>
                            <span class="keyword-tag"><?php echo htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- おすすめスポット検索セクション -->
            <div class="recommendation-section">
                <h3 style="border: none; color: black; margin-bottom: 15px;">あなたにおすすめのスポットを探しましょう</h3>
                <p>分析結果を基に、あなたの好みに合ったスポットをご提案します</p>
                
                <form action="spot_recommendation.php" method="post" id="recommendForm">
                    <div class="region-select">
                        <label for="region" style="display: block; margin-bottom: 10px; font-weight: bold;">地域を選択してください：</label>
                        <select name="region" id="region" required>
                            <option value="">選択してください</option>
                            <optgroup label="関東">
                                <option value="東京都">東京都</option>
                                <option value="神奈川県">神奈川県</option>
                                <option value="千葉県">千葉県</option>
                                <option value="埼玉県">埼玉県</option>
                                <option value="茨城県">茨城県</option>
                                <option value="栃木県">栃木県</option>
                                <option value="群馬県">群馬県</option>
                            </optgroup>
                            <optgroup label="関西">
                                <option value="大阪府">大阪府</option>
                                <option value="京都府">京都府</option>
                                <option value="兵庫県">兵庫県</option>
                                <option value="奈良県">奈良県</option>
                                <option value="和歌山県">和歌山県</option>
                                <option value="滋賀県">滋賀県</option>
                            </optgroup>
                            <optgroup label="中部">
                                <option value="愛知県">愛知県</option>
                                <option value="静岡県">静岡県</option>
                                <option value="岐阜県">岐阜県</option>
                                <option value="三重県">三重県</option>
                                <option value="長野県">長野県</option>
                                <option value="山梨県">山梨県</option>
                            </optgroup>
                            <optgroup label="九州">
                                <option value="福岡県">福岡県</option>
                                <option value="熊本県">熊本県</option>
                                <option value="大分県">大分県</option>
                                <option value="宮崎県">宮崎県</option>
                                <option value="鹿児島県">鹿児島県</option>
                                <option value="佐賀県">佐賀県</option>
                                <option value="長崎県">長崎県</option>
                            </optgroup>
                            <optgroup label="その他">
                                <option value="北海道">北海道</option>
                                <option value="沖縄県">沖縄県</option>
                            </optgroup>
                        </select>
                    </div>
                    
                    <button type="submit" class="recommend-btn" id="searchBtn">
                        おすすめスポットを探す
                    </button>
                </form>
            </div>
            
        <?php else: ?>
            <div style="text-align: center; padding: 50px; background: #f8f9fa; border-radius: 15px;">
                <h3>分析結果が見つかりませんでした</h3>
                <p>まずは画像をアップロードして分析を行ってください。</p>
                <a href="image_upload.php" class="btn btn-primary">画像をアップロードする</a>
            </div>
        <?php endif; ?>
        
        <div class="action-buttons">
            <a href="select.php" class="btn btn-secondary">メニューに戻る</a>
            <a href="image_upload.php" class="btn btn-primary">再分析する</a>
        </div>
    </div>

    <script>
        // フォーム送信時の処理
        document.getElementById('recommendForm').addEventListener('submit', function(e) {
            const region = document.getElementById('region').value;
            const searchBtn = document.getElementById('searchBtn');
            
            if (!region) {
                e.preventDefault();
                alert('地域を選択してください。');
                return false;
            }
            
            // 送信中の表示
            searchBtn.textContent = '検索中...';
            searchBtn.disabled = true;
        });
    </script>
</body>
</html>