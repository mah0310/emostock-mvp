<?php
include("funcs.php");
session_start();
sschk();

// 管理者権限チェック
if ($_SESSION["kanri_flg"] != 1) {
    exit('管理者権限が必要です');
}

$pdo = db_conn();

$id = $_GET['id'];

// 自分自身の削除を防ぐ
if ($id == $_SESSION["user_id"]) {
    exit('自分自身は削除できません');
}

// 削除対象ユーザーが管理者でないことを確認
$check_stmt = $pdo->prepare("SELECT kanri_flg FROM esuser_table WHERE id=:id");
$check_stmt->bindValue(':id', $id, PDO::PARAM_INT);
$check_stmt->execute();
$user_data = $check_stmt->fetch();

if ($user_data && $user_data['kanri_flg'] == 1) {
    exit('管理者ユーザーは削除できません');
}

// 関連データも一緒に削除（トランザクション使用）
$pdo->beginTransaction();

try {
    // 1. ユーザーの分析データを削除
    $stmt1 = $pdo->prepare("DELETE FROM user_analysis WHERE user_id=:id");
    $stmt1->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt1->execute();

    // 2. ユーザーの投稿データを削除
    $stmt2 = $pdo->prepare("DELETE FROM es_table WHERE user_id=:id");
    $stmt2->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt2->execute();

    // 3. ユーザー本体を削除
    $stmt3 = $pdo->prepare("DELETE FROM esuser_table WHERE id=:id");
    $stmt3->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt3->execute();

    // すべて成功した場合、コミット
    $pdo->commit();
    
    // リダイレクト
    redirect("admin_users.php");

} catch (Exception $e) {
    // エラーが発生した場合、ロールバック
    $pdo->rollback();
    exit("削除エラー: " . $e->getMessage());
}
?>
