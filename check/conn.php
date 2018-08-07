<?php
$link = mysqli_connect("localhost","root","root1234") or die("连接数据库失败");
mysqli_set_charset($link,"GBK");
$db_bank1 = mysqli_select_db($link,"bank1");





?>