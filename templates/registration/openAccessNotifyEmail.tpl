{translate key="email.multipart"}

--{$mimeBoundary}
Content-Type: text/plain; charset={$defaultCharset}
Content-Transfer-Encoding: quoted-printable

{$body}

{$issue->getIssueIdentification()}
{translate key="issue.toc"}

{foreach name=tracks from=$publishedPapers item=track key=trackId}
{if $track.title}{$track.title}{/if}
--------
{foreach from=$track.papers item=paper}
{$paper->getPaperTitle()|strip_tags}{if $paper->getPages()} ({$paper->getPages()}){/if}

{foreach from=$paper->getAuthors() item=author name=authorList}
	{$author->getFullName()}{if !$smarty.foreach.authorList.last},{/if}{/foreach}

{/foreach}


{/foreach}
{literal}{$templateSignature}{/literal}

--{$mimeBoundary}
Content-Type: text/html; charset={$defaultCharset}
Content-Transfer-Encoding: quoted-printable

<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={$defaultCharset}" />
		<link rel="stylesheet" href="{$baseUrl}/styles/common.css" type="text/css" />
		{foreach from=$stylesheets item=cssUrl}
		<link rel="stylesheet" href="{$cssUrl}" type="text/css" />
		{/foreach}
		</head>
	<body>

	<p>{$body|escape|nl2br}</p>

		<h3>{$issue->getIssueIdentification()}<br />{translate key="issue.toc"}</h3>
		{foreach name=tracks from=$publishedPapers item=track key=trackId}
			{if $track.title}<h4>{$track.title|escape}</h4>{/if}

			{foreach from=$track.papers item=paper}
				<table width="100%">
					<tr>
						<td>{$paper->getPaperTitle()|strip_unsafe_html}</td>
						<td align="right">
							<a href="{url page="paper" op="view" path=$paper->getBestPaperId($currentEvent)}" class="file">{if $track.abstractsDisabled}{translate key="paper.details"}{else}{translate key="paper.abstract"}{/if}</a>
							{if $mayViewPaper || $paper->getAccessStatus()}
								{foreach from=$paper->getGalleys() item=galley name=galleyList}
									&nbsp;
									<a href="{url page="paper" op="view" path=$paper->getBestPaperId($currentEvent)|to_array:$galley->getGalleyId()}" class="file">{$galley->getLabel()|escape}</a>
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
