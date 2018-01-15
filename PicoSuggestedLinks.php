<?php

/**
 * Pico Suggested Links
 *
 * @author  Bigi Lui
 * @link    http://picocms.org
 * @license http://opensource.org/licenses/MIT The MIT License
 * @version 1.0
 */
final class PicoSuggestedLinks extends AbstractPicoPlugin
{
	/**
	 * This plugin is enabled by default?
	 *
	 * @see AbstractPicoPlugin::$enabled
	 * @var boolean
	 */
	protected $enabled = false;

	/**
	 * Stored config
	 */
	protected $config = array();

	/**
	 * If suggested links should be enabled on this page
	 */
	protected $isSuggestedLinksOn = false;

	/**
	 * Stores the Pico-evaluated url for checking against later
	 */
	protected $requestedUrl = '';

	const CACHE_FILE_NAME = 'suggestedLinksCache.php';

	/**
	 * Triggered after Pico has evaluated the request URL
	 *
	 * @see    Pico::getRequestUrl()
	 * @param  string &$url part of the URL describing the requested contents
	 * @return void
	 */
	public function onRequestUrl(&$url)
	{
		$this->requestedUrl = '/'.$url; // Pico omits leading slash, we want it
	}

	/**
	 * Triggered after Pico has read its configuration
	 *
	 * @see    Pico::getConfig()
	 * @param  array &$config array of config variables
	 * @return void
	 */
	public function onConfigLoaded(array &$config)
	{
		$this->config['filename'] = 'suggestedLinks.txt';
		// default on just means Suggested-Links: off has to be specified
		// on pages with a template that has suggested links for it to be off.
		// the page still needs to use a template that has the suggested links
		// module no matter what, otherwise it won't appear.
		$this->config['default'] = 'on';
		$this->config['cache'] = null;
		$this->config['title'] = 'Recommended for you';
		$this->config['analytics'] = false;
		$this->config['fallbackImage'] = null;
		// load custom config if needed
		if (isset($config['PicoSuggestedLinks.filename'])) {
			$this->config['filename'] = $config['PicoSuggestedLinks.filename'];
		}
		if (isset($config['PicoSuggestedLinks.default'])) {
			$this->config['default'] = $config['PicoSuggestedLinks.default'];
		}
		if (isset($config['PicoSuggestedLinks.title'])) {
			$this->config['title'] = $config['PicoSuggestedLinks.title'];
		}
		if (isset($config['PicoSuggestedLinks.analytics'])) {
			$this->config['analytics'] = $config['PicoSuggestedLinks.analytics'];
		}
		if (isset($config['PicoSuggestedLinks.fallbackImage'])) {
			$this->config['fallbackImage'] = $config['PicoSuggestedLinks.fallbackImage'];
		}
		if (isset($config['twig_config']['cache'])) {
			$this->config['cache'] = $config['twig_config']['cache'];
		}
	}

	/**
	 * Triggered when Pico reads its known meta header fields
	 *
	 * @see    Pico::getMetaHeaders()
	 * @param  string[] &$headers list of known meta header
	 *     fields; the array value specifies the YAML key to search for, the
	 *     array key is later used to access the found value
	 * @return void
	 */
	public function onMetaHeaders(array &$headers)
	{
		// your code
		$headers['suggestedlinks'] = 'Suggested-Links';
	}

	/**
	 * Triggered after Pico has parsed the meta header
	 *
	 * @see    Pico::getFileMeta()
	 * @param  string[] &$meta parsed meta data
	 * @return void
	 */
	public function onMetaParsed(array &$meta)
	{
		$globalSetting = strtolower($this->config['default']);
		$localSetting = strtolower($meta['suggestedlinks']);
		if ($globalSetting === 'on') {
			if ($localSetting === 'off') {
				$this->isSuggestedLinksOn = false;
			} else {
				$this->isSuggestedLinksOn = true;
			}
		} else {
			if ($localSetting === 'on') {
				$this->isSuggestedLinksOn = true;
			} else {
				$this->isSuggestedLinksOn = false;
			}
		}
	}

