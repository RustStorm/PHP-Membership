<!DOCTYPE html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link rel="shortcut icon" type="image/x-icon" href="gear.ico" />
<link href="login.css" rel="stylesheet" type="text/css" />
<title>Admin</title>
</head>
<body>
<div class="wrapper">
    <div class="info">
       	<b>Login</b>     
    </div> 
    <div class="login">
    <span class="error"><?php if(isset($auth->error)) { echo $auth->error; } ?></span> 
    <br />   
    <form id="login" name="login" method="post" action="<?php echo $_SERVER["SCRIPT_NAME"]; ?>">
    <label>Username</label><br />
    	<input type="text" name="username" id="username" value="admin" /><br /><br />
    <label>Password</label><br />
    <input type="text" name="password" id="password" /><br /> <br />
    <input name="submit" type="submit" id="submit" value="login" />
    </form>
    <br />
    </div>
</div>
</body>
</html>