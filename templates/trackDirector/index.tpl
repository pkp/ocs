{**
 * index.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Track director index.
 *
 * $Id$
 *}

{assign var="pageTitle" value="common.queue.long.$pageToDisplay"}
{url|assign:"currentUrl" page="trackDirector"}

{include file="common/header.tpl"}

<ul class="menu">
	<li{if ($pageToDisplay == "submissionsInReview")} class="current"{/if}><a href="{url path="submissionsInReview"}">{translate key="common.queue.short.submissionsInReview"}</a></li>
	<li{if ($pageToDisplay == "submissionsInEditing")} class="current"{/if}><a href="{url path="submissionsInEditing"}">{translate key="common.queue.short.submissionsInEditing}</a></li>
	<li{if ($pageToDisplay == "submissionsAccepted")} class="current"{/if}><a href="{url path="submissionsAccepted"}">{translate key="common.queue.short.submissionsAccepted}</a></li>
	<li{if ($pageToDisplay == "submissionsArchives")} class="current"{/if}><a href="{url path="submissionsArchives"}">{translate key="common.queue.short.submissionsArchives"}</a></li>
</ul>

<br />

<form method="post" name="submit" action="{url op="submissions" path=$pageToDisplay}">
	{if $track}<input type="hidden" name="track" value="{$track|escape:"quotes"}"/>{/if}
	<select name="searchField" size="1" class="selectMenu">
		{html_options_translate options=$fieldOptions selected=$searchField}
	</select>
	<select name="searchMatch" size="1" class="selectMenu">
		<option value="contains"{if $searchMatch == 'contains'} selected="selected"{/if}>{translate key="form.contains"}</option>
		<option value="is"{if $searchMatch == 'is'} selected="selected"{/if}>{translate key="form.is"}</option>
	</select>
	<input type="text" size="15" name="search" class="textField" value="{$search|escape}" />
	<br/>
	<input type="submit" value="{translate key="common.search"}" class="button" />
</form>
&nbsp;

{include file="trackDirector/$pageToDisplay.tpl"}

<form action="#">
{translate key="track.track"}: <select name="track" class="selectMenu" onchange="location.href='{url path=$pageToDisplay track="TRACK_ID" searchField=$searchField searchMatch=$searchMatch search=$search escape=false}'.replace('TRACK_ID', this.options[this.selectedIndex].value)" size="1">{html_options options=$trackOptions selected=$track}</select>
</form>

{if ($pageToDisplay == "submissionsInReview")}
<br />
<h4>{translate key="common.notes"}</h4>
<p>{translate key="director.submissionReview.notes"}</p>
{/if}

{include file="common/footer.tpl"}
