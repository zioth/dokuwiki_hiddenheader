<?php
/**
 * hiddenheader Plugin for DokuWiki / action.php
 *
 * This would be easier and more performant as a syntax plugin, but .
 * See the
 *
 *
 * @author  Eli Fenton
 */
 
if (!defined('DOKU_INC')) {die();}
if (!defined('DOKU_PLUGIN')) {define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');}
require_once DOKU_PLUGIN . 'action.php';
 
class action_plugin_hiddenheader extends DokuWiki_Action_Plugin {
	function register(Doku_Event_Handler $controller) {
		$controller->register_hook('PARSER_WIKITEXT_PREPROCESS', 'AFTER', $this, 'handlePreprocess');
		$controller->register_hook('RENDERER_CONTENT_POSTPROCESS', 'BEFORE', $this, 'handlePostProcess');
		$controller->register_hook('TPL_TOC_RENDER', 'BEFORE', $this, 'handleToc');
	}

	function handlePreprocess(&$event, $param) {
		// should accumulate in hash
		if ($this->hidden) {
			return;
		}
		preg_match_all('/==+\%hide\s*([^=]+)/', $event->data, $m);
		$this->hidden = $m && count($m[1]) > 0 ? array_map('trim', $m[1]) : null;

		if ($this->hidden) {
			$event->data = preg_replace('/(==+)\%hide/', '$1', $event->data);
		}
	}

	//TODO Bug: If you use plugin:include to inlcude a page with hidden headers, this processing doesn't happen.
	//		The only way to fix this is to use a syntax plugin, or do it before save
	function handlePostProcess(&$event, $param) {
		if ($this->hidden)
			foreach ($this->hidden as $h) {
				$event->data[1] = preg_replace('/(<h\d[^>]*)(>(?:<a name[^>]*>|)' . trim($h) . '(?:<\/a>|)<\/h\d>)/', '$1 style="display:none"$2', $event->data[1]);
			}
	}

	function handleToc(&$event, $param) {
		if ($this->hidden) {
			$map = array();
			foreach ($this->hidden as $h) {
				$map[trim($h)] = 1;
			}
			// Remove all hidden headers from the TOC.
			$newdata = array();
			foreach ($event->data as $d) {
				if (!$map[$d['hid']]) {
					$newdata[] = $d;
				}
			}

			global $conf;
			$minItemsToShow = $conf['tocminheads'] ?: 0;
			$event->data = count($newdata) < $minItemsToShow ? array() : $newdata;
		}
	}

	var $hidden;
}
