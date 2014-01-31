{**
 * view.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Paper View.
 *
 * $Id$
 *}
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset={$defaultCharset|escape}" />
	<title>{$paper->getFirstAuthor(true)|escape}</title>

	{if $displayFavicon}<link rel="icon" href="{$faviconDir}/{$displayFavicon.uploadName|escape:"url"}" type="{$displayFavicon.mimeType|escape}" />{/if}

	<link rel="stylesheet" href="{$baseUrl}/lib/pkp/styles/common.css" type="text/css" />
	<link rel="stylesheet" href="{$baseUrl}/styles/common.css" type="text/css" />
	<link rel="stylesheet" href="{$baseUrl}/styles/paperView.css" type="text/css" />

	{foreach from=$stylesheets item=cssUrl}
		<link rel="stylesheet" href="{$cssUrl}" type="text/css" />
	{/foreach}

	<script type="text/javascript" src="{$baseUrl}/lib/pkp/js/general.js"></script>
	{$additionalHeadData}
</head>
<frameset cols="*,180" style="border: 0;">
	{if !$galley}
		{url|assign:"paperUrl" op="viewPaper" path=$paperId}
		{url|assign:"rstUrl" op="viewRST" path=$paperId}
	{else}
		{url|assign:"rstUrl" op="viewRST" path=$paperId|to_array:$galleyId}
		{if $galley->isHtmlGalley()}
			{url|assign:"paperUrl" op="viewPaper" path=$paperId|to_array:$galleyId}
		{elseif $galley->isPdfGalley()}
			{url|assign:"paperUrl" op="viewPDFInterstitial" path=$paperId|to_array:$galleyId}
		{else}
			{url|assign:"paperUrl" op="viewDownloadInterstitial" path=$paperId|to_array:$galleyId}
		{/if}
	{/if}
	<frame src="{$paperUrl}" frameborder="0"/>
	<frame src="{$rstUrl}" noresize="noresize" frameborder="0" scrolling="auto" />
<noframes>
<body>
	<table width="100%">
		<tr>
			<td align="center">
				{translate key="common.error.framesRequired" url=$paperUrl}
			</td>
		</tr>
	</table>
</body>
</noframes>
</frameset>
</html>
