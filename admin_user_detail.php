<?php
session_start();

// 管理者権限チェック
if ($_SESSION["kanri_flg"] != 1) {
    exit('管理者権限が必要です');
}

//エラー表示
ini_set("display_errors", 1);

//2. DB接続します
include("funcs.php");
sschk();
$pdo = db_conn();

$id = $_GET['id'];

// ユーザー基本情報を取得
$stmt = $pdo->prepare("SELECT * FROM esuser_table WHERE id = :id");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch();

if (!$user) {
    exit('ユーザーが見つかりません');
}

// ユーザーの投稿データを取得
$stmt = $pdo->prepare("SELECT * FROM es_table WHERE user_id = :user_id ORDER BY date DESC");
$stmt->bindValue(':user_id', $id, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ユーザーの分析データを取得
$stmt = $pdo->prepare("SELECT * FROM user_analysis WHERE user_id = :user_id ORDER BY created_at DESC");
$stmt->bindValue(':user_id', $id, PDO::PARAM_INT);
$stmt->execute();
$analysis = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ユーザー詳細 - <?=htmlspecialchars($user['name'])?></title>
<link rel="stylesheet" href="css/range.css">
<link href="css/bootstrap.min.css" rel="stylesheet">
<style>div{padding: 10px;font-size:16px;}</style>
<style>
        table {
            border: solid 1px black;
            width: 100%;
            margin: 30px 0;
        }
        td, th {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .user-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        .section-title {
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .keyword-tag {
            display: inline-block;
            background: #e9ecef;
            color: #495057;
            padding: 5px 12px;
            border-radius: 15px;
            margin: 3px;
            font-size: 0.85rem;
        }
        .analysis-result {
            background: white;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            max-height: 200px;
            overflow-y: auto;
        }
</style>
</head>
<body id="main">
<!-- Head[Start] -->
<header>
  <nav class="navbar navbar-default">
    <div class="container-fluid" style="display: flex; align-items: center; background-color:#e4b7a0; font-weight:bold;">
      <div>ユーザー詳細 - <?=htmlspecialchars($user['name'])?></div>
      <div class="navbar-header"><a class="navbar-brand" href="select1.php">ユーザー管理</a></div>
      <div class="navbar-header"><a class="navbar-brand" href="logout.php">ログアウト</a></div>
    </div>
  </nav>
</header>
<!-- Head[End] -->

<!-- Main[Start] -->
<div>
    <div class="container jumbotron">
        
        <!-- ユーザー基本情報 -->
        <div class="user-info">
            <h2>ユーザー基本情報</h2>
            <div class="row">
                <div class="col-md-6">
                    <strong>ID:</strong> <?=$user['id']?><br>
                    <strong>名前:</strong> <?=htmlspecialchars($user['name'])?><br>
                    <strong>メールアドレス:</strong> <?=htmlspecialchars($user['email'])?><br>
                </div>
                <div class="col-md-6">
                    <strong>権限:</strong> <?=$user['kanri_flg'] == 1 ? '管理者' : '一般ユーザー'?><br>
                    <strong>投稿数:</strong> <?=count($posts)?>件<br>
                    <strong>分析回数:</strong> <?=count($analysis)?>回<br>
                </div>
            </div>
        </div>

        <!-- 分析データ -->
        <?php if (!empty($analysis)): ?>
        <h3 class="section-title">好み分析データ</h3>
        <?php foreach ($analysis as $idx => $anal): ?>
            <div style="margin-bottom: 30px;">
                <h4>分析 #<?=$idx + 1?> (<?=date('Y/m/d H:i', strtotime($anal['created_at']))?>)</h4>
                
                <?php if ($anal['keywords']): ?>
                    <div style="margin-bottom: 15px;">
                        <strong>抽出キーワード:</strong><br>
                        <?php 
                        $keywords = json_decode($anal['keywords'], true);
                        if ($keywords) {
                            foreach ($keywords as $keyword) {
                                echo '<span class="keyword-tag">' . htmlspecialchars($keyword) . '</span>';
                            }
                        }
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($anal['analysis_result']): ?>
                    <div class="analysis-result">
                        <strong>分析結果:</strong><br>
                        <?=nl2br(htmlspecialchars(mb_substr($anal['analysis_result'], 0, 500)))?><?=mb_strlen($anal['analysis_result']) > 500 ? '...' : ''?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php else: ?>
        <h3 class="section-title">好み分析データ</h3>
        <p>まだ分析データがありません。</p>
        <?php endif; ?>

        <!-- 投稿データ -->
        <?php if (!empty($posts)): ?>
        <h3 class="section-title">投稿データ</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>投稿内容</th>
                    <th>画像</th>
                    <th>タグ</th>
                    <th>投稿日</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($posts as $post): ?>
                <tr>
                    <td><?=$post["id"]?></td>
                    <td><?=htmlspecialchars($post["post"])?></td>
                    <td>
                        <?php if ($post["img"]): ?>
                            <img src="upload/<?=$post["img"]?>" width="60" style="border-radius: 4px;">
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td><?=htmlspecialchars($post["tag"])?></td>
                    <td><?=date('Y/m/d', strtotime($post["date"]))?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <h3 class="section-title">投稿データ</h3>
        <p>まだ投稿がありません。</p>
        <?php endif; ?>

        <!-- 操作ボタン -->
        <div style="margin-top: 30px;">
            <a href="select1.php" class="btn btn-secondary">ユーザー一覧に戻る</a>
            <?php if ($user['id'] != $_SESSION["user_id"] && $user['kanri_flg'] != 1): ?>
                <a href="admin_user_delete.php?id=<?=$user['id']?>" 
                   class="btn btn-danger" 
                   onclick="return confirm('<?=htmlspecialchars($user['name'])?>さんを削除しますか？\n※関連する投稿・分析データもすべて削除されます。')">
                   ユーザー削除
                </a>
            <?php endif; ?>
        </div>

    </div>
</div>
<!-- Main[End] -->
</body>
</html>