	/**
	 * Triggered before page rendering
	 */
	public function onPageRendering(Twig_Environment &$twig, array &$twigVariables, &$templateName) {
		// put in comment form by filling fb_comment twig variable
		if ($this->isSuggestedLinksOn) {
			if (!empty($this->config['cache'])) {
				// cache enabled
				if (file_exists($this->config['cache'].'/'.self::CACHE_FILE_NAME)) {
					// cached (even if empty data)
					@include($this->config['cache'].'/'.self::CACHE_FILE_NAME);
				} else {
					// not yet cached, read from source
					$suggestedLinksData = $this->loadSuggestedLinks();
					// then cache it
					$cache = "<?php\n\n";
					$cache .= '$suggestedLinksData = '.var_export($suggestedLinksData, true).";\n";
					$fp = fopen($this->config['cache'].'/'.self::CACHE_FILE_NAME, 'w');
					fwrite($fp, $cache);
					fclose($fp);
				}
			} else {
				// cache disabled
				$suggestedLinksData = $this->loadSuggestedLinks();
			}
			if (isset($suggestedLinksData[$this->requestedUrl])) {
				// omit current page from list of suggested links so it won't show it
				unset($suggestedLinksData[$this->requestedUrl]);
			}
			if (!empty($suggestedLinksData)) {
				$this->shuffle_assoc($suggestedLinksData);
				$out = '<div class="suggestedLinks">';
				$out .= '<h4>'.$this->config['title'].'</h4>';
				$i = 0;
				foreach ($suggestedLinksData as $link => $data) {
					if (empty($data['image'])) $data['image'] = $this->config['fallbackImage'];
					if ($this->config['analytics']) {
						$onclick = "onclick=\"ga('send', 'event', 'SuggestedLinks', 'click', '".$link."');\"";
					} else {
						$onclick = '';
					}
					$out .= '<a href="'.$link.'" class="suggestedLinkBox" '.$onclick.'>';
					$out .= '<div style="background-image:url(\''.$data['image'].'\');" class="suggestedLinkImage"></div>';
					$out .= '<div class="suggestedLinkTitle">'.$data['title'].'</div>';
					$out .= '</a>';
					$i++;
					if ($i >= 6) {
						break;
					}
				}
				$out .= '</div>';
				$twigVariables['suggested_links'] = $out;
			} else {
				$twigVariables['suggested_links'] = '';
			}
		} else {
			$twigVariables['suggested_links'] = '';
		}
	}

