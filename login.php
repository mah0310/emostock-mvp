<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width">
<style>div{padding: 10px;font-size:16px;}</style>
<link href="https://fonts.googleapis.com/css2?family=Oswald&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/main.css">
<title>LOGIN</title>
</head>
<body>
  <div class="logo" style="font-weight: bold;">emoStock</div>
  <div class="form-container">
    <div class="form-title">Log in</div>
    <form name="form1" action="login_act.php" method="post" style="padding-top:40px;">
    <label>Email</label>
    <input type="text" name="email" placeholder="Enter email..."><br>
    <label>Passward</label> 
    <input type="password" name="lpw" placeholder="Enter password...">
    <input type="submit" value="LOG IN">
</form>

<div style=padding-top:20px;><a href="./index.php">まだアカウントをお持ちでない方はこちら</a></div>


</body>
</html>