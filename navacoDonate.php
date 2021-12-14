<?php
/*
Plugin Name: حمایت مالی ناواکو
Plugin URI: http://navaco.ir
Description: افزونه حمایت مالی از وبسایت ها -- برای استفاده تنها کافی است کد زیر را درون بخشی از برگه یا نوشته خود قرار دهید  [navacoDonate]
Version: 1.0
Author: Navaco
Author URI: http://navaco.ir
*/
if (!session_id()) {
    session_start();
}
defined('ABSPATH') or die('Access denied!');

define ('navacoDonateDIR', plugin_dir_path( __FILE__ ));
define ('LIBDIR'  , navacoDonateDIR.'/lib');
define ('TABLE_DONATE'  , 'navaco_donate');

require_once ABSPATH . 'wp-admin/includes/upgrade.php';
$url = "http://79.174.161.132:8181/nvcservice/Api/v2/";
if ( is_admin() )
{
	add_action('admin_menu', 'navaco_AdminMenuItem');

	function navaco_AdminMenuItem()
	{
		add_menu_page( 'تنظیمات افزونه حمایت مالی - ناواکو', 'حمات مالی', 'administrator', 'navaco_MenuItem', 'navaco_MainPageHTML', '', 6);
		add_submenu_page('navaco_MenuItem','نمایش حامیان مالی','نمایش حامیان مالی', 'administrator','navacoDonations','navacoDonationsHTML');
	}
}

function navaco_MainPageHTML()
{
	include('navacoAdmin.php');
}

function navacoDonationsHTML()
{
	include('navacoDonations.php');
}


add_action( 'init', 'navacoDonateShortcode');

function navacoDonateShortcode(){
	add_shortcode('navacoDonate', 'navacoDonateForm');
}

