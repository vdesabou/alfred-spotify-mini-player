# Spotify Web API PHP docs

To build these docs, follow [these steps](http://jekyllrb.com/docs/github-pages/) to get Jekyll up and running.

When done, clone [this repo](https://github.com/jwilsson/phpdoc-md) and then run these commands to build the method reference:

```
phpdoc -d /spotify-web-api-php-master/src -t . --template="xml" --ignore "*Exception.php" --visibility=public
phpdocmd structure.xml /spotify-web-api-php/docs/method-reference
```

Change paths etc. as necessary, of course.
