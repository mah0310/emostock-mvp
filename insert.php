<?php
session_start();
$user_id = $_SESSION["user_id"];

//var_dump($_SESSION["user_id"]);
//exit();

include("funcs.php");
//エラー表示
ini_set("display_errors", 1);

//<!-- POSTデータの取得 -->
$post = $_POST['post'];
$img   = fileUpload("upfile","upload");
$tag = $_POST['tag'];

//<!-- DB接続 -->
$pdo = db_conn();

//<!-- DEに書き込み：SQLをphpで作成し書き込み -->
$stmt = $pdo->prepare("INSERT INTO es_table(user_id, `post`, img, tag,`date`)VALUES(:user_id, :post, :img, :tag, sysdate());");
// 準備するよって意味合い！Sysdateは現在時刻ってこと
// :~ というのは後でいれるよってこと
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$stmt->bindValue(':post',  $post, PDO::PARAM_STR);  //Integer（数値の場合 PDO::PARAM_INT 文字だとSTR)
$stmt->bindValue(':img', $img);
$stmt->bindValue(':tag', $tag, PDO::PARAM_STR);  //Integer（数値の場合 PDO::PARAM_INT)
$status = $stmt->execute();


//<!-- 最後の処理 -->
if($status==false){
    //SQL実行時にエラーがある場合（エラーオブジェクト取得して表示）
    $error = $stmt->errorInfo();
    exit("SQL_Error:".$error[2]);
}else{
    //５．index.phpへリダイレクト
    if ($_SESSION["kanri_flg"] == 1) {
        redirect("select1.php");
    } else {
        redirect("select.php");
    }
}
?>
