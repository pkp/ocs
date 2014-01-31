{**
 * index.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Scheduled conference index page. Displayed when both a conference and a
 * scheduled conference have been specified.
 *
 * $Id$
 *}
{*
 * The page and crumb titles differ here since the breadcrumbs already include
 * the conference title, but the page title doesn't.
 *}
{strip}
{assign var="pageCrumbTitleTranslated" value=$schedConf->getSchedConfTitle()}
{assign var="pageTitleTranslated" value=$schedConf->getFullTitle()}
{include file="common/header.tpl"}
{/strip}

<h2>{$schedConf->getSetting('locationName')|nl2br}</h2>
{if $schedConf->getSetting('startDate')}
	{assign var=startDate value=$schedConf->getSetting('startDate')|date_format:$dateFormatLong}
	{assign var=endDate value=$schedConf->getSetting('endDate')|date_format:$dateFormatLong}
	<h2>{$startDate}{if $startDate != $endDate} &ndash; {$endDate}{/if}</h2>
{/if}

<br />

<div>{$schedConf->getLocalizedSetting("introduction")|nl2br}</div>

{if $enableAnnouncementsHomepage}
	{* Display announcements *}
	<div id="announcementsHome">
		<h3>{translate key="announcement.announcementsHome"}</h3>
		{include file="announcement/list.tpl"}	
		<table class="announcementsMore">
			<tr>
				<td><a href="{url page="announcement"}">{translate key="announcement.moreAnnouncements"}</a></td>
			</tr>
		</table>
	</div>
{/if}

<br />

{if $homepageImage}
<div id="homepageImage"><img src="{$publicFilesDir}/{$homepageImage.uploadName|escape}" width="{$homepageImage.width}" height="{$homepageImage.height}" {if $homepageImageAltText != ''}alt="{$homepageImageAltText|escape}"{else}alt="{translate key="common.conferenceHomepageImage.altText"}"{/if} /></div>
{/if}

{if $schedConfPostOverview || $schedConfShowCFP
			|| $schedConfPostPolicies || $schedConfShowProgram ||  $schedConfPostPresentations || $schedConfPostSchedule 
			|| $schedConfPostPayment  || $schedConfPostAccommodation || $schedConfPostSupporters  || $schedConfPostTimeline}
<h3>{translate key="schedConf.contents"}</h3>

<ul class="plain">
	{if $schedConfPostOverview}<li>&#187; <a href="{url page="schedConf" op="overview"}">{translate key="schedConf.overview"}</a></li>{/if}
	{if $schedConfShowCFP}
		<li>&#187; <a href="{url page="schedConf" op="cfp"}">{translate key="schedConf.cfp"}</a>{if $submissionsOpenDate} ({$submissionsOpenDate|date_format:$dateFormatLong} - {$submissionsCloseDate|date_format:$dateFormatLong}){/if}</li>
	{/if}
	{if $schedConfPostTrackPolicies}<li>&#187; <a href="{url page="schedConf" op="trackPolicies"}">{translate key="schedConf.trackPolicies"}</a></li>{/if}
	{if $schedConfShowProgram}<li>&#187; <a href="{url page="schedConf" op="program"}">{translate key="schedConf.program"}</a></li>{/if}
	{if $schedConfPostPresentations}<li>&#187; <a href="{url page="schedConf" op="presentations"}">{translate key="schedConf.presentations.short"}</a></li>{/if}
	{if $schedConfPostSchedule}<li>&#187; <a href="{url page="schedConf" op="schedule"}">{translate key="schedConf.schedule"}</a></li>{/if}
	{if $schedConfPostPayment}<li>&#187; <a href="{url page="schedConf" op="registration"}">{translate key="schedConf.registration"}</a></li>{/if}
	{if $schedConfPostAccommodation}<li>&#187; <a href="{url page="schedConf" op="accommodation"}">{translate key="schedConf.accommodation"}</a></li>{/if}
	{if $schedConfPostSupporters}<li>&#187; <a href="{url page="about" op="organizingTeam"}">{translate key="schedConf.supporters"}</a></li>{/if}
	{if $schedConfPostTimeline}<li>&#187; <a href="{url page="schedConf" op="timeline"}">{translate key="schedConf.timeline"}</a></li>{/if}
</ul>
{/if}
{$additionalHomeContent}

{include file="common/footer.tpl"}
