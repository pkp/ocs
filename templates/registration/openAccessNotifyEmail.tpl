{translate key="email.multipart"}

--{$mimeBoundary}
Content-Type: text/plain; charset={$defaultCharset|escape}
Content-Transfer-Encoding: quoted-printable

{$body}

{$schedConf->getLocalizedTitle()}
{translate key="schedConf.toc"}

{foreach name=tracks from=$publishedPapers item=track key=trackId}
{if $track.title}{$track.title}{/if}
--------
{foreach from=$track.papers item=paper}
{$paper->getLocalizedTitle()|strip_tags}{if $paper->getPages()} ({$paper->getPages()}){/if}

{foreach from=$paper->getAuthors() item=author name=authorList}
	{$author->getFullName()}{if !$smarty.foreach.authorList.last},{/if}{/foreach}

{/foreach}


{/foreach}
{literal}{$templateSignature}{/literal}

--{$mimeBoundary}
Content-Type: text/html; charset={$defaultCharset|escape}
Content-Transfer-Encoding: quoted-printable

<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={$defaultCharset|escape}" />
		<link rel="stylesheet" href="{$baseUrl}/lib/pkp/styles/common.css" type="text/css" />
		<link rel="stylesheet" href="{$baseUrl}/styles/common.css" type="text/css" />
		{foreach from=$stylesheets item=cssUrl}
		<link rel="stylesheet" href="{$cssUrl}" type="text/css" />
		{/foreach}
		</head>
	<body>

	<p>{$body|escape|nl2br}</p>

		<h3>{$schedConf->getLocalizedTitle()}<br />{translate key="schedConf.toc"}</h3>
		{foreach name=tracks from=$publishedPapers item=track key=trackId}
			{if $track.title}<h4>{$track.title|escape}</h4>{/if}

			{foreach from=$track.papers item=paper}
				<table width="100%">
					<tr>
						<td>{$paper->getLocalizedTitle()|strip_unsafe_html}</td>
						<td align="right">
							<a href="{url page="paper" op="view" path=$paper->getBestPaperId($currentSchedConf)}" class="file">{if $paper->getLocalizedAbstract() == ""}{translate key="paper.details"}{else}{translate key="paper.abstract"}{/if}</a>
							{if $mayViewPaper}
								{foreach from=$paper->getGalleys() item=galley name=galleyList}
									&nbsp;
									<a href="{url page="paper" op="view" path=$paper->getBestPaperId($currentSchedConf)|to_array:$galley->getId()}" class="file">{$galley->getGalleyLabel()|escape}</a>
								{/foreach}
							{/if}
						</td>
					</tr>
					<tr>
						<td style="padding-left: 30px;font-style: italic;">
							{foreach from=$paper->getAuthors() item=author name=authorList}
								{$author->getFullName()|escape}{if !$smarty.foreach.authorList.last},{/if}
							{/foreach}
						</td>
						<td align="right">{if $paper->getPages()}{$paper->getPages()|escape}{else}&nbsp;{/if}</td>
						</tr>
					</table>
				{/foreach}
			{if !$smarty.foreach.tracks.last}
				<div class="separator"></div>
			{/if}
		{/foreach}
		<pre>{literal}{$templateSignature}{/literal}</pre>
	</body>
</html>

--{$mimeBoundary}--
