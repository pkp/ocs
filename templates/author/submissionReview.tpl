{**
 * submissionReview.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Author's submission review.
 *
 * $Id$
 *}
{strip}
{if $submission->getReviewMode() == REVIEW_MODE_BOTH_SIMULTANEOUS}
	{assign var="pageCrumbTitle" value="submission.review"}
	{translate|assign:"pageTitleTranslated" key="submission.page.review" id=$submission->getPaperId()}
{elseif $stage==REVIEW_STAGE_ABSTRACT}
	{assign var="pageCrumbTitle" value="submission.abstractReview"}
	{translate|assign:"pageTitleTranslated" key="submission.page.abstractReview" id=$submission->getPaperId()}
{else}{* REVIEW_STAGE_PRESENTATION *}
	{assign var="pageCrumbTitle" value="submission.paperReview"}
	{translate|assign:"pageTitleTranslated" key="submission.page.paperReview" id=$submission->getPaperId()}
{/if}
{include file="common/header.tpl"}
{/strip}

<ul class="menu">
	<li><a href="{url op="submission" path=$submission->getPaperId()}">{translate key="submission.summary"}</a></li>
	{if $submission->getReviewMode() == REVIEW_MODE_BOTH_SEQUENTIAL}
		<li {if $stage==REVIEW_STAGE_ABSTRACT}class="current"{/if}><a href="{url op="submissionReview" path=$submission->getPaperId()|to_array:$smarty.const.REVIEW_STAGE_ABSTRACT}">
			{translate key="submission.abstractReview"}</a>
		</li>
		<li {if $stage==REVIEW_STAGE_PRESENTATION}class="current"{/if}><a href="{url op="submissionReview" path=$submission->getPaperId()|to_array:$smarty.const.REVIEW_STAGE_PRESENTATION}">
			{translate key="submission.paperReview"}</a>
		</li>
	{else}
		<li><a href="{url op="submissionReview" path=$submission->getPaperId()|to_array:$smarty.const.REVIEW_STAGE_ABSTRACT}">{translate key="submission.review"}</a></li>
	{/if}
</ul>


{include file="author/submission/summary.tpl"}

<div class="separator"></div>

{include file="author/submission/peerReview.tpl"}

<div class="separator"></div>

{include file="author/submission/directorDecision.tpl"}

{include file="common/footer.tpl"}
