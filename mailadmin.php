<?
define('PROTECTOR', 1);

$headmod = 'mailadmin';//фикс. места

$textl='Почта администрации';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');


@ini_set('max_execution_time', 0);

$mail_arr = array('KraToS' => 'admin@l2war.mobi','Freya-war' => 'Timon273@mail.ru'); // add your mails



$rootpath = '../';


$fsize = 10 * 1024 * 1024;

$valid_extensions = array('djvu', 'jpg', 'jpe', 'jpeg', 'gif', 'rar', 'zip', 'tar', 'exe', 'bmp', 'png', 'apk', 'sis', 'avi', 'jad', 'jar', 'mp4', '3gp', 'doc', 'pdf', 'mp3', 'wav', 'css', 'mpg'); /* добавить своих */

$ftypes = array( 
'sis' => 'application/vnd.symbian.install',
'amr' => 'audio/amr',
'jad' => 'text/vnd.sun.j2me.app-descriptor;charset=UTF-8',
'jar' => 'application/java-archive',
'mp4' => 'video/mp4',
'3gp' => 'video/3gpp',  
'rar' => 'application/x-rar-compressed',
'doc' => 'application/msword', 
'exe' => 'application/octet-stream', 
'pdf' => 'application/pdf', 
'zip' => 'application/zip', 
'mp3' => 'audio/mpeg', 
'wav' => 'audio/x-wav', 
'bmp' => 'image/bmp', 
'gif' => 'image/gif', 
'jpg' => 'image/jpeg', 
'png' => 'image/png', 
'css' => 'text/css', 
'txt' => 'text/plain', 
'mpg' => 'video/mpeg', 
'avi' => 'video/x-msvideo',
'7z' => 'application/x-7z-compressed',
'apk' => 'application/vnd.android.package-archive',
'djvu' => 'image/vnd.djvu',
);  /* добавить своих */

$x = isset($_GET['countinc']) ? intval($_GET['countinc']) : 1;
$ux = isset($_GET['importinc']) ? intval($_GET['importinc']) : 1;
$nameid = (isset($_POST['nameid']) && !$login) ? htmlentities($_POST['nameid'], ENT_QUOTES, 'UTF-8') : $login;
$mess = isset($_POST['mess']) ? htmlentities($_POST['mess'], ENT_QUOTES, 'UTF-8') : '';
$email = isset($_POST['email']) ? $_POST['email'] : '';
$err = array();

if (isset($_POST['submit'])) {
    
if (empty($_POST['nameid'])) {
$err[] = 1;
} elseif (mb_strlen($_POST['nameid'])>20 || mb_strlen($_POST['nameid'])<3) {
$err[] = 2;
}

if (empty($_POST['email'])) {
$err[] = 3;
} elseif(mb_strlen($_POST['email'])>35 || mb_strlen($_POST['email'])<6){
$err[] = 4;
} elseif (!preg_match("/[0-9a-z_]+@[0-9a-z_^\.]+\.[a-z]{2,3}/i", $_POST['email'])) {
$err[] = 5;
}

if (empty($_POST['mess'])) {
$err[] = 6;
} elseif (mb_strlen($_POST['mess'])>2000 || mb_strlen($_POST['mess'])<30) {
$err[] = 7;
}

if ($_POST['capcha'] != $_SESSION['code']) {
$err[] = 8;
}
unset($_SESSION['code']);

if (!array_key_exists($_POST['to_mail'], $mail_arr)) {
$err[] = 9;    
}

if (sizeof($err)>0) {
echo print_form();
unset($err);
} else {
   
$mb = '_=_Multipart_Boundary_' . substr(md5(uniqid(time())), 0, 8);
$to = $mail_arr[$_POST['to_mail']];
$subject = 'Письмо с сайта ' . $home;
$header = 'Content-Type: multipart/mixed; boundary="' . $mb . '"' . PHP_EOL . 'X-Mailer: PHP' . PHP_EOL . 'Reply-To: ' . $email . PHP_EOL;
$body = '--' . $mb . PHP_EOL . 'Content-Type: text/plain; charset="UTF-8"' . PHP_EOL . 'Content-Disposition: inline' . PHP_EOL . 'Content-Transfer-Encoding: base64' . PHP_EOL . PHP_EOL . chunk_split(base64_encode('Письмо от:' . $nameid . PHP_EOL . 'Адрес для ответа:' . $email . PHP_EOL . $mess . PHP_EOL . 'Инф: UA - ' . getua() . ' IP - ' . $_SERVER['REMOTE_ADDR'] . ' (' . getip() . ')')) . PHP_EOL;

if (isset($_POST['importinc'])) {
foreach ($_POST['importinc'] as $value) {
$res = url_file_exists($value);
if (is_array($res)) {
if ($res['size'] > $fsize) {
echo '<div>Файл слишком большой</div>';    
} elseif (!in_array($res['type'], array_values($ftypes))) {
echo '<div>Запрещенный формат файла</div>';    
} else {
$newfile = (file_exists($dir . DIRECTORY_SEPARATOR . basename(rtrim($value, '/')))) ? basename(rtrim($value, '/')) . '_' . time() : basename(rtrim($value, '/'));
$ext = end(explode('.', $value));
if (in_array($ext, $valid_extensions)) {    
$uploadfil = new SplFileObject($value, 'rb');
$file = new SplTempFileObject();
while (!$uploadfil->eof()) {
$file->fwrite($uploadfil->fgets());
}
$file->rewind();  
$inctream = '';
foreach ($file as $line) {
$inctream .= $line;
}
$body .= PHP_EOL . '--' . $mb . PHP_EOL . 'Content-Type: application/octet-stream; name="' . $newfile . '"' . PHP_EOL . 'Content-Disposition: attachment;' . PHP_EOL . ' filename="' . $newfile . '"' . PHP_EOL . 'Content-Transfer-Encoding: base64' . PHP_EOL . PHP_EOL . chunk_split(base64_encode($inctream));
unset($inctream);
} else {
echo '<div>Запрещенный формат файла</div>';    
}    
}
} 
}
}

if (isset($_inc['uplfile'])) {
foreach ($_inc['uplfile']['error'] as $key => $error) {
if ($error == UPLOAD_ERR_OK) {
$body .= PHP_EOL . '--' . $mb . PHP_EOL . 'Content-Type: application/octet-stream; name="' . $_inc['uplfile']['name'][$key] . '"' . PHP_EOL . 'Content-Disposition: attachment;' . PHP_EOL . ' filename="' . $_inc['uplfile']['name'][$key] . '"' . PHP_EOL . 'Content-Transfer-Encoding: base64' . PHP_EOL . PHP_EOL . chunk_split(base64_encode(file_get_contents($_inc['uplfile']['tmp_name'][$key])));
}
}
$body .= '--' . $mb . '--';    
}

mail_utf8($to, $subject, $body, $header); 
echo '<div>Ваше сообщение будет рассмотрено в ближайшие сроки</div>';
unset($_SESSION['code']);
}
    
} else {
echo print_form();    
}

