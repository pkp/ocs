{**
 * templates/author/submissionReview.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Author's submission review.
 *
 *}
{strip}
{if $submission->getReviewMode() == REVIEW_MODE_BOTH_SIMULTANEOUS}
	{assign var="pageCrumbTitle" value="submission.review"}
	{translate|assign:"pageTitleTranslated" key="submission.page.review" id=$submission->getId()}
{elseif $round==REVIEW_ROUND_ABSTRACT}
	{assign var="pageCrumbTitle" value="submission.abstractReview"}
	{translate|assign:"pageTitleTranslated" key="submission.page.abstractReview" id=$submission->getId()}
{else}{* REVIEW_ROUND_PRESENTATION *}
	{assign var="pageCrumbTitle" value="submission.paperReview"}
	{translate|assign:"pageTitleTranslated" key="submission.page.paperReview" id=$submission->getId()}
{/if}
{include file="common/header.tpl"}
{/strip}

<ul class="menu">
	<li><a href="{url op="submission" path=$submission->getId()}">{translate key="submission.summary"}</a></li>
	{if $submission->getReviewMode() == REVIEW_MODE_BOTH_SEQUENTIAL}
		<li {if $round==REVIEW_ROUND_ABSTRACT}class="current"{/if}><a href="{url op="submissionReview" path=$submission->getId()|to_array:$smarty.const.REVIEW_ROUND_ABSTRACT}">
			{translate key="submission.abstractReview"}</a>
		</li>
		<li {if $round==REVIEW_ROUND_PRESENTATION}class="current"{/if}><a href="{url op="submissionReview" path=$submission->getId()|to_array:$smarty.const.REVIEW_ROUND_PRESENTATION}">
			{translate key="submission.paperReview"}</a>
		</li>
	{else}
		<li><a href="{url op="submissionReview" path=$submission->getId()|to_array:$smarty.const.REVIEW_ROUND_ABSTRACT}">{translate key="submission.review"}</a></li>
	{/if}
</ul>


{include file="author/submission/summary.tpl"}

<div class="separator"></div>

{include file="author/submission/peerReview.tpl"}

<div class="separator"></div>

{include file="author/submission/directorDecision.tpl"}

{include file="common/footer.tpl"}

