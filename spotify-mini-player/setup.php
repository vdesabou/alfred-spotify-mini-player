<html>

<head>
    <title>Alfred Spotify Mini Player Setup</title>

    <link rel="stylesheet" href="include/setup/style/normalize.css" />
    <link rel="stylesheet" href="include/setup/style/style.css">
    <link rel="stylesheet" href="https://alfred-spotify-mini-player.com/css/main.css">
    <link rel="stylesheet"
        href="//cdnjs.cloudflare.com/ajax/libs/github-fork-ribbon-css/0.1.1/gh-fork-ribbon.min.css" />
</head>

<body>

    <body id="js-body">
        <!--[if lt IE 9]><div class="upgrade notice-warning"><strong>Your browser is quite old!</strong> Why not <a href="http://whatbrowser.org/">upgrade to a newer one</a> to better enjoy this site?</div><![endif]-->
        <div class="github-fork-ribbon-wrapper left">
            <div class="github-fork-ribbon">
                <a href="https://github.com//vdesabou/alfred-spotify-mini-player">Visit on GitHub</a>
            </div>
        </div>

        <header id="masthead">
            <div class="inner-wrap">
                <a href="https://alfred-spotify-mini-player.com/" class="site-title">Alfred Spotify Mini Player</a>
        </header><!-- /.masthead -->

        <div id="js-menu-screen" class="menu-screen"></div>


        <div id="page-wrapper">
            <div id="main" role="main">
                <article class="wrap" itemscope itemtype="http://schema.org/Article">


                    <div class="page-title">
                        <h1>Setup</h1>
                    </div>
                    <div class="inner-wrap">
                        <div id="content" class="page-content" itemprop="articleBody">
                            <nav class="toc">
                                <ul id="markdown-toc">
                                    <li><a href="#create-a-spotify-application"
                                            id="markdown-toc-create-a-spotify-application">Create a Spotify
                                            Application</a></li>
                                    <li><a href="#enter-client-id-and-secret"
                                            id="markdown-toc-enter-client-id-and-secret">Enter your <em>Client ID</em> and
                                            <em>Client Secret</em></a></li>
                                    <li><a href="#authenticate-with-spotify" id="markdown-toc-authenticate-with-spotify">Authenticate your application with Spotify</a></li>
                                    <li><a href="#create-the-library" id="markdown-toc-create-the-library">Create the
                                            library</a></li>
                                    <li><a href="#troubleshooting" id="markdown-toc-troubleshooting">Troubleshooting</a></li>
                                    <li><a href="#privacy" id="markdown-toc-privacy">Privacy</a></li>
                                </ul>

                            </nav>
                            <p>
                                Setting up the workflow is easy, you just need to follow these steps
                            </p>
                            <h2 id="create-a-spotify-application">Create a Spotify Application</h2>

                            <ul>
                                <li>
                                    <p>Create an <a
                                            href="https://developer.spotify.com/dashboard/applications">application on
                                            Spotify</a> (this is for both free and premium users)</p>

                                    <ul>
                                        <li>Click on <em>Create an App</em> and set an app name and description:</li>
                                    </ul>
                                </li>
                            </ul>

                            <figure>
                                <a href="https://alfred-spotify-mini-player.com/images/setup1.jpg"><img
                                        src="https://alfred-spotify-mini-player.com/images/setup1.jpg" /></a>
                                <figcaption>Create an app.</figcaption>
                            </figure>

                            <ul>
                                <li>On the application page, click on <em>Edit Settings</em> :</li>
                            </ul>

                            <figure>
                                <a href="https://alfred-spotify-mini-player.com/images/setup10.jpg"><img
                                        src="https://alfred-spotify-mini-player.com/images/setup10.jpg" /></a>
                                <figcaption>Edit Settings.</figcaption>
                            </figure>

                            <ul>
                                <li>Redirect URI must be set to <code
                                        class="language-plaintext highlighter-rouge">http://localhost:15298/callback.php</code>,
                                    then click <em>Add</em>, and then click <em>Save</em> :</li>
                            </ul>

                            <p class="notice-danger"><strong>Warning:</strong> Make sure you’ve clicked <em>Save</em>
                                button once you set the Redirect URI</p>

                            <figure>
                                <a href="https://alfred-spotify-mini-player.com/images/setup11.jpg"><img
                                        src="https://alfred-spotify-mini-player.com/images/setup11.jpg" /></a>
                                <figcaption>Settings.</figcaption>
                            </figure>

                            <ul>
                                <li>The <em>Client ID</em> and <em>Client Secret</em> (needed in next steps) are then
                                    available :</li>
                            </ul>

                            <figure>
                                <a href="https://alfred-spotify-mini-player.com/images/setup12.jpg"><img
                                        src="https://alfred-spotify-mini-player.com/images/setup12.jpg" /></a>
                                <figcaption>Access Client ID and Client Secret.</figcaption>
                            </figure>

                            <h2 id="enter-client-id-and-secret">Enter your <em>Client ID</em> and <em>Client Secret in the form below</em>
                            </h2>

                            <div id="wrapper" class="wrapper">


                                <section>
                                    <form id="ajax" action="client_callback.php">
                                        <table>
                                            <tr>
                                                <td colspan="2" id="response" class="response">Response here</td>
                                            </tr>
                                            <tr>
                                                <td align="right"><label for="ClientID">Client ID:</label></td>
                                                <td><input type="text" name="ClientID" id="ClientID"
                                                        placeholder="👉 enter your client id here" required /></td>
                                            </tr>
                                            <tr>
                                                <td align="right"><label for="ClientSecret">Client Secret:</label></td>
                                                <td><input type="text" name="ClientSecret" id="ClientSecret"
                                                        placeholder="👉 enter your client secret here" required /></td>
                                            </tr>
                                        </table>

                                        <input type="submit" value="Save" />
                                    </form>
                                    <div class="clear"></div>

                                </section>
                            </div>

                            <h2 id="authenticate-with-spotify">Authenticate your application with Spotify</em>
                            </h2>

                            <p class="notice-danger"><strong>Warning:</strong> Make sure you’ve successfully done
                                    previous step, i.e enter and save client id and secret in the form.</p>

                            <ul>
                                <li>
                                    <p>Invoke the workflow (with keyword <code
                                            class="language-plaintext highlighter-rouge">spot_mini</code>, or
                                        <strong>highly recommended</strong> with an <a
                                            href="https://alfred-spotify-mini-player.com/articles/hotkeys">hotkey</a> )
                                        and select Authenticate with Spotify, Google Chrome or Firefox (<a
                                            href="https://github.com/vdesabou/alfred-spotify-mini-player/issues/341">Safari
                                            is not working for authentication</a>) will open and you’ll be prompted to
                                        login with Spotify and allow access to your application. At the end you should
                                        see a message like this:</p>

                                    <p><em>Hello xxx ! You are now successfully logged and you can close this
                                            window.</em></p>
                                </li>
                            </ul>

                            <h2 id="create-the-library">Create the library</h2>
                            <ul>
                                <li>
                                    <p>Invoke the workflow again and Create the library.</p>
                                </li>
                                <li>
                                    <p>You can then start using the workflow:</p>
                                </li>
                            </ul>

                            <figure>
                                <img src="https://alfred-spotify-mini-player.com/images/setup2.jpg" />
                                <figcaption>See progress by invoking workflow.</figcaption>
                            </figure>

                            <p>It will create your library in the background, but you’ll be able to access use the
                                workflow during that time (except for actions where library has to be modified).</p>

                            <ul>
                                <li>The artworks are downloaded locally in the background. It can take a while (several hours or even days) depending on how big is your library. You should get a notification when background download starts and ends</li>
                            </ul>

                            <figure>
                                <img src="https://alfred-spotify-mini-player.com/images/setup4.jpg" />
                                <figcaption>Notification for the start of background download of artworks.</figcaption>
                            </figure>

                            <ul>
                                <li>During that time, you can use the workflow (you’ll see the progress at the top of
                                    main menu), and you can see some blank artworks:</li>
                            </ul>

                            <figure>
                                <img src="https://alfred-spotify-mini-player.com/images/setup5.jpg" />
                                <figcaption>Example of blank artworks until the end of background download is over.
                                </figcaption>
                            </figure>

                            <ul>
                                <li>If you don't want to use artworks, it can be disabled in settings:</li>
                            </ul>

                            <figure>
                                <img src="https://alfred-spotify-mini-player.com/images/setup14.jpg" />
                                <figcaption>Disable artworks.
                                </figcaption>
                            </figure>

                            <h2 id="troubleshooting">Troubleshooting</h2>

                            <p>If you get issues during authentication, please check below before reporting the issue:</p>
                            <ul>
                                <li>
                                    <p>if you’re using a firewall or a software that blocks communications, note that
                                        you’ll have to unblock the following domains to use the workflow:</p>

                                    <ul>
                                        <li>
                                            <p>https://api.spotify.com (for interaction with Spotify)</p>
                                        </li>
                                        <li>
                                            <p>https://raw.githubusercontent.com (for downloading new release from web
                                                site)</p>
                                        </li>
                                        <li>
                                            <p>https://github.com/ (for downloading images when changing theme)</p>
                                        </li>
                                        <li>
                                            <p>http://api.stathat.com (for statistics)</p>
                                        </li>
                                    </ul>
                                </li>
                                <li>
                                    <p>if you’re using a proxy, don’t forget to enable this option in Alfred
                                        preferences, <strong><em>except for the time of authentication, it must be
                                                disabled</em></strong> :</p>
                                </li>
                            </ul>

                            <figure>
                                <a href="https://alfred-spotify-mini-player.com/images/setup6.jpg"><img
                                        src="https://alfred-spotify-mini-player.com/images/setup6.jpg" /></a>
                                <figcaption>Use Mac OS http proxy settings for scripts option (except for
                                    authentication).</figcaption>
                            </figure>

                            <ul>
                                <li><strong>Wappalyzer</strong> browser extension interfere with authentication process:
                                    make sure to disable it during time of authentication.</li>
                            </ul>

                            <h2 id="privacy">Privacy</h2>
                            <p>
                                Alfred Spotify Mini Player writes your <em>Client ID</em> and <em>Client Secret</em> in
                                a local <em>settings.db</em> database file. They are never used in a way that could compromise
                                their integrity other than in the Spotify query, as required by
                                the Spotify Web API. Alfred Spotify Mini Player itself <strong>never</strong> sends the
                                data
                                anywhere else, for any reason, at any time.
                            </p>

                            <hr />
                            <footer class="page-footer">

                            </footer><!-- /.footer -->
                            <aside>

                            </aside>
                        </div><!-- /.content -->
                    </div><!-- /.inner-wrap -->

                </article><!-- ./wrap -->
            </div><!-- /#main -->

        </div>

        <script src="https://alfred-spotify-mini-player.com/js/vendor/jquery-1.9.1.min.js"></script>
        <script src="https://alfred-spotify-mini-player.com/js/main.js"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
        <script>
            var request;

            // http://stackoverflow.com/questions/5004233/jquery-ajax-post-example-with-php
            $("#ajax").submit(function (event) {
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

                request.done(function (response) {
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

                request.always(function () {
                    $("#response").removeClass("note");
                    $("input").prop('disabled', false);
                });

                event.preventDefault();
            });
        </script>
    </body>

</body>

</html>