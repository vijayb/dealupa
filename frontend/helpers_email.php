<?php

require_once("get_deal.php");
require_once("helpers.php");
require_once("array_constants.php");

function get_email_html($user_id, $token, $email, $first_name, $zipcode, $edition, $recommend_deals_to_email, $heart_deals_to_email, $deals_to_email, $deals_con, $memcache, $cache_life) {

	global $cities_url;
	global $cities;
	global $companies;
	global $domain_ac;

	
	$set_preferences_link = generate_settings_link($email);
	$logged_in_link = generate_logged_in_link($email);
	
	if (isset($first_name) && $first_name != "") {
	} else {
		$first_name = "there";
	}

	if (isset($zipcode) && $zipcode != "") {
		$zipcode_correction_html = "";
	} else {
		$edition_name = $cities[$edition];
		$zipcode_correction_html = "Not in " . $edition_name . "? Tell us your zip code <a target=_dealupa href='" . $set_preferences_link . "' style='color:#BC5D33; text-decoration:none;'>here</a>.";
	}
	

	$html = <<<HTML

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<body>

<center>

<table border=0 width=585 cellpadding=0 cellspacing=0 bgcolor=#f9f4e0>

	<tr>
		<td>
			<a href="{$logged_in_link}" target=_dealupa><img src="{$domain_ac}/email_images/email_top_wood.jpg" target=_dealupa></a>
		</td>
	</tr>
HTML;

	if ($zipcode_correction_html != "") {
	
		$html .= <<<HTML
	<tr>
		<td style="font-family:sans-serif; text-align:center; color:#3a1500; padding:30px; font-size:13px;">
			{$zipcode_correction_html}
		</td>
	</tr>
HTML;
	}

	
	



	if (count($heart_deals_to_email) == 0) {
	
		$html .= <<<HTML
			<tr>
				<td style="font-family:sans-serif; text-align:center; color:#3a1500; padding:30px;">
					<span style="font-size:28px; font-weight:bold;">Improve These Daily Emails<br>in 30 Seconds</span>
					<br><br>
					<span style="font-size:13px; font-family:sans-serif;">
						Love getting massage deals? Hate getting pet deals because you have no pets?
						<br>
						Or maybe the other way around?
						
						<br><br>
						
						<a target=_dealupa href="{$set_preferences_link}" style="color:#BC5D33; text-decoration:none; font-weight:bold;">Tell us what kind of things you're into</a> and we'll send you deals that match.
					</span>
				</td>
			</tr>
HTML;
	}	
	
	
	
	
	
	
	
	
	
	
	
	
	if (count($recommend_deals_to_email) > 0) {

		$html .= <<<HTML
			<tr>
				<td style="font-family:sans-serif; text-align:center; color:#3a1500; padding:30px;">
					<span style="font-size:28px; font-weight:bold;">Dealupa Recommends</span>
					<br><br>
					<span style="font-size:13px; font-family:sans-serif;">
						These deals are so good that you absolutely cannot miss them!
						<br>
						Deals here are hand-selected; only a few deals a month make it to here.
					</span>
				</td>
			</tr>
HTML;

	}

	for ($i = 0; $i < count($recommend_deals_to_email); $i++) {
		$deal = getDealById($recommend_deals_to_email[$i], $deals_con, $memcache, $cache_life);
		$single_deal_html = get_deal_html_email($deal, $user_id, $token);
		$html .= $single_deal_html;
	}
	
	
	
	
	
	
	
	
	
	
	if (count($heart_deals_to_email) > 0) {	
		$html .= <<<HTML
			<tr>
				<td style="font-family:sans-serif; text-align:center; color:#3a1500; padding:30px;">
					<span style="font-size:28px; font-weight:bold;">Deals in Categories You <img src="{$domain_ac}/images/cat_heart_on.png"></span>
					<br><br>
					<span style="font-size:13px; font-family:sans-serif;">
						These deals are from categories you've told us you love.
						<br>
						Go to your <a target=_dealupa href="{$set_preferences_link}" style="color:#BC5D33; text-decoration:none;">Settings</a> to edit those categories.
					</span>
				</td>
			</tr>
HTML;
	}

	for ($i = 0; $i < count($heart_deals_to_email); $i++) {
		$deal = getDealById($heart_deals_to_email[$i], $deals_con, $memcache, $cache_life);
		$single_deal_html = get_deal_html_email($deal, $user_id, $token);
		$html .= $single_deal_html;
	}

	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	if (count($recommend_deals_to_email) != 0 || count($heart_deals_to_email) != 0) {
		$html .= <<<HTML
			<tr>
				<td style="font-family:sans-serif; text-align:center; color:#3a1500; padding:30px;">
					<span style="font-size:28px; font-weight:bold;">More Deals We Think You'll Like</span>
					<br><br>
					<span style="font-size:13px; font-family:sans-serif;">
						See something here you're not into?
						<br>
						Go to your <a target=_dealupa href="{$set_preferences_link}" style="color:#BC5D33; text-decoration:none;">Settings</a> to to tell us what things you like.
					</span>
				</td>
			</tr>
HTML;
	} else {
		$html .= <<<HTML
			<tr>
				<td style="font-family:sans-serif; text-align:center; color:#3a1500; padding:30px;">
					<span style="font-size:28px; font-weight:bold;">Deals We Think You'll Like</span>
					<br><br>
					<span style="font-size:13px; font-family:sans-serif;">
						See something here you're not into?
						<br>
						Go to your <a target=_dealupa href="{$set_preferences_link}" style="color:#BC5D33; text-decoration:none;">Settings</a> to to tell us what things you like.
					</span>
				</td>
			</tr>
HTML;
	}
	
	for ($i = 0; $i < count($deals_to_email); $i++) {
		$deal = getDealById($deals_to_email[$i], $deals_con, $memcache, $cache_life);
		$single_deal_html = get_deal_html_email($deal, $user_id, $token);
		$html .= $single_deal_html;
	}
	
	$html .= <<<HTML
	
	
	<tr>
		<td>
			<center>
			<span style="font-size:13px; font-family:sans-serif; padding:10px;">
				<a href="{$logged_in_link}" target=_dealupa style="color:#BC5D33; text-decoration:none; font-size:20px; font-weight:bold">See more deals</a>
				<br><br>
				<a target=_dealupa href="{$set_preferences_link}" style="color:#BC5D33; text-decoration:none;">Tell us what you like</a> and we'll send you deals that match your interests.
				<br><br>
				We'd love to get your feedback on this email and the Dealupa website.<br>Email us at <a href="mailto:founders@dealupa.com" style="color:#BC5D33; text-decoration:none;">founders@dealupa.com</a>.
				<br><br><br><br>
				<span style="font-size:11px"><a target=_dealupa href="{$domain_ac}/unsubscribe.php?user_id={$user_id}&token={$token}" style="color:#BC5D33; text-decoration:none;">Unsubscribe</a>.</span>
			</span>
			</center>
			<br><br>
		</td>
	</tr>
	
</table>

</center>

<br><br>
</body>

</html>
	
HTML;
	
	return $html;
	
}




