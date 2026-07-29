<?php
/**
 * Dependency + version manifest for the block editor script.
 * Ensures wp globals (element, blocks, block-editor, components, SSR, i18n) load first.
 *
 * @package OfnoaPortfolio
 */

return array(
	'dependencies' => array(
		'wp-blocks',
		'wp-element',
		'wp-block-editor',
		'wp-components',
		'wp-server-side-render',
		'wp-i18n',
	),
	'version'      => '1.0.0',
);