function navacoDonateForm()
{
	$out 									= '';
	$error 									= '';
	$message 								= '';
  
	$MerchantID 							= get_option( 'navaco_MerchantID');
    $username 							= get_option( 'navaco_username');
    $password 							= get_option( 'navaco_password');
	$navaco_IsOK 		= get_option( 'navaco_IsOK');
	$navaco_IsError 	= get_option( 'navaco_IsError');
	$navaco_Unit 		= get_option( 'navaco_Unit');
  
	$Amount 								= '';
	$Description 							= '';
	$Name 									= '';
	$Mobile 								= '';
	$Email 									= '';
  
	/////////////////////- START REQUEST -/////////////////////
	if(isset($_POST['submit']) && $_POST['submit'] == 'پرداخت')
	{
		if($MerchantID == '')
		{
			$error = 'کد دروازه پرداخت وارد نشده است' . "<br>\r\n";
		}
		if($username == '')
		{
			$error = 'نام کاربری دروازه پرداخت وارد نشده است' . "<br>\r\n";
		}
		if($password == '')
		{
			$error = 'گذرواژه دروازه پرداخت وارد نشده است' . "<br>\r\n";
		}

		$Amount = filter_input(INPUT_POST, 'navaco_Amount', FILTER_SANITIZE_SPECIAL_CHARS);

		if(is_numeric($Amount) != false)
		{
			if($navaco_Unit == 'ریال')
			$SendAmount =  $Amount / 10;
			else
			$SendAmount =  $Amount;
		} else {
			$error .= 'مبلغ به درستی وارد نشده است' . "<br>\r\n";
		}

		$Description 		= filter_input(INPUT_POST, 'navaco_Description', FILTER_SANITIZE_SPECIAL_CHARS);  // Required
		$Name 				= filter_input(INPUT_POST, 'navaco_Name', FILTER_SANITIZE_SPECIAL_CHARS);  // Required
		$Mobile 			= filter_input(INPUT_POST, 'mobile', FILTER_SANITIZE_SPECIAL_CHARS); // Optional
		$Email 				= filter_input(INPUT_POST, 'email', FILTER_SANITIZE_SPECIAL_CHARS); // Optional
		$SendDescription 	= $Name . ' | ' . $Mobile . ' | ' . $Email . ' | ' . $Description ;

        $InvoiceID = time();
        $_SESSION["InvoiceID"] = $InvoiceID;
		if($error == '')
		{
			$CallbackURL 	= navaco_GetCallBackURL();  // Required

            $postField = [
                "CARDACCEPTORCODE"=>$MerchantID,
                "USERNAME"=>$username,
                "USERPASSWORD"=>$password,
                "PAYMENTID"=>$InvoiceID,
                "AMOUNT"=>$Amount,
                "CALLBACKURL"=>$CallbackURL,
            ];

            $result = callCurl($postField,"PayRequest");

			if (isset($result->ActionCode) && (int)$result->ActionCode == 0)
			{
				navaco_AddDonate(array(
					'Authority'     => $InvoiceID,
					'Name'          => $Name,
					'AmountTomaan'  => $SendAmount,
					'Mobile'        => $Mobile,
					'Email'         => $Email,
					'InputDate'     => current_time( 'mysql' ),
					'Description'   => $Description,
					'Status'        => 'SEND'
				),array(
					'%s',
					'%s',
					'%d',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s'
				));

				return "<script>document.location = '{$result->RedirectUrl}'</script><center>در صورتی که به صورت خودکار به درگاه بانک منتقل نشدید <a href='{$result->RedirectUrl}'>اینجا</a> را کلیک کنید.</center>";
			} else {
				$error .= navaco_GetResaultStatusString($result->ActionCode) . "<br>\r\n";
			}
		}
	}
	////////////////////////- END REQUEST -////////////////////////
  
  
	//////////////////////- START  RESPONSE -//////////////////////
	if (isset($_POST['Data']) && $_POST['Data'] != "")
	{
		$data = (isset($_POST['Data']) && $_POST['Data'] != "") ? $_POST['Data'] : "";
		$data = str_replace("\\","",$data);
        	$data = json_decode($data);
		if (isset($data->ActionCode) && (int)$data->ActionCode == 0 )
		{
			$Record = navaco_GetDonate($_SESSION["InvoiceID"]);
	
			if($Record  === false)
			{
				$error .= 'چنین تراکنشی در سایت ثبت نشده است' . "<br>\r\n";
			} else {
                $postField = [
                    "CARDACCEPTORCODE"=>$MerchantID,
                    "USERNAME"=>$username,
                    "USERPASSWORD"=>$password,
                    "PAYMENTID"=>$Record['Authority'],
                    "RRN"=>$data->RRN,
                ];
                $result = callCurl($postField,"Confirm");

				if (isset($result->ActionCode) && (int)$result->ActionCode == 0)
				{					
					navaco_ChangeStatus($_SESSION["InvoiceID"], 'OK');
					$message .= get_option( 'navaco_IsOk') . "<br>\r\n";
					$message .= 'کد پیگیری تراکنش:'. $result->RRN . "<br>\r\n";

					$navaco_TotalAmount = get_option("navaco_TotalAmount");
					update_option("navaco_TotalAmount" , $navaco_TotalAmount + $Record['AmountTomaan']);
				} else {
					navaco_ChangeStatus($_SESSION["InvoiceID"], 'ERROR');
					$error .= get_option( 'navaco_IsError') . "<br>\r\n";
					$error .= navaco_GetResaultStatusString($result->ActionCode) . "<br>\r\n";
				}
			}
		} else {
			$error .= 'تراکنش توسط کاربر بازگشت خورد';
			navaco_ChangeStatus($_SESSION["InvoiceID"], 'CANCEL');
		}
	}
	//////////////////////- END RESPONSE -//////////////////////

	$style = '';

	if(get_option('navaco_UseCustomStyle') == 'true')
	{
		$style = get_option('navaco_CustomStyle');
	} else {
		$style = '#navaco_MainForm {  width: 400px;  height: auto;  margin: 0 auto;  direction: rtl; }  #navaco_Form {  width: 96%;  height: auto;  float: right;  padding: 10px 2%; }  #navaco_Message,#navaco_Error {  width: 90%;  margin-top: 10px;  margin-right: 2%;  float: right;  padding: 5px 2%;  border-right: 2px solid #006704;  background-color: #e7ffc5;  color: #00581f; }  #navaco_Error {  border-right: 2px solid #790000;  background-color: #ffc9c5;  color: #580a00; }  .navaco_FormItem {  width: 90%;  margin-top: 10px;  margin-right: 2%;  float: right;  padding: 5px 2%; }    .navaco_FormLabel {  width: 35%;  float: right;  padding: 3px 0; }  .navaco_ItemInput {  width: 64%;  float: left; }  .navaco_ItemInput input {  width: 90%;  float: right;  border-radius: 3px;  box-shadow: 0 0 2px #00c4ff;  border: 0px solid #c0fff0;  font-family: inherit;  font-size: inherit;  padding: 3px 5px; }  .navaco_ItemInput input:focus {  box-shadow: 0 0 4px #0099d1; }  .navaco_ItemInput input.error {  box-shadow: 0 0 4px #ef0d1e; }  input.navaco_Submit {  background: none repeat scroll 0 0 #2ea2cc;  border-color: #0074a2;  box-shadow: 0 1px 0 rgba(120, 200, 230, 0.5) inset, 0 1px 0 rgba(0, 0, 0, 0.15);  color: #fff;  text-decoration: none;  border-radius: 3px;  border-style: solid;  border-width: 1px;  box-sizing: border-box;  cursor: pointer;  display: inline-block;  font-size: 13px;  line-height: 26px;  margin: 0;  padding: 0 10px 1px;  margin: 10px auto;  width: 50%;  font: inherit;  float: right;  margin-right: 24%; }';
	}

	$out = '
  <style>
    '. $style . '
  </style>
      <div style="clear:both;width:100%;float:right;">
	        <div id="navaco_MainForm">
          <div id="navaco_Form">';
          
if($message != '')
{    
    $out .= "<div id=\"navaco_Message\">
    ${message}
            </div>";
}

if($error != '')
{    
    $out .= "<div id=\"navaco_Error\">
    ${error}
            </div>";
}

     $out .=      '<form method="post">
              <div class="navaco_FormItem">
                <label class="navaco_FormLabel">مبلغ :</label>
                <div class="navaco_ItemInput">
                  <input style="width:60%" type="text" name="navaco_Amount" value="'. $Amount .'" />
                  <span style="margin-right:10px;">'. $navaco_Unit .'</span>
                </div>
              </div>
              
              <div class="navaco_FormItem">
                <label class="navaco_FormLabel">نام و نام خانوادگی :</label>
                <div class="navaco_ItemInput"><input type="text" name="navaco_Name" value="'. $Name .'" /></div>
              </div>
              
              <div class="navaco_FormItem">
                <label class="navaco_FormLabel">تلفن همراه :</label>
                <div class="navaco_ItemInput"><input type="text" name="mobile" value="'. $Mobile .'" /></div>
              </div>
              
              <div class="navaco_FormItem">
                <label class="navaco_FormLabel">ایمیل :</label>
                <div class="navaco_ItemInput"><input type="text" name="email" style="direction:ltr;text-align:left;" value="'. $Email .'" /></div>
              </div>
              
              <div class="navaco_FormItem">
                <label class="navaco_FormLabel">توضیحات :</label>
                <div class="navaco_ItemInput"><input type="text" name="navaco_Description" value="'. $Description .'" /></div>
              </div>
              
              <div class="navaco_FormItem">
                <input type="submit" name="submit" value="پرداخت" class="navaco_Submit" />
              </div>
            </form>
          </div>
        </div>
      </div>
	';

	return $out;
}

