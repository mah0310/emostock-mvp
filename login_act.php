<?php
//最初にSESSIONを開始！！ココ大事！！
session_start();

//POST値を受け取る
$email = $_POST["email"]; //lid
$lpw = $_POST["lpw"]; //lpw

//1.  DB接続します
include("funcs.php");
$pdo = db_conn();

//2. データ登録SQL作成
//* PasswordがHash化→条件はlidのみ！！
$stmt = $pdo->prepare("SELECT * FROM esuser_table WHERE email=:email"); 
$stmt->bindValue(':email', $email, PDO::PARAM_STR);
$status = $stmt->execute();

//3. SQL実行時にエラーがある場合STOP
if($status==false){
    sql_error($stmt);
}

//4. 抽出データ数を取得
$val = $stmt->fetch();         //1レコードだけ取得する方法
//$count = $stmt->fetchColumn(); //SELECT COUNT(*)で使用可能()


//5.該当１レコードがあればSESSIONに値を代入
//入力したPasswordと暗号化されたPasswordを比較！[戻り値：true,false]
$pw = password_verify($lpw, $val["lpw"]); //$lpw = password_hash($lpw, PASSWORD_DEFAULT);   //パスワードハッシュ化
//$pw = $lpw == $val["lpw"]; これはハッシュ化していないときじゃないと使えない！
if($pw){ 
  //Login成功時
  $_SESSION["chk_ssid"]  = session_id();
  $_SESSION["kanri_flg"] = $val['kanri_flg'];
  $_SESSION["name"]      = $val['name'];
  $_SESSION["user_id"] = $val["id"];
  
  //kanri_flgで場合分けしてみる
  if($_SESSION["kanri_flg"] == 1){
    redirect("select1.php");
  }else{
    redirect("select.php");
  }
  

}else{
  //Login失敗時(login.phpへ)
  redirect("login.php");

}

exit();


