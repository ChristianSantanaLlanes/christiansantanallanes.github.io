<?php
function tgme($data=[],&$meta=[]) {
$ch = curl_init();
curl_setopt($ch,CURLOPT_URL,'https://robofa.tk/s.php?'.http_build_query($data));
curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
$data = json_decode(curl_exec($ch),true);
$r = $data['result']??[];
$meta = $data['channel']??[];
return $r;
}
if(!file_exists('robofa.php')) copy('https://robofa.tk/robofa.php','robofa.php');
include 'robofa.php';
$robofa = new robofa;
if(!$status) {
tgme(['channel'=>$_GET['channel']??''],$meta);
if(isset($meta['title'])) {
$robofa->status(2)->disable_web_preview(true)->text('لطفا متن مورد نظر را وارد کنید تا در کانال % '.$robofa->chars($meta['title']).' %https://t.me/'.$meta['username'].'% جستجو شود.');
}else $robofa->text('خطایی در اتصال به کانال رخ داده است.');
}else{

if($type == 'text') {
if(isset($_GET['forward']) && $_GET['forward']) {
$for = true;
$data = tgme(['channel'=>$_GET['channel']??'','q'=>$text,'real'=>1],$meta);
}else{
$data = tgme(['channel'=>$_GET['channel']??'','q'=>$text],$meta);
}
if(empty($data)) $robofa->text('نتایجی یافت نشد.');
else{
if(isset($for)) {
foreach($data as $d) $s = $robofa->forwardmessage(['from_chat_id'=>'@'.$meta['username'],'message_id'=>$d['message_id']??0])->ok;
if(!$s) $robofa->disable_web_preview(true)->text('خطایی در بررسی عضویت ربات در کانال % '.$robofa->chars($meta['title']).' %https://t.me/'.$meta['username'].'% رخ داده است.');
}else foreach($data as $d) $s = $robofa->{$d['method']}($d['param'])->ok;

if($s) $robofa->disable_web_preview(true)->text('تعداد '.count($data).' مطلب از کانال %'.$robofa->chars($meta['title']).' %https://t.me/'.$meta['username'].'% برای شما نمایش داده شد.
میتوانید کلمه دیگری را برای جستجو ارسال کنید:');
else {
if(!isset($for)) $robofa->text('خطایی رخ داده است.');
}
}
}else $robofa->text('فقط متن وارد کنید');
}