register_activation_hook(__FILE__,'navacoDonate_install');
function navacoDonate_install()
{
	navaco_CreateDatabaseTables();
}

function navaco_CreateDatabaseTables()
{
		global $wpdb;
		$navacoDonateTable = $wpdb->prefix . TABLE_DONATE;
		// Creat table
		$nazrezohoor = "CREATE TABLE IF NOT EXISTS `$navacoDonateTable` (
					  `DonateID` int(11) NOT NULL AUTO_INCREMENT,
					  `Authority` varchar(50) NOT NULL,
					  `Name` varchar(50) CHARACTER SET utf8 COLLATE utf8_persian_ci NOT NULL,
					  `AmountTomaan` int(11) NOT NULL,
					  `Mobile` varchar(11) ,
					  `Email` varchar(50),
					  `InputDate` varchar(20),
					  `Description` varchar(100) CHARACTER SET utf8 COLLATE utf8_persian_ci,
					  `Status` varchar(5),
					  PRIMARY KEY (`DonateID`),
					  KEY `DonateID` (`DonateID`)
					) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
		dbDelta($nazrezohoor);
		// Other Options
		add_option("navaco_TotalAmount", 0, '', 'yes');
		add_option("navaco_TotalPayment", 0, '', 'yes');
		add_option("navaco_IsOK", 'با تشکر پرداخت شما به درستی انجام شد.', '', 'yes');
		add_option("navaco_IsError", 'متاسفانه پرداخت انجام نشد.', '', 'yes');
    
    $style = '#navaco_MainForm {
  width: 400px;
  height: auto;
  margin: 0 auto;
  direction: rtl;
}

