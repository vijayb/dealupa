
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  
  <title>Centered &middot; jQuery Masonry</title>
  
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
  <!--[if lt IE 9]><script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
  
  <link rel="stylesheet" href="/masonry/css/style.css" />
  
  
    <script src="/masonry/js/modernizr-transitions.js"></script>
  
  <script src="http://desandro.com/mint/?js"></script> <!-- analytics -->

  <!-- scripts at bottom of page -->
  
</head>
<body class="demos ">
  

  
<div id="container" class="transitions-enabled centered clearfix">

	<div class="box col2">
		<img src="https://s3.amazonaws.com/munchonme.com/offers/412/5.jpg">
	</div>
	<div class="box col2">
		<img src="http://a1.ak.lscdn.net/imgs/4593106b-4a27-4ad2-9d90-f4d098529781/280_q60_.jpg">
	</div>
	<div class="box col2">
		<img src="https://s3.amazonaws.com/munchonme.com/offers/414/1.jpg">
	</div>
	<div class="box col2">
		<img src="http://a0.ak.lscdn.net/imgs/b80a39d5-5adf-4375-962f-5d926b9afd71/700_q60_.jpg">
	</div>
	<div class="box col2">
		<img src="http://a5.ak.lscdn.net/imgs/69b8f374-4edd-44c0-b307-acb5c796565b/280_q60_.jpg">
	</div>
	<div class="box col2">
		<img src="http://assets1.grouponcdn.com/images/site_images/1988/3941/Oakland-Massage-Therapy_grid_6.jpg">
	</div>
	<div class="box col2">
		<img src="http://a0.ak.lscdn.net/imgs/9db2c07b-9038-496c-9774-7b9e0d9374d0/280_q60_.jpg">
	</div>
	<div class="box col2">
		<img src="https://images.doodledeals.com/8dde434b284ccb2f85e7195aa6f79de78f025ac8/400x250">
	</div>
	<div class="box col2">
		<img src="http://a0.ak.lscdn.net/imgs/0bce2916-f773-4c91-ba58-6a5d6ed07021/300_q60_.jpg">
	</div>
	<div class="box col2">
		<img src="http://www.tzoo-img.com/images/tzoo.p.local.47187.plumberry2.jpg">
	</div>
	<div class="box col2">
		<img src="http://www.tzoo-img.com/images/tzoo.p.local.13889.casamadrona3.jpg">
	</div>
	<div class="box col2">	<img src="https://s3.amazonaws.com/munchonme.com/offers/427/3.jpg">
	</div>
	<div class="box col2">
		<img src="http://assets1.grouponcdn.com/images/site_images/1990/2646/Velma-at-Salon-Saavy-2_grid_6.jpg">
	</div>
	<div class="box col2">
		<img src="http://a2.ak.lscdn.net/imgs/d793c464-7135-4be6-9966-206c71f2c82b/280_q60_.jpg">
	</div>
	<div class="box col2">
		<img src="http://a4.ak.lscdn.net/imgs/92cb4e7f-19bb-4189-a75c-d1a717790bea/300_q60_.jpg">
	</div>
	<div class="box col2">
		<img src="http://a1.ak.lscdn.net/imgs/553a2cf2-160f-4757-8f0d-beee8e62e472/280_q60_.jpg">
	</div>
	<div class="box col2">
		<img src="https://s3.amazonaws.com/munchonme.com/offers/419/2.jpg">
	</div>
	<div class="box col2">
		<img src="http://assets1.grouponcdn.com/images/site_images/1993/2474/El-Hueco2_grid_6_grid_6.jpg">
	</div>
	<div class="box col2">
		<img src="http://a5.ak.lscdn.net/imgs/ab23621d-a310-4485-8590-5c23be4bf186/280_q60_.jpg">
	</div>
	<div class="box col2">
		<img src="http://assets1.grouponcdn.com/images/site_images/1991/8430/Olivias-Brunch-and-Fine-Dining2_grid_6.jpg">
	</div>
	<div class="box col2">
		<img src="https://s3.amazonaws.com/munchonme.com/offers/424/5.jpg">
	</div>
	<div class="box col2">
		<img src="http://a5.ak.lscdn.net/imgs/43d20941-509f-4834-a047-272211b4ae2c/280_q60_.jpg">
	</div>
</div>




  <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
  <script type="text/javascript" src="/masonry/jquery.masonry.min.js"></script>

  <script>
  $(function(){
    $('#container').imagesLoaded( function(){
    $('#container').masonry({
      itemSelector: '.box',
      columnWidth: 310,
      isAnimated: !Modernizr.csstransitions,
      isFitWidth: true,
	  gutterWidth: 0,
    });
      });
  });
</script>
    


</body>
</html>