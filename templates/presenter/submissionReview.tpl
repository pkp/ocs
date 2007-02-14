{**
 * submissionReview.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Presenter's submission review.
 *
 * $Id$
 *}

{translate|assign:"pageTitleTranslated" key="submission.page.review" id=$submission->getPaperId()}
{assign var="pageCrumbTitle" value="submission.review"}
{include file="common/header.tpl"}

<ul class="menu">
	<li><a href="{url op="submission" path=$submission->getPaperId()}">{translate key="submission.summary"}</a></li>
	{if $schedConfSettings.reviewPapers}
		<li {if $reviewType==1}class="current"{/if}><a href="{url op="submissionReview" path=$submission->getPaperId()|to_array:1}">
			{translate key="submission.abstractReview"}</a>
		</li>
		<li {if $reviewType==2}class="current"{/if}><a href="{url op="submissionReview" path=$submission->getPaperId()|to_array:2}">
			{translate key="submission.paperReview"}</a>
		</li>
	{else}
		<li><a href="{url op="submissionReview" path=$submission->getPaperId()}">{translate key="submission.review"}</a></li>
	{/if}
	<li><a href="{url op="submissionEditing" path=$submission->getPaperId()}">{translate key="submission.editing"}</a></li>
</ul>


{include file="presenter/submission/summary.tpl"}

<div class="separator"></div>

{include file="presenter/submission/peerReview.tpl"}

<div class="separator"></div>

{include file="presenter/submission/editorDecision.tpl"}

{include file="common/footer.tpl"}
