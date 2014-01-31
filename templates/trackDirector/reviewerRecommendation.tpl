{**
 * setDueDate.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to set the due date for a review.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="submission.recommendation"}
{include file="common/header.tpl"}
{/strip}

<h3>{translate key="director.paper.enterReviewerRecommendation"}</h3>

<br />

<form method="post" action="{url op="enterReviewerRecommendation"}">
<input type="hidden" name="paperId" value="{$paperId|escape}" />
<input type="hidden" name="reviewId" value="{$reviewId|escape}" />
<table width="100%" class="data">
<tr valign="top">
	<td width="20%" class="label">{translate key="director.paper.recommendation"}</td>
	<td width="80%" class="value">
		<select name="recommendation" size="1" class="selectMenu">
			{html_options_translate options=$reviewerRecommendationOptions}
		</select>
	</td>
</tr>
</table>
<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="submissionReview" path=$paperId}';"/></p>
</form>

{include file="common/footer.tpl"}
