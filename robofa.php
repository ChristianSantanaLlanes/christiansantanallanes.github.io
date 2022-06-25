<?php


class robofa {
private $robofa_ip = '88.198.230.56';
private $version = '3.0.3';
private $robofaurl = 'https://robofa.tk';
private $setvarr = false;
private $token = false;
function __construct($token=false,$shotdown=true,$setvar=true) { 
$this->cid = 0;
$this->data = ['contents'=>[]];
$this->setvarr = $setvar;
if(!$token) {
$this->input = json_decode(file_get_contents('php://input'));
if($this->input && $this->verify($this->input)) {
if($setvar) $this->setvar();
if($shotdown) {
$robofa = $this;
register_shutdown_function(function () use ($robofa) {
$robofa->end();
});
}
if($this->version != $this->input->php_version) {
unlink(__FILE__);
copy($this->robofaurl.'/robofa.php',__FILE__);
}}else die('<html>
<head>
<title>RoboFaBot</title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
<p style="color:red;text-align:center;font-size:20px">
To use this web service in your bot to manage -》 Buttons - New Button -》 Connect to the website and enter the same URL.
<br>
برای استفاده از این وب سرویس در ربات خود به مدیریت -》دکمه ها -》دکمه جدید -》اتصال به وبسایت و آدرس همین URL را وارد کنید.
</p>
</body>
</html>');
}else{
$this->input = (object)[];
$this->input->dbtoken = $token;
$this->token = $token;
}
return true;
}

function __call($method,$args) {
if(strtolower(mb_substr($method,0,3)) == 'get') {
$name = mb_substr($method,3);
if(isset($this->input->$name)) return $this->input->$name;
}
elseif(strtolower(mb_substr($method,0,2)) == 'is') return $this->input->type == strtolower(mb_substr($method,2));

return call_user_func_array([$this,'bot'], array_merge([$method],$args));
}

function __get($name) {
if(isset($this->input->$name)) return $this->input->$name;
}
function start($file) {

$robofa = $this;
if(file_exists($file)) {
if (!file_exists('bot.lock')) touch('bot.lock');

$lock = fopen('bot.lock', 'r+');

$try = 1;
$locked = false;
while (!$locked) {
    $locked = flock($lock, LOCK_EX | LOCK_NB);
    if (!$locked) {
        $this->closeConnection();

        if ($try++ >= 30) {
            exit;
        }
        sleep(1);
    }
}

register_shutdown_function(function($lock) {
    $a = fsockopen((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 'tls' : 'tcp').'://'.$_SERVER['SERVER_NAME'], $_SERVER['SERVER_PORT']);
    fwrite($a, $_SERVER['REQUEST_METHOD'].' '.$_SERVER['REQUEST_URI'].' '.$_SERVER['SERVER_PROTOCOL']."\r\n".'Host: '.$_SERVER['SERVER_NAME']."\r\n\r\n");
    flock($lock, LOCK_UN);
    fclose($lock);
},$lock);
$this->closeConnection();
$running = true;
while($running) {
$ups = $this->rdb('getupdates');
if(!$ups) $ups = [];
foreach($ups as $up) {
$this->input = $up;
if($this->setvarr) {
$up2 = (array)$this->input;
foreach($up2 as $k=>$v) $GLOBALS[$k] = $v;
extract($up2);
unset($up2);
}
if($this->version != $this->input->php_version) {
unlink(__FILE__);
copy($this->robofaurl.'/robofa.php',__FILE__);
}
include $file;
$this->end();
}
}
}else throw new Exception('Can\'t find file.',404);
}
private function closeConnection($message = 'OK!') {
    if (php_sapi_name() === 'cli' || isset($GLOBALS['exited'])) {
        return;
    }
    @ob_end_clean();
    header('Connection: close');
    ignore_user_abort(true);
    ob_start();
    echo '<html><body><h1>'.$message.'</h1></body</html>';
    $size = ob_get_length();
    header("Content-Length: $size");
    header('Content-Type: text/html');
    ob_end_flush();
    flush();
    $GLOBALS['exited'] = true;
}
private function setvar() {
foreach((array)$this->input as $k=>$v) $GLOBALS[$k] = $v;
}

private function set($key,$value) {
$this->data['contents'][$this->cid][$key] = $value;
return $this;
}

private function ot($type,...$vls) {
$tx = $type.':';
foreach($vls as $vl) {
$tx .= urlencode($vl).'&';
}
return $this->content($tx);
}

function content($d) {
if(isset($this->payment)) {
$d = sprintf($this->payment,base64_encode($d));
unset($this->payment);
}
$this->data['contents'][$this->cid]['content'] = $d;
$this->cid = $this->cid+1;
return $this;
}
function payment($amount,$currency,$order_id=0,$data='',$to=0) {
$this->payment = 'payment:'.urlencode($amount).'&'.urlencode($currency).'&%s&'.urlencode($order_id).'&'.urlencode($data).'&'.urlencode($to);
return $this;
}
function verifypayment($id) {
if(!isset($this->data['verifypayment'])) $this->data['verifypayment'] = [];
$this->data['verifypayment'][] = $id;
return $this;
}
function edit($id='last_message_id') {
$this->set('edit',true)->set('message_id',$id);
return $this;
}

function reply($id='last_message_id') {
return $this->set('reply',$id); 
}

function disable_web_preview($x) {
return $this->set('disable_web_preview',$x); 
}

function is_personal($personal) {
$this->data['is_personal'] = $personal; 
return $this;
}

function next_offset($offset) {
$this->data['next_offset'] = $offset; 
return $this;
}

function switch_pm_text($pm) {
$this->data['switch_pm_text'] = $pm; 
return $this;
}

function switch_pm_parameter($parameter) {
$this->data['switch_pm_parameter'] = $parameter; 
return $this;
}

function cache_time($time) {
$this->data['cache_time'] = $time;
return $this;
}

function inline_data($data=[]) {
return $this->set('inline_data',$data); 
}

function keyboard($k=[]) {
return $this->set('keyboard',$k); 
}

function inline_keyboard($k=[]) {
return $this->set('inline_keyboard',$k); 
}

function step($step) {
$this->data['status'] = $step;
return $this;
}

function status($step) {
$this->data['status'] = $step;
return $this;
}

function text($message) {
return $this->content('text:'.$message);
}

function photo($url,$message='') {
return $this->content('photo:'.urlencode($url).'&'.$message);
}

function audio($url,$message='') {
return $this->content('audio:'.urlencode($url).'&'.$message); }

function animation($url,$message='') {
return $this->content('animation:'.urlencode($url).'&'.$message);
}

function document($url,$message='') {
return $this->content('document:'.urlencode($url).'&'.$message); 
}

function video($url,$message='') {
return $this->content('video:'.urlencode($url).'&'.$message); 
}

function voice($url,$message='') {
return $this->content('voice:'.urlencode($url).'&'.$message);
}

function videonote($url,$message='') {
return $this->content('video_note:'.urlencode($url).'&'.$message); 
}

function sticker($url,$message='') {
return $this->content('sticker:'.urlencode($url).'&'.$message); 
}
function venue($latitude, $longitude, $title, $address, $foursquare_id="", $foursquare_type="", $google_place_id="", $google_place_type="") {
return $this->ot('venue',$latitude, $longitude, $title, $address, $foursquare_id, $foursquare_type, $google_place_id, $google_place_type);
}

function location($latitude, $longitude, $live_period=0, $horizontal_accuracy=0, $heading=0, $proximity_alert_radius=0) {
return $this->ot('location',$latitude, $longitude, $live_period, $horizontal_accuracy, $heading, $proximity_alert_radius);
}

function contact($phone_number, $first_name, $last_name="", $vcard="") {
return $this->ot('contact',$phone_number, $first_name, $last_name, $vcard);
}

function poll($question, $options, $is_anonymous=true, $type='regular', $allows_multiple_answers=false, $correct_option_id=0, $explanation="", $open_period="", $close_date=0, $is_closed=false) {
$options = (is_array($options) || is_object($options))?json_encode($options):$options;
return $this->ot('poll',$question, $options, $is_anonymous, $type, $allows_multiple_answers, $correct_option_id, $explanation, $open_period, $close_date, $is_closed);
}

function dice($emoji) {
return $this->ot('dice',$emoji);
}

function game($game_short_name) {
return $this->ot('game',$game_short_name);
}

function mediagroup($media) {
return $this->ot('mediagroup',json_encode($media));
}

function forward($from_chat_id,$message_id) {
return $this->ot('forward',$from_chat_id,$message_id); 
}

function copy($from_chat_id,$message_id,$newcaption=false) {
if($newcaption === false) return $this->ot('copy',$from_chat_id,$message_id); 
else return $this->ot('copy',$from_chat_id,$message_id,$newcaption); 
}

private function getuserip() {
    if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
              $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
              $_SERVER['HTTP_CLIENT_IP'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    $client  = isset($_SERVER['HTTP_CLIENT_IP'])?$_SERVER['HTTP_CLIENT_IP']:'';
    $forward = isset($_SERVER['HTTP_X_FORWARDED_FOR'])?$_SERVER['HTTP_X_FORWARDED_FOR']:'';
    $remote  = $_SERVER['REMOTE_ADDR'];
if(filter_var($client, FILTER_VALIDATE_IP)) $ip = $client; elseif(filter_var($forward, FILTER_VALIDATE_IP)) $ip = $forward; else $ip = $remote;
    return $ip;
}

private function verify($input) {
$ar =[
'update',
'status',
'text',
'text2',
'type',
'useback',
'istext',
'botid',
'back',
'inviteuser',
'invitegp',
'token',
'php_version',
'user_id',
'dbtoken',
'timezone',
'datemode',
'lang'
];
foreach($ar as $a) { if(!isset($input->$a)) return false; }
if($this->getuserip() !=  $this->robofa_ip) return false;
return true;
}

function buttoncr2($ar,$in=2) {
$ke = [];
foreach(array_chunk($ar,$in) as $c=>$k) {
foreach($k as $txt) {
if($txt) $ke[$c][] = ['text'=>$txt];
}
}
return $ke;
}

function buttoncr($text,$in=2) {
return $this->buttoncr2(explode("\n",$text),$in);
}

function getuser($key,$user_id=0) {
if(!$user_id) $user_id = $this->input->user_id;
return $this->rdb(__FUNCTION__,get_defined_vars());
}

function setuser($key,$value,$user_id=0) {
if(!$user_id) $user_id = $this->input->user_id;
return $this->rdb(__FUNCTION__,get_defined_vars());
}

function dbget($key) {
return $this->rdb(__FUNCTION__,get_defined_vars());
}

function dbset($key,$value) {
return $this->rdb(__FUNCTION__,get_defined_vars());
}
function copymessage($datas) {
if(!isset($datas['chat_id'])) $datas['chat_id'] = $this->input->chat_id;
$req = $this->bot(__FUNCTION__,$datas);
if(!$req->ok && isset($datas['from_chat_id']) && isset($datas['message_id'])) {
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,'https://robofa.tk/copy.php?channel='.$datas['from_chat_id'].'&message_id='.$datas['message_id'].'&botapi=1');
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    $res = json_decode(curl_exec($ch));
if(is_object($res) && $res->ok) {
$res = $res->result;
unset($datas['from_chat_id'],$datas['message_id']);
$a = $this->bot($res->method,array_merge($datas,(array)$res->param));
if($a->ok) return $a;
}
return $req;
}
return $req;
}
function bot($method,$datas=[]){
    $url = "https://api.telegram.org/bot".$this->input->token."/".$method;
if(!isset($datas['chat_id'])) {
$sub = strtolower(substr($method,0,4));
if($sub == 'send' || $sub == 'edit' || $sub == 'copy' || $sub == 'forw' || $sub == 'stop' || $sub == 'dele') $datas['chat_id'] = $this->input->chat_id;
}
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$datas);
    $res = curl_exec($ch);
    if(!curl_error($ch)) return json_decode($res);
}
function rdb($method,$datas=[]){
    $url = $this->robofaurl.'/rdb'.$this->input->dbtoken.'/'.$method;
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 61);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$datas);
    $res = curl_exec($ch);
    if(!curl_error($ch)) {
$json = json_decode($res);
if(!$json) return false;
if($json->ok) return $json->result;
throw new Exception($json->description, $json->error_code);
}
}
function end() {
if($this->data != ['contents'=>[]]) {
if(!$this->token) {
header("Content-type: application/json; charset=utf-8");
echo json_encode($this->data,128|256);
}else $this->rdb('replyupdate',['id'=>$this->input->updateid,'data'=>json_encode($this->data)]);
$this->data = ['contents'=>[]];
}}
function getfile_id($file) {
if(preg_match('/(photo|audio|animation|document|video|voice|video_note|sticker):(.*?)($|&)/i',$file,$mt)) return $mt[2];
}
function gettype2($file) {
if(preg_match('/(photo|audio|animation|document|video|voice|video_note|sticker):(.*?)($|&)/i',$file,$mt)) return strtolower($mt[1]);
return 'text';
}
function getlink($file) {
if(preg_match('/(photo|audio|animation|document|video|voice|video_note|sticker):(.*?)($|&)/i',$file,$mt)) {
$h = $this->robofaurl.'/getfile/'.$this->input->botid.'/'.$mt[2];
foreach (get_headers($h) as $value) {
if(preg_match('/Location: (.+)/i',$value,$mt)) return $this->robofaurl.'/getfile/'.$this->input->botid.'/'.$mt[1];
}
return '';
}}

function file() {
return $this->getlink($this->input->text);
}
function getlink_by_fileid($file_id) {
$h = $this->robofaurl.'/getfile/'.$this->input->botid.'/'.$file_id;
foreach (get_headers($h) as $value) {
if(preg_match('/Location: (.+)/i',$value,$mt)) return $this->robofaurl.'/getfile/'.$this->input->botid.'/'.$mt[1];
}
}
function answercallback($text='',$show_alert='',$url='',$cache_time='') {
$this->data['answercallback'] = array_filter(get_defined_vars());
return $this;
}
function shortlink($url) {
return file_get_contents($this->robofaurl.'/shortlink?url='.urlencode($url));
}
function chars($text) {
preg_match_all('/%(.*?)%((http|https|tg):\/\/(.*?))(%| |$|\n)/isu',$text,$href);
$ar = [];
foreach($href[0] as $c=>$t) $ar[$t] = '\\'.$t;
return htmlspecialchars(str_replace(array_keys($ar),$ar,$text));
}
}