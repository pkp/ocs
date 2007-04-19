{**
 * submission.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Submission summary.
 *
 * $Id$
 *}

{translate|assign:"pageTitleTranslated" key="submission.page.summary" id=$submission->getPaperId()}
{assign var="pageCrumbTitle" value="submission.summary"}
{include file="common/header.tpl"}

<ul class="menu">
	<li class="current"><a href="{url op="submission" path=$submission->getPaperId()}">{translate key="submission.summary"}</a></li>
	{if $schedConfSettings.reviewMode == REVIEW_MODE_BOTH_SEQUENTIAL}
		<li><a href="{url op="submissionReview" path=$submission->getPaperId()|to_array:$smarty.const.REVIEW_STAGE_ABSTRACT}">
			{translate key="submission.abstractReview"}</a>
		</li>
		<li><a href="{url op="submissionReview" path=$submission->getPaperId()|to_array:$smarty.const.REVIEW_STAGE_PRESENTATION}">
			{translate key="submission.paperReview"}</a>
		</li>
	{else}
		<li><a href="{url op="submissionReview" path=$submission->getPaperId()}">{translate key="submission.review"}</a></li>
	{/if}
	<li><a href="{url op="submissionHistory" path=$submission->getPaperId()}">{translate key="submission.history"}</a></li>
</ul>

{include file="trackDirector/submission/management.tpl"}

<div class="separator"></div>

{include file="trackDirector/submission/directors.tpl"}

<div class="separator"></div>

{include file="trackDirector/submission/status.tpl"}

<div class="separator"></div>

{include file="trackDirector/submission/metadata.tpl"}

{include file="common/footer.tpl"}
