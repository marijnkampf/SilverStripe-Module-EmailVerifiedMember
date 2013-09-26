<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
	<head>
		<style>
			p{
				font-size: 1.2em;
				color: #444;
			}
			p.comments{
				font-size: 1.4em;
				color: #222;
				padding: 20px;
			}
		</style>
	</head>
	<body>

		<p>Hello $Member.Name,</p>
		
		<p>Please <a href="$ValdiationLink">validate your email address</a> for $BaseHref.</p>
		
		<p>If the above link doesn't work, please copy and paste the line below into your browser's navigation bar.</p>

		<p>$ValdiationLink</p>
		<% if $ModerationRequired %>
			<p>After you have validated your email your account will be reveiwed by a moderator. Once approved you can access the website.</p>
		<% else %> 
			<p>After validating your email you can access the website.</p>
		<% end_if %>
		
	</body>
</html>
