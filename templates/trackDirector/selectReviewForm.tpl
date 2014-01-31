{**
 * assignReviewForm.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Assign a review form to a review.
 *
 *}
{assign var="pageTitle" value="editor.paper.reviewForms"}
{include file="common/header.tpl"}

<h3>{translate key="editor.paper.selectReviewForm"}</h3>

<div id="assignReviewForm">
<table width="100%" class="listing">
	<tr>
		<td class="headseparator" colspan="2">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="85%">{translate key="manager.reviewForms.title"}</td>
		<td width="15%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td class="headseparator" colspan="2">&nbsp;</td>
	</tr>
{iterate from=reviewForms item=reviewForm name=reviewForms}
	<tr valign="top">
		<td>{$reviewForm->getLocalizedTitle()|escape}</td>
		<td class="nowrap">
			{if $assignedReviewFormId == $reviewForm->getId()}{translate key="common.alreadyAssigned"}{else}<a href="{url op="selectReviewForm" path=$paperId|to_array:$reviewId:$reviewForm->getId()}" class="action">{translate key="common.assign"}</a>{/if}&nbsp;|&nbsp;<a href="{url op="previewReviewForm" path=$reviewId|to_array:$reviewForm->getId()}" class="action">{translate key="common.preview"}</a>
	</tr>
	<tr>
		<td colspan="2" class="{if $reviewForms->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}

{if $reviewForms->wasEmpty()}
	<tr>
		<td colspan="2" class="nodata">{translate key="manager.reviewForms.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="2" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td align="left">{page_info iterator=$reviewForms}</td>
		<td align="right">{page_links anchor="reviewForms" name="reviewForms" iterator=$reviewForms}</td>
	</tr>
{/if}
</table>
</div>
{include file="common/footer.tpl"}
