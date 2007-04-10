{**
 * directors.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the submission directors table.
 *
 * $Id$
 *}

<a name="directors"></a>
<h3>{translate key="user.role.directors"}</h3>
<form action="{url op="setDirectorFlags"}" method="post">
<input type="hidden" name="paperId" value="{$submission->getPaperId()}"/>
<table width="100%" class="listing">
	<tr class="heading" valign="bottom">
		<td width="{if $isDirector}20%{else}25%{/if}">&nbsp;</td>
		<td width="30%">&nbsp;</td>
		<td width="10%">{translate key="submission.review"}</td>
		<td width="10%">{translate key="submission.editing"}</td>
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
				{assign var=emailString value="`$editAssignment->getDirectorFullName()` <`$editAssignment->getDirectorEmail()`>"}
				{url|assign:"url" page="user" op="email" redirectUrl=$currentUrl to=$emailString|to_array subject=$submission->getPaperTitle|strip_tags paperId=$submission->getPaperId()}
				{$editAssignment->getDirectorFullName()|escape} {icon name="mail" url=$url}
			</td>
			<td>
				&nbsp;&nbsp;<input
					type="checkbox"
					name="canReview-{$editAssignment->getEditId()}"
					{if $editAssignment->getIsDirector()}
						checked="checked"
						disabled="disabled"
					{else}
						{if $editAssignment->getCanReview()} checked="checked"{/if}
						{if !$isDirector}disabled="disabled"{/if}
					{/if}
				/>
			</td>
			<td>
				&nbsp;&nbsp;<input
					type="checkbox"
					name="canEdit-{$editAssignment->getEditId()}"
					{if $editAssignment->getIsDirector()}
						checked="checked"
						disabled="disabled"
					{else}
						{if $editAssignment->getCanEdit()} checked="checked"{/if}
						{if !$isDirector}disabled="disabled"{/if}
					{/if}
				/>
			</td>
			<td>{if $editAssignment->getDateNotified()}{$editAssignment->getDateNotified()|date_format:$dateFormatShort}{else}&mdash;{/if}</td>
			{if $isDirector}
				<td><a href="{url op="deleteEditAssignment" path=$editAssignment->getEditId()}" class="action">{translate key="common.delete"}</a></td>
			{/if}
		</tr>
	{foreachelse}
		<tr><td colspan="{if $isDirector}6{else}5{/if}" class="nodata">{translate key="common.noneAssigned"}</td></tr>
	{/foreach}
</table>
{if $isDirector}
	<input type="submit" class="button defaultButton" value="{translate key="common.record"}"/>&nbsp;&nbsp;
	<a href="{url op="assignDirector" path="trackDirector" paperId=$submission->getPaperId()}" class="action">{translate key="director.paper.assignTrackDirector"}</a>
	| <a href="{url op="assignDirector" path="director" paperId=$submission->getPaperId()}" class="action">{translate key="director.paper.assignDirector"}</a>
	{if !$selfAssigned}| <a href="{url op="assignDirector" path="director" directorId=$userId paperId=$submission->getPaperId()}" class="action">{translate key="common.addSelf"}</a>{/if}
{/if}
</form>
