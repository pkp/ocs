{**
 * index.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
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
 
{assign var="pageCrumbTitleTranslated" value=$schedConf->getTitle()}
{assign var="pageTitleTranslated" value=$schedConf->getFullTitle()}
{include file="common/header.tpl"}

<h2>{$schedConf->getSetting('locationName')|nl2br}</h2>
{if $schedConf->getSetting('startDate')}<h2>{$schedConf->getSetting('startDate')|date_format:$dateFormatLong} &ndash; {$schedConf->getSetting('endDate')|date_format:$dateFormatLong}</h2>{/if}

<br />

<div>{$schedConf->getSetting("schedConfIntroduction")|nl2br}</div>

{if $enableAnnouncementsHomepage}
	{* Display announcements *}
	<br />
	<center><h3>{translate key="announcement.announcementsHome"}</h3></center>
	{include file="announcement/list.tpl"}	
	<table width="100%">
		<tr>
			<td>&nbsp;</td>
		<tr>
			<td align="right"><a href="{url page="announcement"}">{translate key="announcement.moreAnnouncements"}</a></td>
		</tr>
	</table>
{/if}

<br />

{if $homepageImage}
<div align="center"><img src="{$publicFilesDir}/{$homepageImage.uploadName|escape}" width="{$homepageImage.width}" height="{$homepageImage.height}" style="border: 0;" alt="" /></div>
{/if}

<h3>{translate key="schedConf.contents"}</h3>

<ul class="plain">
	<li>&#187; <a href="{url page="schedConf" op="overview"}">{translate key="schedConf.overview"}</a></li>
	{if $schedConfShowCFP}
		<li>&#187; <a href="{url page="schedConf" op="cfp"}">{translate key="schedConf.cfp"}</a> ({$submissionOpenDate|date_format:$dateFormatLong} - {$submissionCloseDate|date_format:$dateFormatLong})</li>
	{/if}
	{if $schedConfShowSubmissionLink}
		<li>&#187; <a href="{url page="presenter" op="submit" requiresPresenter="1"}">{translate key="schedConf.proposalSubmission"}</a></li>
	{/if}
	<li>&#187; <a href="{url page="schedConf" op="trackPolicies"}">{translate key="schedConf.trackPolicies"}</a></li>
	{if $schedConfShowProgram}<li>&#187; <a href="{url page="schedConf" op="program"}">{translate key="schedConf.program"}</a></li>{/if}
	<li>&#187; <a href="{url page="schedConf" op="presentations"}">{translate key="schedConf.presentations"}</a></li>
	{if $schedConfPaymentsEnabled}<li>&#187; <a href="{url page="schedConf" op="registration"}">{translate key="schedConf.registration"}</a></li>{/if}
	<li>&#187; <a href="{url page="about" op="organizingTeam"}">{translate key="schedConf.supporters"}</a></li>
	{if $schedConfPostTimeline}<li>&#187; <a href="{url page="schedConf" op="timeline"}">{translate key="schedConf.timeline"}</a></li>{/if}
</ul>

{$additionalHomeContent}

{include file="common/footer.tpl"}
