{**
 * timelineForm.tpl
 *
 * Copyright (c) 2006-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Timeline management page.
 *
 * $Id$
 *}

{assign var="pageTitle" value="director.timeline.conferenceTimeline"}
{include file="common/header.tpl"}

<br />

<div class="instruct">
	{translate key="director.timeline.description"}
</div>

<br />

<form action="{url op="updateTimeline"}" method="post">

{include file="common/formErrors.tpl"}

<h3>{translate key="director.timeline.conference"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="50%" class="label">{translate key="director.timeline.eventStartsOn"}</td>
		<td width="50%" class="value">
			{html_select_date prefix="startDate" time=$startDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$yearOffsetFuture}
		</td>
	</tr>
	<tr valign="top">
		<td width="50%" class="label">{translate key="director.timeline.eventEndsOn"}</td>
		<td width="50%" class="value">
			{html_select_date prefix="endDate" time=$endDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$yearOffsetFuture}
		</td>
	</tr>
</table>

<br/>

<h3>{translate key="director.timeline.website"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="50%" class="label">{translate key="director.timeline.eventAppearsOn"}</td>
		<td width="50%" class="value">
			{html_select_date prefix="siteStartDate" time=$siteStartDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$yearOffsetFuture}
		</td>
	</tr>
	<tr valign="top">
		<td width="50%" class="label">{translate key="director.timeline.eventArchivedOn"}</td>
		<td width="50%" class="value">
			{html_select_date prefix="siteEndDate" time=$siteEndDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$yearOffsetFuture}
		</td>
	</tr>
</table>

<br/>

<h3>{translate key="director.timeline.submissions"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="50%" class="label">{translate key="director.timeline.openRegAuthor"}</td>
		<td width="50%" class="value">
			{html_select_date prefix="regAuthorOpenDate" time=$regAuthorOpenDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$yearOffsetFuture}
		</td>
	</tr>
	<tr valign="top">
		<td width="50%" class="label">{translate key="director.timeline.closeRegAuthor"}</td>
		<td width="50%" class="value">
			{html_select_date prefix="regAuthorCloseDate" time=$regAuthorCloseDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$yearOffsetFuture}
		</td>
	<tr valign="top">
		<td width="50%" class="label">{translate key="director.timeline.showCFP"}</td>
		<td width="50%" class="value">
			{html_select_date prefix="showCFPDate" time=$showCFPDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$yearOffsetFuture}
		</td>
	</tr>
	{if $showProposalsOpenDate}
	<tr valign="top">
		<td width="50%" class="label">{translate key="director.timeline.proposalsOpen"}</td>
		<td width="50%" class="value">
			{html_select_date prefix="proposalsOpenDate" time=$proposalsOpenDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$yearOffsetFuture}
		</td>
	</tr>
	{/if}
	{if $showProposalsCloseDate}
	<tr valign="top">
		<td width="50%" class="label">{translate key="director.timeline.proposalsClosed"}</td>
		<td width="50%" class="value">
			{html_select_date prefix="proposalsCloseDate" time=$proposalsCloseDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$yearOffsetFuture}
		</td>
	</tr>
	{/if}
	{if $showSubmissionsOpenDate}
	<tr valign="top">
		<td width="50%" class="label">{translate key="director.timeline.submissionsOpen"}</td>
		<td width="50%" class="value">
			{html_select_date prefix="submissionsOpenDate" time=$submissionsOpenDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$yearOffsetFuture}
		</td>
	</tr>
	{/if}
	{if $showSubmissionsCloseDate}
	<tr valign="top">
		<td width="50%" class="label">{translate key="director.timeline.submissionsClosed"}</td>
		<td width="50%" class="value">
			{html_select_date prefix="submissionsCloseDate" time=$submissionsCloseDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$yearOffsetFuture}
		</td>
	</tr>
	{/if}
</table>

<br/>

<h3>{translate key="director.timeline.reviews"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="50%" class="label">{translate key="director.timeline.openRegReviewer"}</td>
		<td width="50%" class="value">
			{html_select_date prefix="regReviewerOpenDate" time=$regReviewerOpenDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$yearOffsetFuture}
		</td>
	</tr>
	<tr valign="top">
		<td width="50%" class="label">{translate key="director.timeline.closeRegReviewer"}</td>
		<td width="50%" class="value">
			{html_select_date prefix="regReviewerCloseDate" time=$regReviewerCloseDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$yearOffsetFuture}
		</td>
	</tr>
	{*
	<tr valign="top">
		<td width="50%" class="label">{translate key="director.timeline.closeReviewProcess"}</td>
		<td width="50%" class="value">
			{html_select_date prefix="closeReviewProcessDate" time=$closeReviewProcessDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$yearOffsetFuture}
		</td>
	</tr>
	*}
	{*<tr valign="top">
		<td width="50%" class="label">{translate key="director.timeline.secondRoundDue"}</td>
		<td width="50%" class="value">
			{html_select_date prefix="secondRoundDueDate" time=$secondRoundDueDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$yearOffsetFuture}
		</td>
	</tr>*}
</table>

<br/>

<h3>{translate key="director.timeline.registration"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="50%" class="label">{translate key="director.timeline.openRegRegistrant"}</td>
		<td width="50%" class="value">
			{html_select_date prefix="regRegistrantOpenDate" time=$regRegistrantOpenDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$yearOffsetFuture}
		</td>
	</tr>
	<tr valign="top">
		<td width="50%" class="label">{translate key="director.timeline.closeRegRegistrant"}</td>
		<td width="50%" class="value">
			{html_select_date prefix="regRegistrantCloseDate" time=$regRegistrantCloseDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$yearOffsetFuture}
		</td>
	</tr>
</table>

<br/>

<h3>{translate key="director.timeline.websitePosting"}</h3>

<table width="100%" class="data">
	{*
	<tr valign="top">
		<td width="50%" class="label">
			<input type="checkbox" name="postPresentations" id="postPresentations" value="1" {if $postPresentations}checked="checked"{/if} />
			{fieldLabel name="postPresentations" key="director.timeline.postPresentations"}
		</td>
		<td width="50%" class="value">
				{html_select_date prefix="postPresentationsDate" time=$postPresentationsDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$yearOffsetFuture}
		</td>
	</tr>
	*}
	<tr valign="top">
		<td width="50%" class="label">
			<input type="checkbox" name="postAbstracts" id="postAbstracts" value="1" {if $postAbstracts}checked="checked"{/if} />
			{fieldLabel name="postAbstracts" key="director.timeline.postAbstracts"}
		</td>
		<td width="50%" class="value">
				{html_select_date prefix="postAbstractsDate" time=$postAbstractsDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$yearOffsetFuture}
		</td>
	</tr>
	<tr valign="top">
		<td width="50%" class="label">
			<input type="checkbox" name="postPapers" id="postPapers" value="1" {if $postPapers}checked="checked"{/if} />
			{fieldLabel name="postPapers" key="director.timeline.postPapers"}
		</td>
		<td width="50%" class="value">
				{html_select_date prefix="postPapersDate" time=$postPapersDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$yearOffsetFuture}
		</td>
	</tr>
	<tr valign="top">
		<td width="50%" class="label">
			<input type="checkbox" name="delayOpenAccess" id="delayOpenAccess" value="1" {if $delayOpenAccess}checked="checked"{/if} />
			{fieldLabel name="delayOpenAccess" key="director.timeline.delayOpenAccess"}
		</td>
		<td width="50%" class="value">
				{html_select_date prefix="delayOpenAccessDate" time=$delayOpenAccessDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$yearOffsetFuture}
		</td>
	</tr>
	{*
	<tr valign="top">
		<td width="50%" class="label">
			<input type="checkbox" name="closeComments" id="closeComments" value="1" {if $closeComments}checked="checked"{/if} />
			{fieldLabel name="closeComments" key="director.timeline.closeComments"}
		</td>
		<td width="50%" class="value">
				{html_select_date prefix="closeCommentsDate" time=$closeCommentsDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$yearOffsetFuture}
		</td>
	</tr>
	*}
</table>

<p>
	<input type="submit" value="{translate key="common.save"}" class="button defaultButton" />
	<input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="timeline" escape=false}'" /></p>
</p>


</form>

{include file="common/footer.tpl"}