// func //

function mail_utf8($to, $subject = '(No subject)', $message = '', $header = ''){ 
$header_ = 'MIME-Version: 1.0' . PHP_EOL;
mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $message, $header_ . $header); 
}

function getip() {
if ($_SERVER['HTTP_CLIENT_IP'] && strcasecmp($_SERVER['HTTP_CLIENT_IP'], 'unknown'))
$ip = $_SERVER['HTTP_CLIENT_IP'];
elseif ($_SERVER['HTTP_X_FORWARDED_FOR'] && strcasecmp($_SERVER['HTTP_X_FORWARDED_FOR'], 'unknown'))
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
elseif ($_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown'))
$ip = $_SERVER['REMOTE_ADDR'];
else
$ip = 'unknown';
return $ip;
}

function getua() {
return preg_match("/Opera Mini/i", $_SERVER['HTTP_USER_AGENT']) && !empty($_SERVER['HTTP_X_OPERAMINI_PHONE_UA']) ? $_SERVER['HTTP_USER_AGENT'] . ' (' . $_SERVER['HTTP_X_OPERAMINI_PHONE_UA'] . ')' : $_SERVER['HTTP_USER_AGENT'];
}

function url_file_exists($furl) {
$headers = get_headers($furl, 1);
if (preg_match('|200|', $headers[0])) {
return array('type' => $headers['Content-Type'], 'size' => $headers['Content-Length']);
} else {
return false;
}
}

function print_form () {
global $nameid, $email, $mess, $err, $x, $ux, $mail_arr;

$err[] = 0;
echo src_err($err);

echo '<div><form enctype="multipart/form-data" action="' . $_SERVER['PHP_SELF'] . '" method="post">Ваше имя<br/><input type="text" name="nameid" value="' . $nameid . '" size="20"/><br/>Ваш e-mail<br/><input type="text" name="email" value="' . $email . '" size="35"/><br/>Ваше сообщение<br/><textarea name="mess" rows="5" cols="30">' . $mess . '</textarea><br/>';
echo '<select name="to_mail">';
echo '<option value="0" selected="selected">Выбор адресата</option>';    
foreach ($mail_arr as $adm_name => $adm_mail) {
echo '<option value="' . $adm_name . '">' . $adm_name . '</option>';    
}
echo '</select><br/><br/>';

for ($i=0;$i<$x;$i++) {
echo '<input name="uplfile[]" type="file" /><br/>' . PHP_EOL;
}

for ($ui=0;$ui<$ux;$ui++) {
echo '<input name="importinc[]" type="text" value="http://" /><br/>' . PHP_EOL;
}

echo '<br/>&nbsp;<img src="../captcha.php?r=' . rand(1000, 9999) . '" border="1" alt="captcha.jpg" /><br/><input type="text" size="5" name="capcha"/><br/>';
echo 'Количество полей вложений<br/><a href="' . $_SERVER['PHP_SELF'] . '?countinc=1&amp;importinc=' . $ux . '">1</a> | <a href="' . $_SERVER['PHP_SELF'] . '?countinc=5&amp;importinc=' . $ux . '">5</a> | <a href="' . $_SERVER['PHP_SELF'] . '?countinc=10&amp;importinc=' . $ux . '">10</a><br/>';
echo 'Количество полей импорта<br/><a href="' . $_SERVER['PHP_SELF'] . '?countinc=' . $x . '&amp;importinc=1">1</a> | <a href="' . $_SERVER['PHP_SELF'] . '?countinc=' . $x . '&amp;importinc=5">5</a> | <a href="' . $_SERVER['PHP_SELF'] . '?countinc=' . $x . '&amp;importinc=10">10</a><br/>';
echo '<input type="submit" name="submit" value="Отправить"/></form></div>';
}

function src_err ($err_nums) {
$err_arr = array(
'Указывайте реальный ящик для ответа',
'Не указано имя !!!', // 1
'В имени менее 3 или более 20 символов',
'Не указан e-mail !!!', // 3
'В адресе e-mail менее 6 или более 35 символов',
'Неверный формат e-mail', // 5
'Сообщение не написано !!!',
'Сообщение должно быть не короче 30 и не более 2000 символов', // 7
'Введите текст с картинки', // capcha
'Не указан адресат'
);

$error = '<div class="alarm">';
foreach ($err_nums as $src) {
$error .= '<div>' . $err_arr[$src] . '</div>';
}
$error .= '</div>';
return $error;
}
include($path.'inc/down.php');
?>
