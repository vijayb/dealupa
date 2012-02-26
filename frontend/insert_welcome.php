<?


$html = "";

if ($w == 100) {


	$html .= <<<HTML
	

<div id="welcome-100">
	<div>
		<table class="welcome-table">
		<tr>
		<td style="width:190px;"><img id="welcome-image" src="/images/envelope.png"></td>
		<td><span style="color:#000000; font-size:32px; line-height:1.3" id="welcome-headline"></span></td>
		</tr>
		</table>
		<br>
		<span style="color:#000000; font-size:20px; font-weight:700;">Sign up in seconds.</span>
		<br><br>
		<div style="width:100%; text-align:center">
			<table class="welcome-table">
			<tr>
			<td>Enter your email</td>
			<td>Pick a password</td>
			<td>&nbsp;</td>
			</tr>
			<tr>
			<td><input type="text" id="signup-email" name="signup-email">&nbsp;&nbsp;</td>
			<td><input type="password" id="signup-password" name="signup-password">&nbsp;&nbsp;</td>
			<td><input type="button" class="c-g save-search" style="height:35px; font-size:18px;" value="Go!" onclick="signupUser();"></td>
			</tr>
			<tr>
			<td><span style="font-size:13px; color:#999999;">We'll never share it or spam you.</span></td>
			<td></td>
			<td></td>
			</tr>
			</table>
		</div>
		<br>
		<div id="error-message"></div>
		<br>
		or <fb:login-button autologoutlink=true size="medium" scope="email"></fb:login-button> with Facebook
		<br><br>
		<a href="javascript:void(0);" onclick="removeWelcome(1);" style="color:#687eab">Damn you, popup, go away!</a>
		<span id="welcome-fine-print"></span>
	</div>
</div>
	
	
	
	
HTML;


} else if ($w == 200) {




	$html .= <<<HTML



<div id="welcome-200">
	<a href="javascript:void(0);" onclick='removeWelcome(1);'><div id="close" style="position:absolute;top:0px;right:0px;padding:3px 6px;"><img src="/images/dark_orange_x.png"></div></a>
	<span style="text-shadow:#c05e00 0px -1px 0px; color:#ffffff; font-size:28px; line-height:1.3" id="welcome-headline">Sign up to get the most of out Dealupa.</span>
	<br><br>
	<div style="width:100%; text-align:center">
		<table class="welcome-table">
		<tr>
		<td style="color:#ffffff">Enter your email</td>
		<td style="color:#ffffff">Pick a password</td>
		<td>&nbsp;</td>
		</tr>
		<tr>
		<td><input type="text" id="signup-email" name="signup-email" style="border:1px #c05e00 solid;">&nbsp;&nbsp;</td>
		<td><input type="password" id="signup-password" name="signup-password" style="border:1px #c05e00 solid;">&nbsp;&nbsp;</td>
		<td><input type="button" class="c-g save-search" style="height:35px; font-size:18px;" value="Go!" onclick="signupUser();"></td>
		</tr>
		<tr>
		<td><span style="font-size:13px; color:#833b00;">We'll never share it or spam you.</span></td>
		<td></td>
		<td></td>
		</tr>
		</table>
	</div>
	<div id="error-message"></div>
	<br>
	<span style="font-size:13px; color:#833b00;">or <fb:login-button autologoutlink=true size="medium" scope="email"></fb:login-button> with Facebook</span>
	<span id="welcome-fine-print"></span>

</div>	






HTML;

} else if ($w == 300) {




	$html .= <<<HTML



<div id="welcome-300">
	<a href="javascript:void(0);" onclick='removeWelcome(1);'><div id="close" style="position:absolute;top:0px;right:0px;padding:3px 6px;"><img src="/images/dark_orange_x.png"></div></a>
	<table width=100%>
	<tr>
	<td style="padding:10px">
		<span style="text-shadow:#c05e00 0px -1px 0px; color:#ffffff; font-size:18px; line-height:1.3" id="welcome-headline">
			<span style="font-size:28px">Welcome to Dealupa, the best place to shop for daily deals from all the best deal sites.</span>
			<br>
			Sign up to get search-based emails when the deals you want are available.
		</span>
	</td>
	<td>&nbsp;&nbsp;</td>
	<td style="text-align:center">
		<div>
			<table class="welcome-table">
			<tr>
			<td style="color:#ffffff">Enter your email</td>
			<td style="color:#ffffff">Pick a password</td>
			<td>&nbsp;</td>
			</tr>
			<tr>
			<td><input type="text" id="signup-email" name="signup-email" style="border:1px #c05e00 solid;">&nbsp;&nbsp;</td>
			<td><input type="password" id="signup-password" name="signup-password" style="border:1px #c05e00 solid;">&nbsp;&nbsp;</td>
			<td><input type="button" class="c-g save-search" style="height:35px; font-size:18px;" value="Go!" onclick="signupUser();"></td>
			</tr>
			<tr>
			<td><span style="font-size:13px; color:#833b00;">We'll never share it or spam you.</span></td>
			<td></td>
			<td></td>
			</tr>
			</table>
		</div>
		<span style="font-size:13px; color:#833b00;">or&nbsp;&nbsp;<fb:login-button autologoutlink=true size="medium" scope="email"></fb:login-button>&nbsp;&nbsp;with Facebook
		<div id="error-message"></div>
	</td>
	</tr>
	</table>
</div>	






HTML;

} else if ($w == 400) {




	$html .= <<<HTML


<div id="welcome-400">


	<div id="welcome-400-overlay" style="display:block; width:675px">
		<div>
			<span style="color:#ffffff; font-size:32px; line-height:1.3" id="welcome-headline">
			Get all the best <span id="welcome-400-category"></span> deals in <span id="welcome-400-city"></span> from around the web.
			</span>
			<br><br>
			<img src="/email_images/logo_strip.png">
			<br><br><br>
			<div style="width:100%; text-align:center">
				<table class="welcome-table">
				<tr>
				<td style="color:#ffffff">Enter your email</td>
				<td style="color:#ffffff">Pick a password</td>
				<td>&nbsp;</td>
				</tr>
				<tr>
				<td><input type="text" id="signup-email" name="signup-email">&nbsp;&nbsp;</td>
				<td><input type="password" id="signup-password" name="signup-password">&nbsp;&nbsp;</td>
				<td><input type="button" class="c-g save-search" style="height:35px; font-size:18px;" value="Go!" onclick="signupUser();"></td>
				</tr>
				<tr>
				<td><span style="font-size:13px; color:#999999;">We'll never share it or spam you.</span></td>
				<td></td>
				<td></td>
				</tr>
				</table>
			</div>
			<br>
			<div id="error-message"></div>
			<br>
			<a href="javascript:void(0);" onclick="removeWelcome(1); showLogin();" style="font-weight:bold">Already registered? Sign in.</a>
			<br><br>
			or <fb:login-button autologoutlink=true size="medium" scope="email"></fb:login-button> with Facebook
			<br>
			<span id="welcome-fine-print"></span>
		</div>
	</div>



</div>

HTML;



} else if ($w == 401) {




	$html .= <<<HTML


<div id="welcome-400">


	<div id="welcome-400-overlay" style="display:block; width:675px;">
		<div>
			<span style="color:#ffffff; font-size:32px; line-height:1.3" id="welcome-headline">
			Get all the best <span id="welcome-400-category"></span> deals in <span id="welcome-400-city"></span><br>from around the web.</span>
			<br><br>
			<img src="/email_images/logo_strip.png">
			<br><br>
			<div style="width:100%; text-align:center">
				<table class="welcome-table">
				<tr>
				<td style="color:#ffffff">Enter your email</td>
				<td style="color:#ffffff">Pick a password</td>
				<td>&nbsp;</td>
				</tr>
				<tr>
				<td><input type="text" id="signup-email" name="signup-email">&nbsp;&nbsp;</td>
				<td><input type="password" id="signup-password" name="signup-password">&nbsp;&nbsp;</td>
				<td><input type="button" class="c-g save-search" style="height:35px; font-size:18px;" value="Go!" onclick="signupUser();"></td>
				</tr>
				<tr>
				<td><span style="font-size:13px; color:#999999;">We'll never share it or spam you.</span></td>
				<td></td>
				<td></td>
				</tr>
				</table>
			</div>
			<br>
			<div id="error-message"></div>
			<br>
			or <fb:login-button autologoutlink=true size="medium" scope="email"></fb:login-button> with Facebook
			<br>
			<span id="welcome-fine-print"></span>
		</div>
	</div>



</div>

HTML;






} else if ($w == 500) {



	$html .= <<<HTML


<div id="welcome-500">


	<div id="welcome-500-overlay" style="display:block">
		<div>
			<span style="color:#ffffff; font-size:36px; line-height:1.3" id="welcome-headline">
			Win your dream vacation,<br> courtesy of <b>Dealupa.</b>
			<br>
			</span>
			<br><br>
			<div style="width:100%; text-align:center">
				<table class="welcome-table">
				<tr>
				<td style="color:#ffffff">Enter your email</td>
				<td style="color:#ffffff">Pick a password</td>
				<td>&nbsp;</td>
				</tr>
				<tr>
				<td><input type="text" id="signup-email" name="signup-email">&nbsp;&nbsp;</td>
				<td><input type="password" id="signup-password" name="signup-password">&nbsp;&nbsp;</td>
				<td><input type="button" class="c-g save-search" style="height:35px; padding:0px 20px; font-size:18px;" value="Go!" onclick="signupUser();"></td>
				</tr>
				<tr>
				<td><span style="font-size:13px; color:#999999;">We'll never share it or spam you.</span></td>
				<td></td>
				<td></td>
				</tr>
				</table>
			</div>
			<div id="error-message"></div>
			<br>
			or <fb:login-button autologoutlink=true size="medium" scope="email"></fb:login-button> with Facebook | <a href="javascript:void(0);" onclick="removeWelcome(1); showLogin();">Already registered? Sign in.</a>
			<br><br>
			<b>Dealupa:</b> The best daily deals in <span id="welcome-500-city">your city</span> from around the web.
			<br><br>
			<span id="welcome-fine-print" style="font-size:12px;"><a href="http://blog.dealupa.com/?page_id=341" target=_blank>Terms & Conditions</a></span>

		</div>
	</div>



</div>





HTML;

}






echo($html);

?>