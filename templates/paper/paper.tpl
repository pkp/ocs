{**
 * paper.tpl
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Paper View.
 *
 * $Id$
 *}
{include file="paper/header.tpl"}

	<div id="topBar">
		<div id="paperFontSize">
			{translate key="paper.fontSize"}:&nbsp;
			<a href="#" onclick="setFontSize('{translate|escape:"jsparam" key="paper.fontSize.small.altText"}');" class="icon">{icon path="templates/images/icons/" name="font_small"}</a>&nbsp;
			<a href="#" onclick="setFontSize('{translate|escape:"jsparam" key="paper.fontSize.medium.altText"}');" class="icon">{icon path="templates/images/icons/" name="font_medium"}</a>&nbsp;
			<a href="#" onclick="setFontSize('{translate|escape:"jsparam" key="paper.fontSize.large.altText"}');" class="icon">{icon path="templates/images/icons/" name="font_large"}</a>
		</div>
	</div>
{if $galley}
	{if $galley->isHTMLGalley()}
		{$galley->getHTMLContents()}
	{/if}
{else}

	<h3>{$paper->getPaperTitle()|strip_unsafe_html}</h3>
	<div><em>{$paper->getAuthorString()|escape}</em></div>
	<br />

	<blockquote>
	{if $schedConfPostSchedule}
		{if $room && $building}
			{translate key="manager.scheduler.building"}:&nbsp;{$building->getBuildingName()|escape}<br/>
			{translate key="manager.scheduler.room"}:&nbsp;{$room->getRoomName()|escape}<br/>
		{/if}
		{if $paper->getStartTime()}
			{translate key="common.date"}:&nbsp;{$paper->getStartTime()|date_format:$datetimeFormatShort}&nbsp;&ndash;&nbsp;{$paper->getEndTime()|date_format:$timeFormat}<br/>
		{/if}
	{/if}{* $schedConfPostSchedule *}
	{translate key="submission.lastModified"}:&nbsp;{$paper->getLastModified()|date_format:$dateFormatShort}<br/>
	</blockquote>

	{if $paper->getPaperAbstract()}
	<h4>{translate key="paper.abstract"}</h4>
	<br />
	<div>{$paper->getPaperAbstract()|strip_unsafe_html|nl2br}</div>
	<br />
	{/if}

	{if $mayViewPaper}
		{assign var=galleys value=$paper->getLocalizedGalleys()}
		{if $galleys}
			{translate key="reader.fullText"}
			{assign var="hasPriorAction" value=0}
			{foreach from=$galleys item=galley name=galleyList}
				{if $hasPriorAction}&nbsp;|&nbsp;{/if}
				<a href="{url page="paper" op="view" path=$paperId|to_array:$galley->getGalleyId()}" class="action" target="_parent">{$galley->getGalleyLabel()|escape}</a>
				{assign var="hasPriorAction" value=1}
			{/foreach}
		{/if}
	{elseif $schedConf->getSetting('delayOpenAccess') && $schedConf->getSetting('delayOpenAccessDate') > time()}
		{translate key="reader.fullTextRegistrantsOnlyUntil" date=$schedConf->getSetting('delayOpenAccessDate')|date_format:$dateFormatShort}
	{elseif $schedConf->getSetting('postPapers') && $schedConf->getSetting('postPapersDate') > time()}
		{translate key="reader.fullTextNotPostedYet" date=$schedConf->getSetting('postPapersDate')|date_format:$dateFormatShort}
	{elseif $conference->getSetting('paperAccess') == PAPER_ACCESS_REGISTRATION_REQUIRED}
		{translate key="reader.fullTextRegistrationRequired"}
	{elseif $conference->getSetting('paperAccess') == PAPER_ACCESS_ACCOUNT_REQUIRED && !$isUserLoggedIn}
		{url|assign:"accountUrl" page="user" op="account"}
		{translate key="reader.fullTextAccountRequired" registerUrl=$accountUrl}
	{else}
		{translate key="reader.fullTextNotAvailable"}
	{/if}
{/if}

{include file="paper/comments.tpl"}

{include file="paper/footer.tpl"}
