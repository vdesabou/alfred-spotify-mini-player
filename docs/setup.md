---
layout: article
permalink: /setup/
title: "Setup"
date: 2015-01-01
modified: 2020-10-05
toc: true
share: false
noindex: false
---

{% include toc.html %}

Setting up the workflow is easy, you just need to follow these steps:-

## Download and install the workflow

* [Download](https://github.com/vdesabou/alfred-spotify-mini-player/releases/latest/download/spotifyminiplayer.alfredworkflow) the workflow

* Open spotifyminiplayer.alfredworkflow by double-clicking it or dragging it into Alfred.

## Create a Spotify Application

* Create an [application on Spotify](https://developer.spotify.com/my-applications) (this is for both free and premium users)

   * Click on *Create an App* :-

<figure>
	<a href="{{ site.url }}/images/setup1.jpg"><img src="{{ site.url }}/images/setup1.jpg"></a>
	<figcaption>Create an app.</figcaption>
</figure>


  * Step 1: Set *App or Hardware Name* and *App or Hardware Description* as following, and select *Desktop App* :-

<figure>
	<a href="{{ site.url }}/images/setup7.jpg"><img src="{{ site.url }}/images/setup7.jpg"></a>
	<figcaption>Step 1.</figcaption>
</figure>

  * Step 2: Respond with **No** :-

<figure>
	<a href="{{ site.url }}/images/setup8.jpg"><img src="{{ site.url }}/images/setup8.jpg"></a>
	<figcaption>Step 2.</figcaption>
</figure>

  * Step 3: Tick all the boxes and click on *Submit* :-

<figure>
	<a href="{{ site.url }}/images/setup9.jpg"><img src="{{ site.url }}/images/setup9.jpg"></a>
	<figcaption>Step 3.</figcaption>
</figure>

  * On the application page, click on *Edit Settings* :-

<figure>
	<a href="{{ site.url }}/images/setup10.jpg"><img src="{{ site.url }}/images/setup10.jpg"></a>
	<figcaption>Edit Settings.</figcaption>
</figure>

  * Redirect URI must be set to `http://localhost:15298/callback.php`, then click *Add*, and then click *Save* :-

<figure>
	<a href="{{ site.url }}/images/setup11.jpg"><img src="{{ site.url }}/images/setup11.jpg"></a>
	<figcaption>Settings.</figcaption>
</figure>


**Warning:** Make sure you've clicked *Save* button once you set the Redirect URI
{: .notice-danger}

  * The *Client ID* and *Client Secret* (needed in next steps) are then available :-

<figure>
	<a href="{{ site.url }}/images/setup12.jpg"><img src="{{ site.url }}/images/setup12.jpg"></a>
	<figcaption>Access Client ID and Client Secret.</figcaption>
</figure>

## Notes

* if you're using a firewall or a software that blocks communications, note that you'll have to unblock the following domains to use the workflow:-

  * https://api.spotify.com (for interaction with Spotify)

  * https://raw.githubusercontent.com (for downloading new release from web site)

  * https://github.com/ (for downloading images when changing theme)

  * http://api.stathat.com (for statistics)

* if you're using a proxy, don't forget to enable this option in Alfred preferences, ***except for the time of authentication, it must be disabled*** :-

<figure>
	<a href="{{ site.url }}/images/setup6.jpg"><img src="{{ site.url }}/images/setup6.jpg"></a>
	<figcaption>Use Mac OS http proxy settings for scripts option (except for authentication).</figcaption>
</figure>

* **Wappalyzer** browser extension interfere with authentication process: make sure to disable it during time of authentication.


## Create the library


* Invoke the workflow (with keyword `spot_mini`, or **highly recommended** with an [hotkey]({{ site.url }}/articles/hotkeys) )

* Follow the steps as below by copy/pasting the *Client ID* and *Client Secret* into Alfred window when asked:

<figure>
	<a href="{{ site.url }}/images/setup.gif"><img src="{{ site.url }}/images/setup.gif"></a>
	<figcaption>Paste Client ID and Client Secret (click to enlarge).</figcaption>
</figure>


* Invoke the workflow again and select Authenticate with Spotify, Google Chrome or Firefox ([Safari is not working for authentication](https://github.com/vdesabou/alfred-spotify-mini-player/issues/341)) will open and you'll be prompted to login with Spotify and allow access to your application. At the end you should see a message like this:-

    *Hello xxx ! You are now successfully logged and you can close this window.*

* Invoke the workflow again and Create the library.

* You can then start using the workflow:-

<figure>
	<img src="{{ site.url }}/images/setup2.jpg">
	<figcaption>See progress by invoking workflow.</figcaption>
</figure>

It will create your library in the background, but you'll be able to access use the workflow during that time (except for actions where library has to be modified).

* The artworks are downloaded in the background, you should get a notification when background download starts:-

<figure>
	<img src="{{ site.url }}/images/setup4.jpg">
	<figcaption>Notification for the start of background download of artworks.</figcaption>
</figure>

* During that time, you can use the workflow (you'll see the progress at the top of main menu), and you can see some blank artworks:-

<figure>
	<img src="{{ site.url }}/images/setup5.jpg">
	<figcaption>Example of blank artworks until the end of background download is over.</figcaption>
</figure>