function get_deal_html_email($deal, $user_id, $token) {

	global $cities_url;
	global $cities;
	global $companies;
	global $domain_ac;

	$force_edition = 0;
	


	require("deal_data_extraction.php");
	
	$categories = get_simple_categories_array();
			
	$deal_site_url = $domain_ac . "/click.php?user_id=$user_id&deal_id=" . $deal["id"] . "&token=" . $token;
	
	
	$single_deal_html = <<<HTML

<tr>
	<td style="font-family:sans-serif; color:#3a1500; font-size:13px;">
		<center>
			<table border=0 width=330 cellpadding=0 cellspacing=0 style="background-color:#222;">
				<tr>
					<td>
						<a href="{$deal_site_url}" target=_dealupa>
							<img src="{$image_url}" style="display:block" width=330>
						</a>
					</td>
				</tr>
				<tr>
					<td style="height:25px; background-color:#000; color:#fff; padding:7px;">
						
HTML;
	if ($price == 0) {
		$price_html = "Free";
	} else {
		$price_html = "\$" . $price;
	}

	$single_deal_html .= <<<HTML
					{$price_html}
HTML;

	if ($value > 0) {

		$single_deal_html .= <<<HTML
					<span style="color:#777;">for \${$value} value</span>
HTML;
	}

	$single_deal_html .= <<<HTML
					</td>
				</tr>
				
				
				
				<tr>
					<td style="padding:5px 5px 0px 5px;">
						<a href="{$deal_site_url}" style="color:#BC5D33; font-size:18px; text-decoration:none;" target=_dealupa>
							{$name}
						</a>
					</td>
				</tr>
				<tr>
					<td style="padding:5px 5px 0px 5px; color:#999; font-size:12px;">
						{$street} {$city} {$state} 
					</td>
				</tr>
				<tr>
					<td style="padding:5px 5px 0px 5px; font-size:14px;">
						<span style="color:#fff;">{$deal["title"]}</span>
					</td>
				</tr>
				
HTML;


	if ($yelp_rating != "") {


		$single_deal_html .= <<<HTML
	
				<tr>
					<td style="padding:8px 5px 0px 5px; color:#999; font-size:12px;">
					
						<a id="yelp-link-{$deal["id"]}" href="{$deal["yelp_url"]}" target=_dealupa style="color:#BC5D33; text-decoration:none;">
							<img src="{$domain_ac}/images/yelp/yelp_{$yelp_rating}.png" alt="arrow">
							&#160;
							<img src="{$domain_ac}/images/yelp.png?150">
							&#160;- {$deal["yelp_review_count"]} reviews
						</a>
					
					</td>
				</tr>
HTML;
	}


	$single_deal_html .= <<<HTML
				<tr>
					<td style="padding:5px 5px 0px 5px; color:#999; font-size:12px;">
HTML;

	for ($x = 0; $x < count($category_arr); $x++) {
		if ($category_arr[$x] != 0) {
			$single_deal_html .= "<span style='background-color:black; padding:2px 5px;'>" . $categories[$category_arr[$x]] . "</span>&nbsp;&nbsp;&nbsp;";
		}
	}
					
	$single_deal_html .= <<<HTML
					</td>
				</tr>
				<tr>
					<td style="padding:20px 5px 15px 5px;">
						<center>
							<a target=_dealupa href="{$deal_site_url}" style="background-color:#ea9321; color:#000; text-decoration:none; padding:8px; font-size:16px; font-weight:bold;">See details at {$companies[$deal["company_id"]]}</a>
						</center>
					</td>
				</tr>
				<tr>
					<td style="padding:5px 5px 10px 5px; color:#999; font-size:12px;">
						<center>Posted today</center>
					</td>
				</tr>
			</table>
		</center>
		<br><br>
	</td>
</tr>

HTML;

	return $single_deal_html;
}