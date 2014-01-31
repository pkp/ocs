{**
 * navsidebar.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Director navigation sidebar.
 * Lists active assignments and director functions.
 *
 * $Id$
 *}
<div class="block" id="sidebarDirector">
	<span class="blockTitle">{translate key="user.role.director"}</span>
	
	<span class="blockSubtitle">{translate key="paper.submissions"}</span>
	<ul>
		<li><a href="{url op="submissions" path="submissionsUnassigned"}">{translate key="common.queue.short.submissionsUnassigned"}</a>&nbsp;({if $submissionsCount[0]}{$submissionsCount[0]}{else}0{/if})</li>
		<li><a href="{url op="submissions" path="submissionsInReview"}">{translate key="common.queue.short.submissionsInReview"}</a>&nbsp;({if $submissionsCount[1]}{$submissionsCount[1]}{else}0{/if})</li>
		<li><a href="{url op="submissions" path="submissionsAccepted"}">{translate key="common.queue.short.submissionsAccepted"}</a></li>
		<li><a href="{url op="submissions" path="submissionsArchives"}">{translate key="common.queue.short.submissionsArchives"}</a></li>
	</ul>
</div>
