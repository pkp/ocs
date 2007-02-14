{**
 * submissionRegrets.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show submission regrets/cancels/earlier rounds
 *
 *
 * $Id$
 *}

{translate|assign:"pageTitleTranslated" key="trackDirector.regrets.title" paperId=$submission->getPaperId()}
{assign var=pageTitleTranslated value=$pageTitleTranslated|escape}
{assign var="pageCrumbTitle" value="trackDirector.regrets.breadcrumb"}
{include file="common/header.tpl"}

<ul class="menu">
	<li><a href="{url op="submission" path=$submission->getPaperId()}">{translate key="submission.summary"}</a></li>
	{if $schedConfSettings.reviewPapers and $canReview}
		<li><a href="{url op="submissionReview" path=$submission->getPaperId()|to_array:1}">
			{translate key="submission.abstractReview"}</a>
		</li>
		<li><a href="{url op="submissionReview" path=$submission->getPaperId()|to_array:2}">
			{translate key="submission.paperReview"}</a>
		</li>
	{elseif $canReview}
		<li><a href="{url op="submissionReview" path=$submission->getPaperId()}">{translate key="submission.review"}</a></li>
	{/if}
	{if $canEdit}<li><a href="{url op="submissionEditing" path=$submission->getPaperId()}">{translate key="submission.editing"}</a></li>{/if}
	<li class="current"><a href="{url op="submissionHistory" path=$submission->getPaperId()}">{translate key="submission.history"}</a></li>
</ul>

{include file="trackDirector/submission/summary.tpl"}

<div class="separator"></div>

{include file="trackDirector/submission/rounds.tpl"}

{include file="common/footer.tpl"}
