{**
 * block.tpl
 *
 * Copyright (c) 2000-2008 John Willinsky
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
	
	{if $currentSchedConf}
		<span class="blockSubtitle">{translate key="schedConf.contents"}</span>

		<ul class="plain">
			<li>&#187; <a href="{url page="schedConf" op="overview"}">{translate key="schedConf.overview"}</a></li>
			{if $schedConfShowCFP}
				<li>&#187; <a href="{url page="schedConf" op="cfp"}">{translate key="schedConf.cfp"}</a>{if $submissionsOpenDate} ({$submissionsOpenDate|date_format:$dateFormatLong} - {$submissionsCloseDate|date_format:$dateFormatLong}){/if}</li>
			{/if}
			{if $schedConfShowSubmissionLink}
				<li>&#187; <a href="{url page="presenter" op="submit" requiresPresenter="1"}">{translate key="schedConf.proposalSubmission"}</a></li>
			{/if}
			<li>&#187; <a href="{url page="schedConf" op="trackPolicies"}">{translate key="schedConf.trackPolicies"}</a></li>
			{if $schedConfShowProgram}<li>&#187; <a href="{url page="schedConf" op="program"}">{translate key="schedConf.program"}</a></li>{/if}
			{if $schedConfShowSchedule}<li>&#187; <a href="{url page="schedConf" op="schedule"}">{translate key="schedConf.schedule"}</a></li>{/if}
			<li>&#187; <a href="{url page="schedConf" op="presentations"}">{translate key="schedConf.presentations.short"}</a></li>
			{if $schedConfPaymentsEnabled}<li>&#187; <a href="{url page="schedConf" op="registration"}">{translate key="schedConf.registration"}</a></li>{/if}
			<li>&#187; <a href="{url page="about" op="organizingTeam"}">{translate key="schedConf.supporters"}</a></li>
			{if $schedConfPostTimeline}<li>&#187; <a href="{url page="schedConf" op="timeline"}">{translate key="schedConf.timeline"}</a></li>{/if}
		</ul>
		{/if}

		{if $currentConference}
		<span class="blockSubtitle">{translate key="navigation.browse"}</span>
		<ul>
			<li><a href="{url page="search" op="schedConfs"}">{translate key="navigation.browseByConference"}</a></li>
			<li><a href="{url page="search" op="presenters"}">{translate key="navigation.browseByPresenter"}</a></li>
			<li><a href="{url page="search" op="titles"}">{translate key="navigation.browseByTitle"}</a></li>
			{if $hasOtherConferences}
			<li><a href="{url conference="index"}">{translate key="navigation.otherConferences"}</a></li>
			{/if}
		</ul>
		{/if}
	</div>

