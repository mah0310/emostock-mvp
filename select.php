<?php
session_start();
$user_id = $_SESSION["user_id"];

//エラー表示
ini_set("display_errors", 1);

//2. DB接続します
include("funcs.php");
sschk();
$pdo = db_conn();

//２．データ表示SQL作成
$sql = "SELECT * FROM es_table WHERE user_id = :user_id ORDER BY date DESC LIMIT 50";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$status = $stmt->execute();

//３．データ表示
$values = "";
if($status==false) {
  $error = $stmt->errorInfo();
  exit("SQLError:".$error[2]);
}

//全データ取得
$values = $stmt->fetchAll(PDO::FETCH_ASSOC);

// JSONエンコードを安全に実行
try {
    $json = json_encode($values, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        $json = "[]";
    }
} catch (Exception $e) {
    $json = "[]";
}

// 好み分析結果があるかチェック（安全版）
$analysis_result = null;
try {
    $analysis_sql = "SELECT analysis_result FROM user_analysis WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1";
    $analysis_stmt = $pdo->prepare($analysis_sql);
    $analysis_stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $analysis_stmt->execute();
    $analysis_result = $analysis_stmt->fetch();
} catch (PDOException $e) {
    $analysis_result = null;
}

// 分析用画像を取得
$analysisImages = [];
$uploadDir = "upload/analysis/";
if (is_dir($uploadDir)) {
    $pattern = $uploadDir . "*_" . $user_id . "_*";
    $analysisImages = glob($pattern);
    // 新しい順にソート
    usort($analysisImages, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
}

?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>All Post</title>
<link rel="stylesheet" href="css/range.css">
<link href="css/bootstrap.min.css" rel="stylesheet">
<style>div{padding: 10px;font-size:16px;}</style>
<style>
        table {
            border: solid 1px black;
            width: 100%;
            margin: 30px 0;
        }
        td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }

        /* 好み分析セクションのスタイル */
        .analysis-section {
            background:#f5f2ed;
            border-radius: 15px;
            padding: 30px;
            margin: 20px 0 40px 0;
            text-align: center;
            color: #424242;
        }
        
        .analysis-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 15px;
            font-family:'Oswald', sans-serif;
        }
        
        .analysis-subtitle {
            font-size: 16px;
            margin-bottom: 25px;
            opacity: 0.9;
        }
        
        .analysis-btn {
            background: #e4b7a0;
            border: 2px solid white;
            color: #424242;
            padding: 15px 30px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin: 0 10px;
        }
        
        .analysis-btn:hover {
            background: white;
            color: #e4b7a0;
            text-decoration: none;
            transform: translateY(-2px);
        }
        
        .analysis-btn.secondary {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.5);
        }
        
        .status-message {
            background: rgba(255,255,255,0.1);
            padding: 10px 20px;
            border-radius: 25px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        /* 画像ギャラリーのスタイル */
        .gallery-section {
            margin: 40px 0;
        }
        
        .gallery-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
            border-bottom: 2px solid #e4b7a0;
            padding-bottom: 10px;
        }
        
        .image-gallery {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin: 20px 0;
        }
        
        .gallery-item {
            aspect-ratio: 1;
            overflow: hidden;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        
        .gallery-item:hover {
            transform: scale(1.05);
        }
        
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        
        .gallery-empty {
            text-align: center;
            color: #666;
            padding: 40px;
            background: #f8f9fa;
            border-radius: 10px;
            border: 2px dashed #ddd;
        }

        /* モーダルスタイル */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            max-width: 90%;
            max-height: 90%;
            border-radius: 10px;
        }
        
        .modal-close {
            position: absolute;
            top: 20px;
            right: 30px;
            color: white;
            font-size: 30px;
            cursor: pointer;
        }

        /* レスポンシブ対応 */
        @media (max-width: 768px) {
            .image-gallery {
                gap: 5px;
            }
            
            .gallery-item {
                border-radius: 5px;
            }
        }
</style>
</head>

<body id="main">
<!-- Head[Start] -->
<header>
  <nav class="navbar navbar-default">
    <div class="container-fluid" style="display: flex; align-items: center; background-color:#e4b7a0; font-weight:bold;">
      <div class="navbar-header"><a class="navbar-brand" href="login.php">ログイン</a></div>
      <div class="navbar-header"><a class="navbar-brand" href="logout.php">ログアウト</a></div>
    </div>
  </nav>
</header>
<!-- Head[End] -->

<!-- Main[Start] -->
<div>
    <div class="container jumbotron">
        <!-- 好み分析セクション[Start] -->
        <div class="analysis-section">
            <div class="analysis-title">あなたの「好き」を見つける</div>
            
            <?php if ($analysis_result): ?>
                <div class="status-message">
                    分析完了！あなたの好みを基におすすめスポットをご提案できます
                </div>
                <div class="analysis-subtitle">
                    あなたの好み傾向が分析されています
                </div>
                <a href="preference_result.php" class="analysis-btn">
                    分析結果を見る
                </a>
                <a href="image_upload.php" class="analysis-btn secondary">
                    再分析する
                </a>
            <?php else: ?>
                <div class="analysis-subtitle">
                    いいなと思う画像10枚から、あなたの「好き」を分析します<br>
                    分析結果を基に、おすすめのスポットをご提案します
                </div>
                <a href="image_upload.php" class="analysis-btn">
                    自分の「好き」を知る
                </a>
            <?php endif; ?>
        </div>
        <!-- 好み分析セクション[End] -->

        <!-- 分析用画像ギャラリー[Start] -->
        <?php if (!empty($analysisImages)): ?>
        <div class="gallery-section">
            <div class="gallery-title">あなたがアップロードした「好き」な画像</div>
            <div class="image-gallery">
                <?php foreach (array_slice($analysisImages, 0, 12) as $imagePath): ?>
                    <div class="gallery-item" onclick="openModal('<?php echo htmlspecialchars($imagePath); ?>')">
                        <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="好きな画像" loading="lazy">
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (count($analysisImages) > 12): ?>
                <p style="text-align: center; color: #666;">
                    他 <?php echo count($analysisImages) - 12; ?> 枚の画像があります
                </p>
            <?php endif; ?>
        </div>
        <?php elseif ($analysis_result): ?>
        <div class="gallery-section">
            <div class="gallery-title">あなたがアップロードした「好き」の画像</div>
            <div class="gallery-empty">
                画像ファイルが見つかりませんでした。<br>
                再度分析を行うと、ここに画像が表示されます。
            </div>
        </div>
        <?php endif; ?>
        <!-- 分析用画像ギャラリー[End] -->

        

    </div>
</div>
<!-- Main[End] -->

<!-- 画像モーダル -->
<div id="imageModal" class="modal" onclick="closeModal()">
    <span class="modal-close">&times;</span>
    <img class="modal-content" id="modalImage">
</div>

<script>
// 画像モーダル機能
function openModal(imageSrc) {
    document.getElementById('imageModal').style.display = 'flex';
    document.getElementById('modalImage').src = imageSrc;
}

function closeModal() {
    document.getElementById('imageModal').style.display = 'none';
}

// ESCキーでモーダルを閉じる
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});

const a = '<?php echo $json; ?>';
console.log(JSON.parse(a));
</script>
</body>
</html>