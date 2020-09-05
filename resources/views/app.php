<!DOCTYPE html>
<html>
<head>
	<title>texnder.com</title>
	<style type="text/css">
		html,body{
			padding: 0;
			margin: 0;
			width: 100%;
			height: 100%;
			font-size: 15px;
		}
		#main{
			width: inherit;
			height: inherit;
			display: flex;
			align-items: center;
			justify-content: center;
		}
		.logoConatiner{
			max-width: 400px;
			max-height: auto;
		}
		.logo{
			width: 400px;
			height: auto;
		}
		.important-links{
			display: flex;
			justify-content: center;
		}
		.important-links a{
			padding: 2px 12px;
		}
	</style>
</head>
<body>
	<div id="main">
		<div class="container">
			<div class="logoConatiner">
				<img src="<?= url('images/logo.png') ?>" class="logo">
			</div>
			<div class="important-links">
				<a href="http://texnder.com/components" target="_blank">components</a>
				<a href="http://texnder.com/documentation/" target="_blank">documentations</a>
				<a href="http://texnder.com/about-us" target="_blank">about us</a>
				<a href="http://texnder.com/contact-us" target="_blank">contact us</a>
			</div>
		</div>
	</div>
</body>
</html>