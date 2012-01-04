{**
 * navsidebar.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Track Director navigation sidebar.
 *
 * $Id$
 *}
<div class="block" id="sidebarTrackDirector">
	<span class="blockTitle">{translate key="user.role.trackDirector"}</span>
	<span class="blockSubtitle">{translate key="paper.submissions"}</span>
	<ul>
		<li><a href="{url op="index" path="submissionsInReview"}">{translate key="common.queue.short.submissionsInReview"}</a>&nbsp;({if $submissionsCount[0]}{$submissionsCount[0]}{else}0{/if})</li>
		<li><a href="{url op="index" path="submissionsAccepted"}">{translate key="common.queue.short.submissionsAccepted"}</a></li>
		<li><a href="{url op="index" path="submissionsArchives"}">{translate key="common.queue.short.submissionsArchives"}</a></li>
	</ul>
</div>
