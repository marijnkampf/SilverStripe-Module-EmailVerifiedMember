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

		<p>Hello $Moderator.Name,</p>
		
		<p><a href="$ModerationLink">$Member.Name <% if $Member.Nickname %>($Member.Nickname)<% end_if %></a> has created an account on $SiteTitle.</p>
		
		<p>Please log in to the <a href="$ModerationLink">security area of the CMS</a> to approve or delete their account.</p>

		<p>You can reply directly to $Member.Name by replying to this email and let them know your decision.</p>
	</body>
</html>
