{**
 * block.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site sidebar menu -- navigation links.
 *
 * $Id$
 *}
	<div class="block" id="sidebarNavigation">
		<span class="blockTitle">{translate key="plugins.block.navigation.conferenceContent"}</span>
		
		<span class="blockSubtitle">{translate key="navigation.search"}</span>
		<form method="post" action="{url page="search" op="results"}">
		<table>
		<tr>
			<td><input type="text" id="query" name="query" size="15" maxlength="255" value="" class="textField" /></td>
		</tr>
		<tr>
			<td><select name="searchField" size="1" class="selectMenu">
				{html_options_translate options=$paperSearchByOptions}
			</select></td>
		</tr>
		<tr>
			<td><input type="submit" value="{translate key="common.search"}" class="button" /></td>
		</tr>
		</table>
		</form>
		
		<br />
	
		{if $currentSchedConf && ($schedConfPostOverview || $schedConfShowCFP
			|| $schedConfPostPolicies || $schedConfShowProgram ||  $schedConfPostPresentations || $schedConfPostSchedule 
			|| $schedConfPostPayment  || $schedConfPostAccommodation || $schedConfPostSupporters  || $schedConfPostTimeline)}
		<span class="blockSubtitle">{translate key="schedConf.contents"}</span>

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

		{if $currentConference}
		<span class="blockSubtitle">{translate key="navigation.browse"}</span>
		<ul>
			<li><a href="{url page="search" op="schedConfs"}">{translate key="navigation.browseByConference"}</a></li>
			<li><a href="{url page="search" op="authors"}">{translate key="navigation.browseByAuthor"}</a></li>
			<li><a href="{url page="search" op="titles"}">{translate key="navigation.browseByTitle"}</a></li>
			{if $hasOtherConferences}
			<li><a href="{url conference="index"}">{translate key="navigation.otherConferences"}</a></li>
			{/if}
		</ul>
		{/if}
	</div>

