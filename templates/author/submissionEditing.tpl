{**
 * submissionEditing.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Author's submission editing.
 *
 * $Id$
 *}

{translate|assign:"pageTitleTranslated" key="submission.page.editing" id=$submission->getPaperId()}
{assign var="pageCrumbTitle" value="submission.editing"}
{include file="common/header.tpl"}

<ul class="menu">
	<li><a href="{url op="submission" path=$submission->getPaperId()}">{translate key="submission.summary"}</a></li>
	{if $eventSettings.reviewPapers}
		<li><a href="{url op="submissionReview" path=$submission->getPaperId()|to_array:1}">
			{translate key="submission.abstractReview"}</a>
		</li>
		<li><a href="{url op="submissionReview" path=$submission->getPaperId()|to_array:2}">
			{translate key="submission.paperReview"}</a>
		</li>
	{else}
		<li><a href="{url op="submissionReview" path=$submission->getPaperId()}">{translate key="submission.review"}</a></li>
	{/if}
	<li class="current"><a href="{url op="submissionEditing" path=$submission->getPaperId()}">{translate key="submission.editing"}</a></li>
</ul>

{include file="author/submission/summary.tpl"}

<div class="separator"></div>

{include file="author/submission/layout.tpl"}

{include file="common/footer.tpl"}