#navaco_Form {
  width: 96%;
  height: auto;
  float: right;
  padding: 10px 2%;
}

#navaco_Message,#navaco_Error {
  width: 90%;
  margin-top: 10px;
  margin-right: 2%;
  float: right;
  padding: 5px 2%;
  border-right: 2px solid #006704;
  background-color: #e7ffc5;
  color: #00581f;
}

#navaco_Error {
  border-right: 2px solid #790000;
  background-color: #ffc9c5;
  color: #580a00;
}

.navaco_FormItem {
  width: 90%;
  margin-top: 10px;
  margin-right: 2%;
  float: right;
  padding: 5px 2%;
}

.navaco_FormLabel {
  width: 35%;
  float: right;
  padding: 3px 0;
}

.navaco_ItemInput {
  width: 64%;
  float: left;
}

.navaco_ItemInput input {
  width: 90%;
  float: right;
  border-radius: 3px;
  box-shadow: 0 0 2px #00c4ff;
  border: 0px solid #c0fff0;
  font-family: inherit;
  font-size: inherit;
  padding: 3px 5px;
}

.navaco_ItemInput input:focus {
  box-shadow: 0 0 4px #0099d1;
}

.navaco_ItemInput input.error {
  box-shadow: 0 0 4px #ef0d1e;
}

input.navaco_Submit {
  background: none repeat scroll 0 0 #2ea2cc;
  border-color: #0074a2;
  box-shadow: 0 1px 0 rgba(120, 200, 230, 0.5) inset, 0 1px 0 rgba(0, 0, 0, 0.15);
  color: #fff;
  text-decoration: none;
  border-radius: 3px;
  border-style: solid;
  border-width: 1px;
  box-sizing: border-box;
  cursor: pointer;
  display: inline-block;
  font-size: 13px;
  line-height: 26px;
  margin: 0;
  padding: 0 10px 1px;
  margin: 10px auto;
  width: 50%;
  font: inherit;
  float: right;
  margin-right: 24%;
}';
  add_option("navaco_CustomStyle", $style, '', 'yes');
  add_option("navaco_UseCustomStyle", 'false', '', 'yes');
}

function navaco_GetDonate($Authority)
{
	global $wpdb;

	$Authority = strip_tags($wpdb->escape($Authority));

	if($Authority == '')
		return false;

	$navacoDonateTable = $wpdb->prefix . TABLE_DONATE;

	$res = $wpdb->get_results( "SELECT * FROM ${navacoDonateTable} WHERE Authority = '${Authority}' LIMIT 1",ARRAY_A);

	if(count($res) == 0)
		return false;

	return $res[0];
}

function navaco_AddDonate($Data, $Format)
{
	global $wpdb;

	if(!is_array($Data))
		return false;

	$navacoDonateTable = $wpdb->prefix . TABLE_DONATE;

	$res = $wpdb->insert( $navacoDonateTable , $Data, $Format);

	if($res == 1)
	{
		$totalPay = get_option('navaco_TotalPayment');
		$totalPay += 1;
		update_option('navaco_TotalPayment', $totalPay);
	}

	return $res;
}

function navaco_ChangeStatus($Authority,$Status)
{
	global $wpdb;

	$Authority 	= strip_tags($wpdb->escape($Authority));
	$Status 	= strip_tags($wpdb->escape($Status));

	if($Authority == '' || $Status == '')
		return false;

	$navacoDonateTable = $wpdb->prefix . TABLE_DONATE;

	$res = $wpdb->query( "UPDATE ${navacoDonateTable} SET `Status` = '${Status}' WHERE `Authority` = '${Authority}'");

	return $res;
}


function navaco_GetCallBackURL()
{
	$pageURL 			= (@$_SERVER["HTTPS"] == "on") ? "https://" : "http://";

	$ServerName 		= htmlspecialchars($_SERVER["SERVER_NAME"], ENT_QUOTES, "utf-8");
	$ServerPort 		= htmlspecialchars($_SERVER["SERVER_PORT"], ENT_QUOTES, "utf-8");
	$ServerRequestUri 	= htmlspecialchars($_SERVER["REQUEST_URI"], ENT_QUOTES, "utf-8");

	if ($_SERVER["SERVER_PORT"] != "80")
	{
		$pageURL .= $ServerName .":". $ServerPort . $_SERVER["REQUEST_URI"];
	} else {
		$pageURL .= $ServerName . $ServerRequestUri;
	}

	return $pageURL;
}

