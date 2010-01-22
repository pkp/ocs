{**
 * index.tpl
 *
 * Copyright (c) 2000-2010 John Willinsky
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
	<li{if ($pageToDisplay == "submissionsAccepted")} class="current"{/if}><a href="{url path="submissionsAccepted"}">{translate key="common.queue.short.submissionsAccepted}</a></li>
	<li{if ($pageToDisplay == "submissionsArchives")} class="current"{/if}><a href="{url path="submissionsArchives"}">{translate key="common.queue.short.submissionsArchives"}</a></li>
</ul>

<form action="#">
<ul class="filter">
	<li>{translate key="director.submissions.inTrack"}: <select name="filterTrack" onchange="location.href='{url|escape path=$pageToDisplay searchField=$searchField searchMatch=$searchMatch search=$search dateFromDay=$dateFromDay dateFromYear=$dateFromYear dateFromMonth=$dateFromMonth dateToDay=$dateToDay dateToYear=$dateToYear dateToMonth=$dateToMonth filterTrack="TRACK_ID" escape=false}'.replace('TRACK_ID', this.options[this.selectedIndex].value)" size="1" class="selectMenu">{html_options options=$trackOptions selected=$filterTrack}</select></li>
</ul>
</form>

<form method="post" name="submit" action="{url op="submissions" path=$pageToDisplay}">
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

{if ($pageToDisplay == "submissionsInReview")}
<br />
<h4>{translate key="common.notes"}</h4>
<p>{translate key="director.submissionReview.notes"}</p>
{/if}

{include file="common/footer.tpl"}
