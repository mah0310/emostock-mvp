<?php
session_start();

// PHP設定を強制変更
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '50M');
ini_set('max_execution_time', '300');
ini_set('memory_limit', '256M');

// エラー表示設定
ini_set("display_errors", 1);
error_reporting(E_ALL);

// セキュリティアクセスを定義
if (!defined('SECURE_ACCESS')) {
    define('SECURE_ACCESS', true);
}

// 必要なファイルを読み込み
include("funcs.php");
sschk();
include("gemini_api.php");

$user_id = $_SESSION["user_id"];
$pdo = db_conn();

// アップロード先ディレクトリの設定
$uploadDir = "upload/analysis/";
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
    chmod($uploadDir, 0777);
}

$uploadedFiles = [];
$errors = [];

// アップロードされた画像を処理
if (isset($_FILES['images']) && is_array($_FILES['images']['tmp_name'])) {
    foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
        if (is_uploaded_file($tmpName) && $_FILES['images']['error'][$key] === 0) {
            $originalName = $_FILES['images']['name'][$key];
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            
            // 画像ファイルのチェック
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($extension, $allowedExtensions)) {
                $errors[] = "ファイル「{$originalName}」は対応していない形式です。";
                continue;
            }
            
            // ファイルサイズチェック（5MB制限）
            if ($_FILES['images']['size'][$key] > 5 * 1024 * 1024) {
                $errors[] = "ファイル「{$originalName}」のサイズが大きすぎます（5MB以下）。";
                continue;
            }
            
            // ユニークなファイル名生成
            $fileName = date("YmdHis") . "_" . $user_id . "_" . uniqid() . "." . $extension;
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($tmpName, $uploadPath)) {
                $uploadedFiles[] = $uploadPath;
                
                // ファイル権限設定
                chmod($uploadPath, 0644);
            } else {
                $errors[] = "ファイル「{$originalName}」のアップロードに失敗しました。";
            }
        }
    }
}

// エラーがある場合は元のページに戻る
if (!empty($errors)) {
    $_SESSION['upload_errors'] = $errors;
    redirect("image_upload.php?error=upload_failed");
}

// 最低5枚のチェック
if (count($uploadedFiles) < 5) {
    $_SESSION['upload_errors'] = ["最低5枚の画像をアップロードしてください。現在: " . count($uploadedFiles) . "枚"];
    redirect("image_upload.php?error=insufficient_images");
}

// Gemini APIで分析実行
try {
    $analysisResult = analyzeImagesForPersonalPreferences($uploadedFiles);
    
    if ($analysisResult) {
        // キーワード抽出
        $keywords = extractKeywordsFromAnalysis($analysisResult);
        
        // データベース保存処理
        try {
            // 既存の分析結果があるかチェック
            $checkStmt = $pdo->prepare("SELECT id FROM user_analysis WHERE user_id = :user_id");
            $checkStmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $checkStmt->execute();
            
            if ($checkStmt->fetch()) {
                // 更新
                $stmt = $pdo->prepare("UPDATE user_analysis SET analysis_result = :analysis_result, keywords = :keywords, created_at = NOW() WHERE user_id = :user_id");
            } else {
                // 新規挿入
                $stmt = $pdo->prepare("INSERT INTO user_analysis (user_id, analysis_result, keywords) VALUES (:user_id, :analysis_result, :keywords)");
            }
            
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':analysis_result', $analysisResult, PDO::PARAM_STR);
            $stmt->bindValue(':keywords', json_encode($keywords, JSON_UNESCAPED_UNICODE), PDO::PARAM_STR);
            
            $status = $stmt->execute();
            
            if ($status === false) {
                $error = $stmt->errorInfo();
                throw new Exception("SQLエラー: " . $error[2]);
            }
            
            // 成功時は結果ページにリダイレクト
            $_SESSION['analysis_success'] = "好み分析が完了しました！";
            redirect("preference_result.php");
            
        } catch (PDOException $e) {
            echo "データベースエラーの詳細:<br>";
            echo "- エラーメッセージ: " . $e->getMessage() . "<br>";
            echo "- SQLState: " . $e->getCode() . "<br>";
            echo "- 分析結果の長さ: " . strlen($analysisResult) . "文字<br>";
            echo "- キーワード数: " . count($keywords) . "個<br>";
            exit();
        }
        
    } else {
        // 分析失敗時の詳細情報表示
        echo "分析失敗の詳細情報:<br>";
        echo "- アップロードされたファイル数: " . count($uploadedFiles) . "<br>";
        echo "- APIキー設定: " . (defined('GEMINI_API_KEY') ? "設定済み" : "未設定") . "<br>";
        echo "- APIキー長: " . (defined('GEMINI_API_KEY') ? strlen(GEMINI_API_KEY) : 0) . "文字<br>";
        echo "- ファイルパス例: " . (isset($uploadedFiles[0]) ? $uploadedFiles[0] : "なし") . "<br>";
        
        if (isset($uploadedFiles[0])) {
            echo "- ファイル存在確認: " . (file_exists($uploadedFiles[0]) ? "存在する" : "存在しない") . "<br>";
            echo "- ファイルサイズ: " . (file_exists($uploadedFiles[0]) ? filesize($uploadedFiles[0]) : 0) . " bytes<br>";
        }
        
        $_SESSION['upload_errors'] = ["画像分析に失敗しました。詳細を確認してください。"];
        echo "<br><a href='image_upload.php'>戻る</a>";
        exit();
    }

} catch (Exception $e) {
    error_log("Analysis Error: " . $e->getMessage());
    $_SESSION['upload_errors'] = ["分析中にエラーが発生しました: " . $e->getMessage()];
    
    echo "<br><a href='image_upload.php'>戻る</a>";
    exit();
}

// 万が一ここまで来た場合のフォールバック
echo "予期しないフロー: 処理が完了しませんでした<br>";
echo "<a href='image_upload.php'>戻る</a>";
exit();
?>
