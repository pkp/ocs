{**
 * summary.tpl
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the submission summary table.
 *
 * $Id$
 *}
<a name="submission"></a>
<h3>{translate key="paper.submission"}</h3>

<table width="100%" class="data">
	<tr>
		<td width="20%" class="label">{translate key="paper.authors"}</td>
		<td width="80%">
			{url|assign:"url" page="user" op="email" redirectUrl=$currentUrl to=$submission->getAuthorEmails() subject=$submission->getPaperTitle() paperId=$submission->getPaperId()}
			{$submission->getAuthorString()|escape} {icon name="mail" url=$url}
		</td>
	</tr>
	<tr>
		<td class="label">{translate key="paper.title"}</td>
		<td>{$submission->getPaperTitle()|strip_unsafe_html}</td>
	</tr>
	<tr>
		<td class="label">{translate key="track.track"}</td>
		<td>{$submission->getTrackTitle()|escape}</td>
	</tr>
	<tr>
		<td class="label">{translate key="user.role.director"}</td>
		<td>
			{assign var=editAssignments value=$submission->getEditAssignments()}
			{foreach from=$editAssignments item=editAssignment}
				{assign var=emailString value="`$editAssignment->getDirectorFullName()` <`$editAssignment->getDirectorEmail()`>"}
				{url|assign:"url" page="user" op="email" redirectUrl=$currentUrl to=$emailString|to_array subject=$submission->getPaperTitle()|strip_tags paperId=$submission->getPaperId()}
				{$editAssignment->getDirectorFullName()|escape} {icon name="mail" url=$url}
				<br/>
			{foreachelse}
				{translate key="common.noneAssigned"}
			{/foreach}
		</td>
	</tr>
</table>
