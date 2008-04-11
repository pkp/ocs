{**
 * timelineForm.tpl
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Timeline management page.
 *
 * $Id$
 *}
{assign var="pageTitle" value="manager.timeline.conferenceTimeline"}
{include file="common/header.tpl"}

<br />

<div class="instruct">
	{translate key="manager.timeline.description"}
</div>

<br />

<form action="{url op="updateTimeline"}" method="post">

{include file="common/formErrors.tpl"}

<h3>{translate key="manager.timeline.conference"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="50%" class="label">{translate key="manager.timeline.schedConfStartsOn"}</td>
		<td width="50%" class="value">
			{html_select_date prefix="startDate" time=$startDate all_extra="class=\"selectMenu\"" start_year=$firstYear end_year=$lastYear}
		</td>
	</tr>
	<tr valign="top">
		<td width="50%" class="label">{translate key="manager.timeline.schedConfEndsOn"}</td>
		<td width="50%" class="value">
			{html_select_date prefix="endDate" time=$endDate all_extra="class=\"selectMenu\"" start_year=$firstYear end_year=$lastYear}
		</td>
	</tr>
</table>

<br/>

<h3>{translate key="manager.timeline.website"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="50%" class="label">{translate key="manager.timeline.schedConfAppearsOn"}</td>
		<td width="50%" class="value">
			{html_select_date prefix="siteStartDate" time=$siteStartDate all_extra="class=\"selectMenu\"" start_year=$firstYear end_year=$lastYear}
		</td>
	</tr>
	<tr valign="top">
		<td width="50%" class="label">{translate key="manager.timeline.schedConfArchivedOn"}</td>
		<td width="50%" class="value">
			{html_select_date prefix="siteEndDate" time=$siteEndDate all_extra="class=\"selectMenu\"" start_year=$firstYear end_year=$lastYear}
		</td>
	</tr>
</table>

<br/>

<h3>{translate key="manager.timeline.submissions"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="50%" class="label">{translate key="manager.timeline.openRegPresenter"}</td>
		<td width="50%" class="value">
			{html_select_date prefix="regPresenterOpenDate" time=$regPresenterOpenDate all_extra="class=\"selectMenu\"" start_year=$firstYear end_year=$lastYear}
		</td>
	</tr>
	<tr valign="top">
		<td width="50%" class="label">{translate key="manager.timeline.closeRegPresenter"}</td>
		<td width="50%" class="value">
			{html_select_date prefix="regPresenterCloseDate" time=$regPresenterCloseDate all_extra="class=\"selectMenu\"" start_year=$firstYear end_year=$lastYear}
		</td>
	<tr valign="top">
		<td width="50%" class="label">{translate key="manager.timeline.showCFP"}</td>
		<td width="50%" class="value">
			{html_select_date prefix="showCFPDate" time=$showCFPDate all_extra="class=\"selectMenu\"" start_year=$firstYear end_year=$lastYear}
		</td>
	</tr>
	<tr valign="top">
		<td width="50%" class="label">{translate key="manager.timeline.submissionsOpen"}</td>
		<td width="50%" class="value">
			{html_select_date prefix="submissionsOpenDate" time=$submissionsOpenDate all_extra="class=\"selectMenu\"" start_year=$firstYear end_year=$lastYear}
		</td>
	</tr>
	<tr valign="top">
		<td width="50%" class="label">{translate key="manager.timeline.submissionsClosed"}</td>
		<td width="50%" class="value">
			{html_select_date prefix="submissionsCloseDate" time=$submissionsCloseDate all_extra="class=\"selectMenu\"" start_year=$firstYear end_year=$lastYear}
		</td>
	</tr>
</table>

<br/>

<h3>{translate key="manager.timeline.reviews"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="50%" class="label">{translate key="manager.timeline.openRegReviewer"}</td>
		<td width="50%" class="value">
			{html_select_date prefix="regReviewerOpenDate" time=$regReviewerOpenDate all_extra="class=\"selectMenu\"" start_year=$firstYear end_year=$lastYear}
		</td>
	</tr>
	<tr valign="top">
		<td width="50%" class="label">{translate key="manager.timeline.closeRegReviewer"}</td>
		<td width="50%" class="value">
			{html_select_date prefix="regReviewerCloseDate" time=$regReviewerCloseDate all_extra="class=\"selectMenu\"" start_year=$firstYear end_year=$lastYear}
		</td>
	</tr>
</table>

<br/>

<h3>{translate key="manager.timeline.websitePosting"}</h3>

<table width="100%" class="data">
	{*
	<tr valign="top">
		<td width="50%" class="label">
			<input type="checkbox" name="postPresentations" id="postPresentations" value="1" {if $postPresentations}checked="checked"{/if} />
			{fieldLabel name="postPresentations" key="manager.timeline.postPresentations"}
		</td>
		<td width="50%" class="value">
				{html_select_date prefix="postPresentationsDate" time=$postPresentationsDate all_extra="class=\"selectMenu\"" start_year=$firstYear end_year=$lastYear}
		</td>
	</tr>
	*}
	<tr valign="top">
		<td width="50%" class="label">
			<input type="checkbox" name="postAbstracts" id="postAbstracts" value="1" {if $postAbstracts}checked="checked"{/if} />
			{fieldLabel name="postAbstracts" key="manager.timeline.postAbstracts"}
		</td>
		<td width="50%" class="value">
				{html_select_date prefix="postAbstractsDate" time=$postAbstractsDate all_extra="class=\"selectMenu\"" start_year=$firstYear end_year=$lastYear}
		</td>
	</tr>
	<tr valign="top">
		<td width="50%" class="label">
			<input type="checkbox" name="postSchedule" id="postSchedule" value="1" {if $postSchedule}checked="checked"{/if} />
			{fieldLabel name="postSchedule" key="manager.timeline.postSchedule"}
		</td>
		<td width="50%" class="value">
				{html_select_date prefix="postScheduleDate" time=$postScheduleDate all_extra="class=\"selectMenu\"" start_year=$firstYear end_year=$lastYear}
		</td>
	</tr>
	<tr valign="top">
		<td width="50%" class="label">
			<input type="checkbox" name="postPapers" id="postPapers" value="1" {if $postPapers}checked="checked"{/if} />
			{fieldLabel name="postPapers" key="manager.timeline.postPapers"}
		</td>
		<td width="50%" class="value">
				{html_select_date prefix="postPapersDate" time=$postPapersDate all_extra="class=\"selectMenu\"" start_year=$firstYear end_year=$lastYear}
		</td>
	</tr>
	<tr valign="top">
		<td width="50%" class="label">
			<input type="checkbox" name="delayOpenAccess" id="delayOpenAccess" value="1" {if $delayOpenAccess}checked="checked"{/if} />
			{fieldLabel name="delayOpenAccess" key="manager.timeline.delayOpenAccess"}
		</td>
		<td width="50%" class="value">
				{html_select_date prefix="delayOpenAccessDate" time=$delayOpenAccessDate all_extra="class=\"selectMenu\"" start_year=$firstYear end_year=$lastYear}
		</td>
	</tr>
	<tr valign="top">
		<td width="50%" class="label">
			<input type="checkbox" name="closeComments" id="closeComments" value="1" {if $closeComments}checked="checked"{/if} />
			{fieldLabel name="closeComments" key="manager.timeline.closeComments"}
		</td>
		<td width="50%" class="value">
				{html_select_date prefix="closeCommentsDate" time=$closeCommentsDate all_extra="class=\"selectMenu\"" start_year=$firstYear end_year=$lastYear}
		</td>
	</tr>
	<tr valign="top">
		<td class="label" colspan="2">
			<input type="checkbox" name="postTimeline" id="postTimeline" value="1" {if $postTimeline}checked="checked"{/if} />
			{fieldLabel name="postTimeline" key="manager.timeline.postTimeline"}
		</td>
	</tr>
</table>

<br/>

<p>
	{if $errorsExist}<input type="checkbox" name="overrideDates" value="1" id="overrideDates" />&nbsp;&nbsp;<label for="overrideDates">{translate key="manager.timeline.overrideDates"}</label><br />{/if}
	<input type="submit" value="{translate key="common.save"}" class="button defaultButton" />
	<input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="index" escape=false}'" /></p>
</p>

</form>

{include file="common/footer.tpl"}
