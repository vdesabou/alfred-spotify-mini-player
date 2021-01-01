<html>
<head>
	<title>Alfred Spotify Mini Player Setup</title>

	<link rel="stylesheet" href="include/setup/style/normalize.css" />
	<link rel="stylesheet" href="include/setup/style/style.css">
</head>

<body>
	<div id="wrapper" class="wrapper">
		<section>
			<h1>Alfred Spotify Mini Player  Setup</h1>
			<p>
				Hi! In order to use Alfred Spotify Mini Player, you need to generate a Spotify API key by
				creating a Spotify app.
			</p>
		</section>

		<section>
			<h2>Instructions</h2>
			<p>
				Setting up the app is not very hard, but it has a couple
				specific steps. Just follow these instructions, and you'll be
				fine!
			</p>
			<p>
				If you're worried about privacy,
				<a href="#privacy">here&rsquo;s</a> what I have to say.
			</p>
			<p class="info note">
				If you already know how to make a Spotify app, you can enter the
				ID and secret <a href="#ajax">here</a>. Please set the callback
				URL correctly, though (step 8).
			</p>

			<ol>
				<li>Open up the <a href="https://developer.spotify.com/my-applications/#!/" target="blank">Spotify application manager page</a>.</li>
				<li>Login, if prompted.</li>
				<li>
					Click "Create an app."
					<figure>
						<img src="img/create_an_app_button.png"
						alt="A screenshot of the Spotify Developer website, focusesed on the 'Create an app button'"
						/>
						<figcaption>The 'Create an app' button</figcaption>
					</figure>
				</li>
				<li>
					Enter an application name and description. They can be anything you want.
					<figure>
						<img src="img/create_an_app_name.png"
						alt="A screenshot of the Spotify Developer application creation page, with example data entered in the 'Application Name' and 'Application Description' fields."
						/>
						<figcaption>This data will do nicely.</figcaption>
					</figure>
				</li>
				<li>Click "Create"!</li>
				<li>
					Note the "Client ID" and "Client Secret." You'll enter them
					later.
				</li>
				<li>Click the "Add URI" button to add a new Redirect URI.</li>
				<li>
					Type <code>http://localhost:15298/callback.php</code> and click "Add"
					<figure>
						<img src="img/redirect.png"
						alt="A screenshot of the Spotify Developer application data page, with our redirect page ready to be submitted."
						/>
						<figcaption>Just type it in!</figcaption>
					</figure>
				</li>
				<li>
					Save those changes!
					<figure>
						<img src="img/save.png"
						alt="A screenshot of the Spotify Developer application data page showing the save button."
						/>
						<figcaption>The green one, please.</figcaption>
					</figure>
				</li>
				<li>Copy and paste your <strong>Client ID</strong> and <strong>Client Secret</strong> below:
					<form id="ajax" action="client_callback.php">
						<table>
							<tr>
								<td colspan="2" id="response" class="response">Response here</td>
							</tr>
							<tr>
								<td align="right"><label for="ClientID">Client ID:</label></td>
								<td><input type="text" name="ClientID" id="ClientID" placeholder="8bba5265e1e6199op53216e03bt6aeff" required /></td>
							</tr>
							<tr>
								<td align="right"><label for="ClientSecret">Client Secret:</label></td>
								<td><input type="text" name="ClientSecret" id="ClientSecret" placeholder="3b3z7eg0evol510ebb32f94667135e40" required /></td>
							</tr>
						</table>

						<input type="submit" value="Save" />
						<a href="#privacy" class="cancel">privacy notice</a>
					</form>
					<div class="clear"></div>
					<figure>
						<img src="img/client_info.png"
						alt="A screenshot of the Spotify Developer application data page showing the locations of the client ID and secret."
						/>
						<figcaption>This will not be a blur on your screen.</figcaption>
					</figure>
				</li>
				<li>Go back to Alfred Spotify Mini Player and finish your setup!</li>
			</ol>
		</section>

		<section>
			<h2 id="privacy">Privacy</h2>
			<p>
				Alfred Spotify Mini Player merely reads and writes your keys from and to your
				hard drive. They are never used in a way that could compromise
				their integrity other than in the Spotify query, as required by
				the API. Alfred Spotify Mini Player itself <strong>never</strong> sends the data
				anywhere else, for any reason, at any time.
			</p>
		</section>
	</div>

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
	<script>
	var request;

		// http://stackoverflow.com/questions/5004233/jquery-ajax-post-example-with-php
		$("#ajax").submit(function(event) {
			$("input").prop('disabled', true);
			$("#response").show();
			$("#response").addClass("note");
			$("#response").text("Verifying your information...");

			request = $.ajax({
				type: "GET",
				url: "client_submit.php",
				data: {
					"id": $("#ClientID").val(),
					"secret": $("#ClientSecret").val()
				},
				dataType: "json"
			});

			request.done(function(response) {
				console.log(response);
				if (response.status === "error") {
					$("#response").removeClass("note").removeClass("success");
					$("#response").show().addClass("error");
					$("#response").html("<strong>Error:</strong> " + response.message);
				} else {
					$("#response").removeClass("note").removeClass("error");
					$("#response").show().addClass("success");
					$("#response").text(response.message);
				}
			});

			request.always(function() {
				$("#response").removeClass("note");
				$("input").prop('disabled', false);
			});

		    event.preventDefault();
		});
	</script>
</body>
</html>