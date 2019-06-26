<?php
class OGMetaPF {
	public static function onParserFirstCallInit(Parser $parser) {
		$parser->setFunctionHook('metaimage', [ __CLASS__, 'metaImagePF' ]);
		$parser->setFunctionHook('metatitle', [ __CLASS__, 'metaTitlePF' ]);
		$parser->setFunctionHook('metadesc', [ __CLASS__, 'metaDescPF' ]);
	}

	public static function metaImagePF(Parser $parser, $metaImage, $echoback = '') {
		$parserOutput = $parser->getOutput();
		if ($parserOutput->getExtensionData('metaimage') !== null) {
			if ($echoback) {
				return $metaImage;
			}
			return '';
		}
		$file = Title::newFromText($metaImage, NS_FILE);
		if ($file !== null) {
			$parserOutput->setExtensionData('metaimage', $file->getDBkey());
		}
		if ($echoback) {
			return $metaImage;
		}
		return '';
	}

	public static function metaTitlePF(Parser $parser, $metaTitle) {
		$parserOutput = $parser->getOutput();
		if ($parserOutput->getExtensionData('metatitle') !== null) {
			return '';
		}
		$parserOutput->setExtensionData('metatitle', $metaTitle);
		return '';
	}

	public static function metaDescPF(Parser $parser, $metaDesc, $echoback = '') {
		global $wgLang;
		$parserOutput = $parser->getOutput();
		$metaDesc = MessageCache::singleton()->transform(
			$metaDesc, false, $wgLang, $parser->getTitle()
		);
		if ($parserOutput->getExtensionData('metadesc') !== null) {
			if ($echoback) {
				return $metaDesc;
			}
			return '';
		}
		$parserOutput->setExtensionData('metadesc', $metaDesc);
		if ($echoback) {
			return $metaDesc;
		}
		return '';
	}

	public static function onOutputPageParserOutput(OutputPage &$out, ParserOutput $parserOutput) {
		global $wgLogo, $wgSitename, $wgXhtmlNamespaces;

		$ogMetaImage = $parserOutput->getExtensionData('metaimage');
		$ogMetaTitle = $parserOutput->getExtensionData('metatitle');
		$ogMetaDesc = $parserOutput->getExtensionData('metadesc');

		if ($ogMetaImage !== null) {
			$metaImage = wfFindFile(Title::newFromDBkey($ogMetaImage));
		} else {
			$metaImage = false;
		}

		$wgXhtmlNamespaces['og'] = 'http://opengraphprotocol.org/schema/';
		$title = $out->getTitle();
		$isMainpage = $title->isMainPage();

		$meta = [];

		if ($isMainpage) {
			$meta['og:type'] = 'website';
			$meta['og:title'] = $wgSitename;
		} else {
			$meta['og:type'] = 'article';
			$meta['og:site_name'] = $wgSitename;
			$meta['og:title'] = $title->getPrefixedText();
		}

		if ($ogMetaTitle !== null) {
			$meta['og:title'] = $ogMetaTitle;
		}

		if ($metaImage !== false) {
			if (is_object($metaImage)) {
				$meta['og:image'] = wfExpandUrl($metaImage->createThumb(1200, 630));
			} else {
				$meta['og:image'] = $metaImage;
			}
		} else {
			$meta['og:image'] = wfExpandUrl($wgLogo);
		}
		if ($ogMetaDesc !== null) {
			$meta['og:description'] = $ogMetaDesc;
		} else {
			$meta['og:description'] = wfMessage('ogmetapf-default-desc')->text();
		}
		$meta['og:description'] = htmlspecialchars(strip_tags($meta['og:description']), ENT_QUOTES);
		$meta['og:url'] = $title->getFullURL();

		foreach ($meta as $og => $val) {
			if ($val) {
				$out->addHeadItem(
					"meta:property:$og",
					'       ' . Html::element('meta', [
						'property' => $og,
						'content' => $val
					]) . "\n"
				);
			}
		}
	}
}
