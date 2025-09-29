<!-- Sign in 画面を作ろう！！ -->

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width">
<style>div{padding: 10px;font-size:16px;}</style>
<link href="https://fonts.googleapis.com/css2?family=Oswald&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/main.css">
<title>Sign up </title>
</head>

<body>
  <div class="logo" style="font-weight: bold;">emoStock</div>
  <div class="form-container">
    <div class="form-title">Sign Up</div>
    <form name="form" action="signin_act.php" method="post" class="form">
      <label>Fullname</label>
      <input type="text" name="name" placeholder="Enter full name..."><br>
      <label>Email</label>
      <input type="text" name="email" placeholder="Enter email..."><br>
      <label>Passward</label>
      <input type="password" name="lpw" placeholder="Enter password..."><br>
      <input type="submit" value="SIGN UP">
    </form>
    <div style=padding-top:20px;><a href="./login.php">すでにアカウントをお持ちの方はこちら</a></div>
</body>
</html>