		</td>
		</tr>
		</table>
	</div>


	<div id="top-bar">
		<div id="top-bar-logo" style="float:left; position:relative; top:-3px;">
			<a href="/"><img src="/images/logo.png"></a>
		</div>

		<div id="edition" style="float:left; left:10px; position:relative; background: #9C4600;">
			<span id="city-name"><a href='index.php' style="color:#ffffff;">Back to Dealupa</a></span>
		</div>


		<?php require("top_links_div.php"); ?>
		
	</div>	

	
<!-- FB BEGIN -->	

    <div id="fb-root"></div>
    <script>
      window.fbAsyncInit = function() {
        FB.init({
<?php if ($_SERVER["HTTP_HOST"] == "mobdealio.com") { ?>
        appId: '144572715637682', // MOBDEALIO
<?php } else if ($_SERVER["HTTP_HOST"] == "dealupa.com") { ?>
		appId: '201211216608489', // DEALUPA
<?php } ?>
          cookie: true,
          xfbml: true,
		  channelUrl : 'channel.html', // channel.html file
          oauth: true
        });
      };
      (function() {
        var e = document.createElement('script'); e.async = true;
        e.src = document.location.protocol +
          '//connect.facebook.net/en_US/all.js';
        document.getElementById('fb-root').appendChild(e);
      }());
    </script>
	
<!-- FB END -->	


<!-- GET SATISFACTION BEGIN -->

<script type="text/javascript" charset="utf-8">
  var is_ssl = ("https:" == document.location.protocol);
  var asset_host = is_ssl ? "https://s3.amazonaws.com/getsatisfaction.com/" : "\
http://s3.amazonaws.com/getsatisfaction.com/";
  document.write(unescape("%3Cscript src='" + asset_host + "javascripts/feedbac\
k-v2.js' type='text/javascript'%3E%3C/script%3E"));
</script>

<script type="text/javascript" charset="utf-8">
  var feedback_widget_options = {};
  feedback_widget_options.display = "overlay";
  feedback_widget_options.company = "deelio";
  feedback_widget_options.placement = "bottom";
  feedback_widget_options.color = "#6e6252";
  feedback_widget_options.style = "idea";
  var feedback_widget = new GSFN.feedback_widget(feedback_widget_options);
</script>

<!-- GS END -->

	
</body>

</html>