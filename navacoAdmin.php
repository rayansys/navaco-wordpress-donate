<?php 
defined('ABSPATH') or die('Access denied!');

if ( $_POST ) {
	
	if ( isset($_POST['navaco_MerchantID']) ) {
		update_option( 'navaco_MerchantID', $_POST['navaco_MerchantID'] );
	}
	if ( isset($_POST['navaco_username']) ) {
		update_option( 'navaco_username', $_POST['navaco_username'] );
	}
	if ( isset($_POST['navaco_password']) ) {
		update_option( 'navaco_password', $_POST['navaco_password'] );
	}
	
	if ( isset($_POST['navaco_IsOK']) ) {
		update_option( 'navaco_IsOK', $_POST['navaco_IsOK'] );
	}
  
	if ( isset($_POST['navaco_IsError']) ) {
		update_option( 'navaco_IsError', $_POST['navaco_IsError'] );
	}
	
  if ( isset($_POST['navaco_Unit']) ) {
		update_option( 'navaco_Unit', $_POST['navaco_Unit'] );
	}
  
  if ( isset($_POST['navaco_UseCustomStyle']) ) {
		update_option( 'navaco_UseCustomStyle', 'true' );
    
    if ( isset($_POST['navaco_CustomStyle']) )
    {
      update_option( 'navaco_CustomStyle', strip_tags($_POST['navaco_CustomStyle']) );
    }
    
	}
  else
  {
    update_option( 'navaco_UseCustomStyle', 'false' );
  }
  
	echo '<div class="updated" id="message"><p><strong>تنظیمات ذخیره شد</strong>.</p></div>';
	
}
?>
<h2 id="add-new-user">تنظیمات افزونه حمایت مالی - ناواکو</h2>
<h2 id="add-new-user">جمع تمام پرداخت ها : <?php echo get_option("navaco_TotalAmount"); ?>  تومان</h2>
<form method="post">
  <table class="form-table">
    <tbody>
      <tr class="user-first-name-wrap">
        <th><label for="navaco_MerchantID">کد دروازه پرداخت</label></th>
        <td>
          <input type="text" class="regular-text" dir="ltr" value="<?php echo get_option( 'navaco_MerchantID'); ?>" id="navaco_MerchantID" name="navaco_MerchantID">
          <p class="description indicator-hint"></p>
        </td>
      </tr>
      <tr class="user-first-name-wrap">
        <th><label for="navaco_username">نام کاربری دروازه پرداخت</label></th>
        <td>
          <input type="text" class="regular-text" dir="ltr" value="<?php echo get_option( 'navaco_username'); ?>" id="navaco_username" name="navaco_username">
          <p class="description indicator-hint"></p>
        </td>
      </tr>
      <tr class="user-first-name-wrap">
        <th><label for="navaco_password">گذرواژه دروازه پرداخت</label></th>
        <td>
          <input type="text" class="regular-text" dir="ltr" value="<?php echo get_option( 'navaco_password'); ?>" id="navaco_password" name="navaco_password">
          <p class="description indicator-hint"></p>
        </td>
      </tr>
      <tr>
        <th><label for="navaco_IsOK">پرداخت صحیح</label></th>
        <td><input type="text" class="regular-text" value="<?php echo get_option( 'navaco_IsOK'); ?>" id="navaco_IsOK" name="navaco_IsOK"></td>
      </tr>
      <tr>
        <th><label for="navaco_IsError">خطا در پرداخت</label></th>
        <td><input type="text" class="regular-text" value="<?php echo get_option( 'navaco_IsError'); ?>" id="navaco_IsError" name="navaco_IsError"></td>
      </tr>
      
      <tr class="user-display-name-wrap">
        <th><label for="navaco_Unit">واحد پول</label></th>
        <td>
          <?php $navaco_Unit = get_option( 'navaco_Unit'); ?>
          <select id="navaco_Unit" name="navaco_Unit">
            <option <?php if($navaco_Unit == 'تومان' ) echo 'selected="selected"' ?>>تومان</option>
            <option <?php if($navaco_Unit == 'ریال' ) echo 'selected="selected"' ?>>ریال</option>
          </select>
        </td>
      </tr>
      
      <tr class="user-display-name-wrap">
        <th>استفاده از استایل سفارشی</th>
        <td>
          <?php $navaco_UseCustomStyle = get_option('navaco_UseCustomStyle') == 'true' ? 'checked="checked"' : ''; ?>
          <input type="checkbox" name="navaco_UseCustomStyle" id="navaco_UseCustomStyle" value="true" <?php echo $navaco_UseCustomStyle ?> /><label for="navaco_UseCustomStyle">استفاده از استایل سفارشی برای فرم</label><br>
        </td>
      </tr>
      
      
      <tr class="user-display-name-wrap" id="navaco_CustomStyleBox" <?php if(get_option('navaco_UseCustomStyle') != 'true') echo 'style="display:none"'; ?>>
        <th>استایل سفارشی</th>
        <td>
          <textarea style="width: 90%;min-height: 400px;direction:ltr;" name="navaco_CustomStyle" id="navaco_CustomStyle"><?php echo get_option('navaco_CustomStyle') ?></textarea><br>
        </td>
      </tr>
      
    </tbody>
  </table>
  <p class="submit"><input type="submit" value="به روز رسانی تنظیمات" class="button button-primary" id="submit" name="submit"></p>
</form>

<script>
  if(typeof jQuery == 'function')
  {
    jQuery("#navaco_UseCustomStyle").change(function(){
      if(jQuery("#navaco_UseCustomStyle").prop('checked') == true)
        jQuery("#navaco_CustomStyleBox").show(500);
      else
        jQuery("#navaco_CustomStyleBox").hide(500);
    });
  }
</script>

