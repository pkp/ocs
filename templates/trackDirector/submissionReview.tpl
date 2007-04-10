{**
 * submissionReview.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Submission review.
 *
 * $Id$
 *}

{if $schedConfSettings.reviewMode == $smarty.const.REVIEW_MODE_BOTH_SIMULTANEOUS}
	{assign var="pageCrumbTitle" value="submission.review"}
	{translate|assign:"pageTitleTranslated" key="submission.page.review" id=$submission->getPaperId()}
{elseif $stage==REVIEW_PROGRESS_ABSTRACT}
	{assign var="pageCrumbTitle" value="submission.abstractReview"}
	{translate|assign:"pageTitleTranslated" key="submission.page.abstractReview" id=$submission->getPaperId()}
{else}{* REVIEW_PROGRESS_PRESENTATION *}
	{assign var="pageCrumbTitle" value="submission.paperReview"}
	{translate|assign:"pageTitleTranslated" key="submission.page.paperReview" id=$submission->getPaperId()}
{/if}

{include file="common/header.tpl"}

<ul class="menu">
	<li><a href="{url op="submission" path=$submission->getPaperId()}">{translate key="submission.summary"}</a></li>
	{if $schedConfSettings.reviewMode == REVIEW_MODE_BOTH_SEQUENTIAL}
		<li {if $stage==REVIEW_PROGRESS_ABSTRACT}class="current"{/if}>
			<a href="{url op="submissionReview" path=$submission->getPaperId()|to_array:$smarty.const.REVIEW_PROGRESS_ABSTRACT}">
				{translate key="submission.abstractReview"}</a>
		</li>
		<li {if $stage==REVIEW_PROGRESS_PRESENTATION}class="current"{/if}>
			<a href="{url op="submissionReview" path=$submission->getPaperId()|to_array:$smarty.const.REVIEW_PROGRESS_PRESENTATION}">
				{translate key="submission.paperReview"}</a>
		</li>
	{else}
		<li class="current"><a href="{url op="submissionReview" path=$submission->getPaperId()}">{translate key="submission.review"}</a></li>
	{/if}
	{if $canEdit}<li><a href="{url op="submissionEditing" path=$submission->getPaperId()}">{translate key="submission.editing"}</a></li>{/if}
	<li><a href="{url op="submissionHistory" path=$submission->getPaperId()}">{translate key="submission.history"}</a></li>
</ul>

{include file="trackDirector/submission/peerReview.tpl"}

<div class="separator"></div>

{include file="trackDirector/submission/directorDecision.tpl"}

{include file="common/footer.tpl"}
