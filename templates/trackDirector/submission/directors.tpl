{**
 * templates/trackDirector/submission/directors.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the submission directors table.
 *
 *}
<div id="directors">
<h3>{translate key="user.role.directors"}</h3>
<table width="100%" class="listing">
	<tr class="heading" valign="bottom">
		<td width="{if $isDirector}20%{else}25%{/if}">&nbsp;</td>
		<td>&nbsp;</td>
		<td width="{if $isDirector}20%{else}25%{/if}">{translate key="submission.request"}</td>
		{if $isDirector}<td width="10%">{translate key="common.action"}</td>{/if}
	</tr>
	{assign var=editAssignments value=$submission->getEditAssignments()}
	{foreach from=$editAssignments item=editAssignment name=editAssignments}
	{if $editAssignment->getDirectorId() == $userId}
		{assign var=selfAssigned value=1}
	{/if}
		<tr valign="top">
			<td>{if $editAssignment->getIsDirector()}{translate key="user.role.director"}{else}{translate key="user.role.trackDirector"}{/if}</td>
			<td>
				{assign var=emailString value=$editAssignment->getDirectorFullName()|concat:" <":$editAssignment->getDirectorEmail():">"}
				{url|assign:"url" page="user" op="email" redirectUrl=$currentUrl to=$emailString|to_array subject=$submission->getLocalizedTitle|strip_tags paperId=$submission->getId()}
				{$editAssignment->getDirectorFullName()|escape} {icon name="mail" url=$url}
			</td>
			<td>{if $editAssignment->getDateNotified()}{$editAssignment->getDateNotified()|date_format:$dateFormatShort}{else}&mdash;{/if}</td>
			{if $isDirector}
				<td><a href="{url page="director" op="deleteEditAssignment" path=$editAssignment->getEditId()}" class="action">{translate key="common.delete"}</a></td>
			{/if}
		</tr>
	{foreachelse}
		<tr><td colspan="{if $isDirector}4{else}3{/if}" class="nodata">{translate key="common.noneAssigned"}</td></tr>
	{/foreach}
</table>
{if $isDirector}
	<a href="{url page="director" op="assignDirector" path="trackDirector" paperId=$submission->getId()}" class="action">{translate key="director.paper.assignTrackDirector"}</a>
	|&nbsp;<a href="{url page="director" op="assignDirector" path="director" paperId=$submission->getId()}" class="action">{translate key="director.paper.assignDirector"}</a>
	{if !$selfAssigned}|&nbsp;<a href="{url page="director" op="assignDirector" path="director" directorId=$userId paperId=$submission->getId()}" class="action">{translate key="common.addSelf"}</a>{/if}
{/if}
</div>

