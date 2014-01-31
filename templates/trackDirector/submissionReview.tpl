{**
 * submissionReview.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Submission review.
 *
 * $Id$
 *}
{strip}
{if $submission->getReviewMode() == $smarty.const.REVIEW_MODE_BOTH_SIMULTANEOUS}
	{translate|assign:"pageTitleTranslated" key="submission.page.review" id=$submission->getPaperId()}
	{assign var="pageCrumbTitle" value="submission.review"}
{elseif $stage==REVIEW_STAGE_ABSTRACT}
	{translate|assign:"pageTitleTranslated" key="submission.page.abstractReview" id=$submission->getPaperId()}
	{assign var="pageCrumbTitle" value="submission.abstractReview"}
{else}{* REVIEW_STAGE_PRESENTATION *}
	{translate|assign:"pageTitleTranslated" key="submission.page.paperReview" id=$submission->getPaperId()}
	{assign var="pageCrumbTitle" value="submission.paperReview"}
{/if}
{include file="common/header.tpl"}
{/strip}

<ul class="menu">
	<li><a href="{url op="submission" path=$submission->getPaperId()}">{translate key="submission.summary"}</a></li>
	{if $submission->getReviewMode() == REVIEW_MODE_BOTH_SEQUENTIAL}
		<li {if $stage==REVIEW_STAGE_ABSTRACT}class="current"{/if}>
			<a href="{url op="submissionReview" path=$submission->getPaperId()|to_array:$smarty.const.REVIEW_STAGE_ABSTRACT}">
				{translate key="submission.abstractReview"}</a>
		</li>
		<li {if $stage==REVIEW_STAGE_PRESENTATION}class="current"{/if}>
			<a href="{url op="submissionReview" path=$submission->getPaperId()|to_array:$smarty.const.REVIEW_STAGE_PRESENTATION}">
				{translate key="submission.paperReview"}</a>
		</li>
	{else}
		<li class="current"><a href="{url op="submissionReview" path=$submission->getPaperId()}">{translate key="submission.review"}</a></li>
	{/if}
	<li><a href="{url op="submissionHistory" path=$submission->getPaperId()}">{translate key="submission.history"}</a></li>
</ul>

{include file="trackDirector/submission/peerReview.tpl"}

<div class="separator"></div>

{include file="trackDirector/submission/directorDecision.tpl"}

{include file="common/footer.tpl"}