	/**
	 * Triggered after Pico has rendered the page
	 *
	 * @param  string &$output contents which will be sent to the user
	 * @return void
	 */
	public function onPageRendered(&$output)
	{
		$css = '
<style type="text/css">
div.suggestedLinks { width: 100%; margin: 20px 0 0 0; }
div.suggestedLinks h4 { font-size: 18px; font-weight: bold; color: #666; margin: 0; }
div.suggestedLinks a.suggestedLinkBox { display: inline-block; width: 250px; border: 0; text-decoration: none; margin: 0 20px 20px 0; vertical-align: top; }
div.suggestedLinks a.suggestedLinkBox div.suggestedLinkImage { width: 250px; height: 180px; border-radius: 5px; box-shadow: 2px 2px 1px 0px rgba(0,0,0,0.2); background: transparent center center/cover no-repeat scroll; }
div.suggestedLinks a.suggestedLinkBox div.suggestedLinkTitle { width: 250px; font-size: 18px; line-height: 1; font-weight: normal; color: #333; margin: 5px 0; height: 36px; overflow: hidden; text-overflow: ellipsis; position: relative; }

@media (max-width: 611px) {
	div.suggestedLinks a.suggestedLinkBox { width: 250px; }
	div.suggestedLinks a.suggestedLinkBox div.suggestedLinkImage { width: 250px; height: 180px; }
	div.suggestedLinks a.suggestedLinkBox div.suggestedLinkTitle { width: 250px; }
}
@media (min-width: 612px) and (max-width: 711px) {
	div.suggestedLinks a.suggestedLinkBox { width: 150px; }
	div.suggestedLinks a.suggestedLinkBox div.suggestedLinkImage { width: 150px; height: 100px; }
	div.suggestedLinks a.suggestedLinkBox div.suggestedLinkTitle { width: 150px; }
}
@media (min-width: 712px) and (max-width: 768px) {
	div.suggestedLinks a.suggestedLinkBox { width: 180px; }
	div.suggestedLinks a.suggestedLinkBox div.suggestedLinkImage { width: 180px; height: 135px; }
	div.suggestedLinks a.suggestedLinkBox div.suggestedLinkTitle { width: 180px; }
}
@media (min-width: 769px) and (max-width: 991px) {
	div.suggestedLinks a.suggestedLinkBox { width: 215px; }
	div.suggestedLinks a.suggestedLinkBox div.suggestedLinkImage { width: 215px; height: 160px; }
	div.suggestedLinks a.suggestedLinkBox div.suggestedLinkTitle { width: 215px; }
}
@media (min-width: 992px) {
	div.suggestedLinks a.suggestedLinkBox { width: 250px; }
	div.suggestedLinks a.suggestedLinkBox div.suggestedLinkImage { width: 250px; height: 180px; }
	div.suggestedLinks a.suggestedLinkBox div.suggestedLinkTitle { width: 250px; }
}
@media (max-width:320px) { }
@media (min-width:321px) and (max-width:639px) { }
</style>
';
		// add css to end of <head>
		$output = str_replace('</head>', ($css . '</head>'), $output);
	}

	/**
	 * Meat of the logic.
	 */
	private function loadSuggestedLinks() {
		$linksFile = realpath($this->getConfig('content_dir')) . '/' . $this->config['filename'];
		$src = @file_get_contents($linksFile);
		$links = preg_split('/$\R?^/m', $src);
		$files = array();
		foreach ($links as $link) {
			$link = trim($link);
			if (empty($link)) {
				continue;
			}
			$files[$link] = $this->discoverLink($link);
		}
		if (empty($files)) {
			return array();
		}
		$headers = $this->getMetaHeaders();
		$metas = array();
		foreach ($files as $link => $file) {
			$rawContent = file_get_contents($file);
			try {
				$metas[$link] = $this->parseFileMeta($rawContent, $headers);
			} catch (\Symfony\Component\Yaml\Exception\ParseException $e) {
				$metas[$link] = $this->parseFileMeta('', $headers);
				$metas[$link]['YAML_ParseError'] = $e->getMessage();
			}
		}
		return $metas;
	}

	/**
	 * Got from PHP doc comments for shuffle()
	 */
	private function shuffle_assoc(&$array) {
		$keys = array_keys($array);
		shuffle($keys);
		foreach($keys as $key) {
			$new[$key] = $array[$key];
		}
		$array = $new;
		return true;
	}
  
	/**
	 * Mostly copied from Pico's own discoverRequestedFile()
	 */
	private function discoverLink($requestUrl) {
		$requestUrl = str_replace('\\', '/', $requestUrl);
		$requestUrlParts = explode('/', $requestUrl);

		$requestFileParts = array();
		foreach ($requestUrlParts as $requestUrlPart) {
			if (($requestUrlPart === '') || ($requestUrlPart === '.')) {
				continue;
			} elseif ($requestUrlPart === '..') {
				array_pop($requestFileParts);
				continue;
			}
			$requestFileParts[] = $requestUrlPart;
		}

		// discover the content file to serve
		// Note: $requestFileParts neither contains a trailing nor a leading slash
		$requestFile = $this->getConfig('content_dir') . implode('/', $requestFileParts);
		if (is_dir($requestFile)) {
			// if no index file is found, try a accordingly named file in the previous dir
			// if this file doesn't exist either, show the 404 page, but assume the index
			// file as being requested (maintains backward compatibility to Pico < 1.0)
			$indexFile = $requestFile . '/index' . $this->getConfig('content_ext');
			if (file_exists($indexFile) || !file_exists($requestFile . $this->getConfig('content_ext'))) {
				$requestFile = $indexFile;
				return;
			}
		}
		$requestFile .= $this->getConfig('content_ext');

		return $requestFile;
	}
}
