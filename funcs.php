<?php
//XSS対応（ echoする場所で使用！それ以外はNG ）
function h($str){
    return htmlspecialchars($str, ENT_QUOTES);
}

//DB接続関数：db_conn()
function db_conn(){
    try {
        // config.phpを読み込んで変数を取得
        $config = include(__DIR__. "/config.php");
        
        // または、require後にextract関数を使用
        require_once(__DIR__. "/config.php");
        
        // 関数スコープで変数にアクセスするため、グローバルから取得
        global $db_name, $db_host, $db_id, $db_pw;
        
        // さらに確実にするため、$GLOBALSからも取得を試行
        if (!isset($db_name)) {
            $db_name = $GLOBALS['db_name'] ?? 'emostock';
            $db_host = $GLOBALS['db_host'] ?? 'localhost';
            $db_id = $GLOBALS['db_id'] ?? 'root';
            $db_pw = $GLOBALS['db_pw'] ?? '';
        }
        
        //localhost以外の場合
        if($_SERVER["HTTP_HOST"] != 'localhost'){
            require_once(__DIR__. "/config_server.php");
        }
        
        return new PDO('mysql:dbname='.$db_name.';charset=utf8;host='.$db_host, $db_id, $db_pw);
    } catch (PDOException $e) {
        exit('DB Connection Error:'.$e->getMessage());
    }
}



//SQLエラー関数：sql_error($stmt)
function sql_error($stmt) {
    $error = $stmt->errorInfo();
    exit("SQLError:".$error[2]);
}

//リダイレクト関数: redirect($file_name)
function redirect($file_name) {
    header("Location: ".$file_name);
    exit();
}


//SessionCheck(スケルトン)
function sschk(){
    if( $_SESSION['chk_ssid'] != session_id() ){
      exit('LOGIN ERROR');
    }else{
      session_regenerate_id(true);
      $_SESSION['chk_ssid'] = session_id();
    }
  }
  
//画像のアップロード
function fileUpload($fname,$path){
    if (isset($_FILES[$fname] ) && $_FILES[$fname]["error"] ==0 ) {
        //ファイル名取得
        $file_name = $_FILES[$fname]["name"];
        //一時保存場所取得
        $tmp_path  = $_FILES[$fname]["tmp_name"];
        //拡張子取得
        $extension = pathinfo($file_name, PATHINFO_EXTENSION);
        //ユニークファイル名作成
        $file_name = date("YmdHis").md5(session_id()) . "." . $extension;
        // FileUpload [--Start--]
        $file_dir_path = $path."/".$file_name;
        if ( is_uploaded_file( $tmp_path ) ) {
            if ( move_uploaded_file( $tmp_path, $file_dir_path ) ) {
                chmod( $file_dir_path, 0644 );
                return $file_name; //成功時：ファイル名を返す
            } else {
                return 1; //失敗時：ファイル移動に失敗
            }
        }
     }else{
         return 2; //失敗時：ファイル取得エラー
     }
}



