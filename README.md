# PicoSuggestedLinks

A plugin for the Pico CMS to support a "Recommended Links" module like many blogs have.

You can define a list of links as "Recommended Links", which this plugin will display in a module,
randomized every time, a maximum of 6 links with images.

Example:

# Installation

1. Drop this `PicoSuggestedLinks.php` file into your Pico plugin directory.
1. Add the following configuration to your Pico config file (see next section).
1. Add a line into your Pico theme's template file (see the section after next).

# Config

PicoSuggestedLinks uses a few configuration variables. Here is an example using all of them.

```
$config[ 'PicoSuggestedLinks.enabled' ] = true;
$config[ 'PicoSuggestedLinks.filename' ] = 'suggestedLinks.txt'; // use different extension as default .md files
$config[ 'PicoSuggestedLinks.default' ] = 'on'; // on means you have to specifically turn off on pages if needed
$config[ 'PicoSuggestedLinks.analytics' ] = true; // google analytics
$config[ 'PicoSuggestedLinks.title' ] = 'Recommended for you';
$config[ 'PicoSuggestedLinks.fallbackImage' ] = '/themes/default/logo.png';
```

`PicoSuggestedLinks.enabled`  
This enables or disables the plugin completely.

# Theme change

You will have to add a single line to your theme template file, on pages where you want the module to appear:

```
{{ suggested_links }}
```

# Caching

PicoSuggestedLinks utilize the same caching directory that your Pico is set up to use (for Twig).
For example, if you have the following cache configuration with Pico right now:

```
$config['twig_config'] = array(
    'cache' => dirname(__FILE__).'/../cache', // To enable Twig caching change this to a path to a writable directory
    'autoescape' => false,                    // Auto-escape Twig vars or not
    'debug' => false                          // Enable Twig debug or not
);
```
Your Pico CMS is set up to store cache files to a `cache` directory under your Pico root. This
allows Pico to cache files and load pages much faster.

Similarly, PicoSuggestedLinks will use the same setting, and store a cache file into it (named
`suggestedLinksCache.php`, which will allow suggested links to load much faster, because it will
not have to parse every single file on your list of links every time.