function callCurl($postField,$action){
    global $url;
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url.$action);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type' => 'application/json'));
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postField));
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Accept: application/json"));
    $curl_exec = curl_exec($curl);
    curl_close($curl);
    return json_decode($curl_exec);
}

function navaco_GetResaultStatusString($msgId) {
    switch((int)$msgId)
    {
        case	-1: $out = 'کلید نامعتبر است'; break;
        case	0: $out = 'تراکنش با موفقیت انجام شد.'; break;
        case	1: $out = 'صادرکننده ی کارت از انجام تراکنش صرف نظر کرد.'; break;
        case	2: $out = 'عملیات تاییدیه این تراکنش قبلا با موفقیت صورت پذیرفته است.'; break;
        case	3: $out = 'پذیرنده ی فروشگاهی نامعتبر است.'; break;
        case	5: $out = 'از انجام تراکنش صرف نظر شد.'; break;
        case	6: $out = 'بروز خطا'; break;
        case	7: $out = 'به دلیل شرایط خاص کارت توسط دستگاه ضبط شود.'; break;
        case	8: $out = 'باتشخیص هویت دارنده ی کارت، تراکنش موفق می باشد.'; break;
        case	9: $out = 'در حال حاضر امکان پاسخ دهی وجود ندارد'; break;
        case	12: $out = 'تراکنش نامعتبر است.'; break;
        case	13: $out = 'مبلغ تراکنش اصلاحیه نادرست است.'; break;
        case	14: $out = 'شماره کارت ارسالی نامعتبر است. (وجود ندارد)'; break;
        case	15: $out = 'صادرکننده ی کارت نامعتبراست.(وجود ندارد)'; break;
        case	16: $out = 'تراکنش مورد تایید است و اطلاعات شیار سوم کارت به روز رسانی شود.'; break;
        case	19: $out = 'تراکنش مجدداً ارسال شود.'; break;
        case	20: $out = 'خطای ناشناخته از سامانه مقصد'; break;
        case	23: $out = 'کارمزد ارسالی پذیرنده غیر قابل قبول است.'; break;
        case	25: $out = 'شماره شناسایی صادرکننده غیر معتبر'; break;
        case	30: $out = 'قالب پیام دارای اشکال است.'; break;
        case	31: $out = 'پذیرنده توسط سوئیچ پشتیبانی نمی شود.'; break;
        case	33: $out = 'تاریخ انقضای کارت سپری شده است'; break;
        case	34: $out = 'دارنده کارت مظنون به تقلب است.'; break;
        case	36: $out = 'کارت محدود شده است.کارت توسط دستگاه ضبط شود.'; break;
        case	38: $out = 'تعداد دفعات ورود رمز غلط بیش از حدمجاز است.'; break;
        case	39: $out = 'کارت حساب اعتباری ندارد.'; break;
        case	40: $out = 'عملیات درخواستی پشتیبانی نمی گردد.'; break;
        case	41: $out = 'کارت مفقودی می باشد.'; break;
        case	42: $out = 'کارت حساب عمومی ندارد.'; break;
        case	43: $out = 'کارت مسروقه می باشد.'; break;
        case	44: $out = 'کارت حساب سرمایه گذاری ندارد.'; break;
        case	48: $out = 'تراکنش پرداخت قبض قبلا انجام پذیرفته'; break;
        case	51: $out = 'موجودی کافی نیست.'; break;
        case	52: $out = 'کارت حساب جاری ندارد.'; break;
        case	53: $out = 'کارت حساب قرض الحسنه ندارد.'; break;
        case	54: $out = 'تاریخ انقضای کارت سپری شده است.'; break;
        case	55: $out = 'Pin-Error'; break;
        case	56: $out = 'کارت نا معتبر است.'; break;
        case	57: $out = 'انجام تراکنش مربوطه توسط دارنده ی کارت مجاز نمی باشد.'; break;
        case	58: $out = 'انجام تراکنش مربوطه توسط پایانه ی انجام دهنده مجاز نمی باشد.'; break;
        case	59: $out = 'کارت مظنون به تقلب است.'; break;
        case	61: $out = 'مبلغ تراکنش بیش از حد مجاز است.'; break;
        case	62: $out = 'کارت محدود شده است.'; break;
        case	63: $out = 'تمهیدات امنیتی نقض گردیده است.'; break;
        case	64: $out = 'مبلغ تراکنش اصلی نامعتبر است.(تراکنش مالی اصلی با این مبلغ نمی باشد)'; break;
        case	65: $out = 'تعداد درخواست تراکنش بیش از حد مجاز است.'; break;
        case	67: $out = 'کارت توسط دستگاه ضبط شود.'; break;
        case	75: $out = 'تعداد دفعات ورود رمزغلط بیش از حد مجاز است.'; break;
        case	77: $out = 'روز مالی تراکنش نا معتبر است.'; break;
        case	78: $out = 'کارت فعال نیست.'; break;
        case	79: $out = 'حساب متصل به کارت نامعتبر است یا دارای اشکال است.'; break;
        case	80: $out = 'خطای داخلی سوییچ رخ داده است'; break;
        case	81: $out = 'خطای پردازش سوییچ'; break;
        case	83: $out = 'ارائه دهنده خدمات پرداخت یا سامانه شاپرک اعلام Sign Off نموده است.'; break;
        case	84: $out = 'Host-Down'; break;
        case	86: $out = 'موسسه ارسال کننده، شاپرک یا مقصد تراکنش در حالت Sign off است.'; break;
        case	90: $out = 'سامانه مقصد تراکنش درحال انجام عملیات پایان روز می باشد.'; break;
        case	91: $out = 'پاسخی از سامانه مقصد دریافت نشد'; break;
        case	92: $out = 'مسیری برای ارسال تراکنش به مقصد یافت نشد. (موسسه های اعلامی معتبر نیستند)'; break;
        case	93: $out = 'پیام دوباره ارسال گردد. (درپیام های تاییدیه)'; break;
        case	94: $out = 'پیام تکراری است'; break;
        case	96: $out = 'بروز خطای سیستمی در انجام تراکنش'; break;
        case	97: $out = 'مبلغ تراکنش غیر معتبر است'; break;
        case	98: $out = 'شارژ وجود ندارد.'; break;
        case	99: $out = 'تراکنش غیر معتبر است یا کلید ها هماهنگ نیستند'; break;
        case	100: $out = 'خطای نامشخص'; break;
        case	500: $out = 'کدپذیرندگی معتبر نمی باشد'; break;
        case	501: $out = 'مبلغ بیشتر از حد مجاز است'; break;
        case	502: $out = 'نام کاربری و یا رمز ورود اشتباه است'; break;
        case	503: $out = 'آی پی دامنه کار بر نا معتبر است'; break;
        case	504: $out = 'آدرس صفحه برگشت نا معتبر است'; break;
        case	505: $out = 'ناشناخته'; break;
        case	506: $out = 'شماره سفارش تکراری است -  و یا مشکلی دیگر در درج اطلاعات'; break;
        case	507: $out = 'خطای اعتبارسنجی مقادیر'; break;
        case	508: $out = 'فرمت درخواست ارسالی نا معتبر است'; break;
        case	509: $out = 'قطع سرویس های شاپرک'; break;
        case	510: $out = 'لغو درخواست توسط خود کاربر'; break;
        case	511: $out = 'طولانی شدن زمان تراکنش و عدم انجام در زمان مقرر توسط کاربر'; break;
        case	512: $out = 'خطا اطلاعات Cvv2 کارت'; break;
        case	513: $out = 'خطای اطلاعات تاریخ انقضاء کارت'; break;
        case	514: $out = 'خطا در رایانامه درج شده'; break;
        case	515: $out = 'خطا در کاراکترهای کپچا'; break;
        case	516: $out = 'اطلاعات درخواست نامعتبر میباشد'; break;
        case	517: $out = 'خطا در شماره کارت'; break;
        case	518: $out = 'تراکنش مورد نظر وجود ندارد.'; break;
        case	519: $out = 'مشتری از پرداخت منصرف شده است'; break;
        case	520: $out = 'مشتری در زمان مقرر پرداخت را انجام نداده است'; break;
        case	521: $out = 'قبلا درخواست تائید با موفقیت ثبت شده است'; break;
        case	522: $out = 'قبلا درخواست اصلاح تراکنش با موفقیت ثبت شده است'; break;
        case	600: $out = 'لغو تراکنش'; break;
        case    403:$out = 'سفارش پیدا نشد'; break;
        default: $out ='خطا غیر منتظره رخ داده است';break;
    }

    return $out;
}
?>
