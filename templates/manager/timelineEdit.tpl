{**
 * timelineForm.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Timeline management page.
 *
 *}
{strip}
{assign var="pageTitle" value="manager.timeline.conferenceTimeline"}
{include file="common/header.tpl"}
{/strip}

<br />

<div class="instruct">
	{translate key="manager.timeline.description"}
</div>

<br />

<form class="pkp_form" action="{url op="updateTimeline"}" method="post">

{include file="common/formErrors.tpl"}

<div id="scheduleEvents">
<h3>{translate key="manager.timeline.scheduleEvents"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="50%" class="heading"><h4>{translate key="manager.timeline.conference"}</h4></td>
		<td width="50%" class="heading"><h4>{translate key="manager.timeline.postDate"}</h4></td>
	</tr>
	<tr valign="top">
		<td width="50%" class="label">
			<a name="startDate"></a>
			{translate key="manager.timeline.schedConfStartsOn"}
		</td>
		<td width="50%" class="value">
			{html_select_date prefix="startDate" time=$startDate all_extra="class=\"selectMenu\"" start_year=$firstYear end_year=$lastYear}
		</td>
	</tr>
	<tr valign="top">
		<td width="50%" class="label">
			<a name="endDate"></a>
			{translate key="manager.timeline.schedConfEndsOn"}
		</td>
		<td width="50%" class="value">
			{html_select_date prefix="endDate" time=$endDate all_extra="class=\"selectMenu\"" start_year=$firstYear end_year=$lastYear}
		</td>
	</tr>
</table>
</div>
<br/>

<div id="websiteTimeline">
<table width="100%" class="data">
	<tr valign="top">
		<td width="50%" class="heading"><h4>{translate key="manager.timeline.website"}</h4></td>
		<td width="50%" class="heading"><h4>{translate key="manager.timeline.postDate"}</h4></td>
	</tr>
	<tr valign="top">
		<td width="50%" class="label">
			<a name="siteStartDate"></a>
			{translate key="manager.timeline.schedConfAppearsOn"}
		</td>
		<td width="50%" class="value">
			{html_select_date prefix="siteStartDate" time=$siteStartDate all_extra="class=\"selectMenu\"" start_year=$firstYear end_year=$lastYear}
		</td>
	</tr>
	<tr valign="top">
		<td width="50%" class="label">
			<a name="siteEndDate"></a>
			{translate key="manager.timeline.schedConfArchivedOn"}
		</td>
		<td width="50%" class="value">
			{html_select_date prefix="siteEndDate" time=$siteEndDate all_extra="class=\"selectMenu\"" start_year=$firstYear end_year=$lastYear}
		</td>
	</tr>
</table>
</div>
<br/>

<div id="submissionsTimeline">
<table width="100%" class="data">
<tr valign="top">
		<td width="50%" class="heading"><h4>{translate key="manager.timeline.submissions"}</h4></td>
		<td width="50%" class="heading"><h4>{translate key="manager.timeline.postDate"}</h4></td>
	</tr>
	<tr valign="top">
		<td width="50%" class="label">
			<a name="regAuthorOpenDate"></a>
			{translate key="manager.timeline.openRegAuthor"}
		</td>
		<td width="50%" class="value">
			{html_select_date prefix="regAuthorOpenDate" time=$regAuthorOpenDate all_extra="class=\"selectMenu\"" start_year=$firstYear end_year=$lastYear}
		</td>
	</tr>
	<tr valign="top">
		<td width="50%" class="label">
			<a name="regAuthorCloseDate"></a>
			{translate key="manager.timeline.closeRegAuthor"}
		</td>
		<td width="50%" class="value">
			{html_select_date prefix="regAuthorCloseDate" time=$regAuthorCloseDate all_extra="class=\"selectMenu\"" start_year=$firstYear end_year=$lastYear}
			<input type="hidden" name="regAuthorCloseDateHour" value="23" />
			<input type="hidden" name="regAuthorCloseDateMinute" value="59" />
			<input type="hidden" name="regAuthorCloseDateSecond" value="59" />
		</td>
	</tr>
	<tr valign="top">
		<td width="50%" class="label">
			<a name="showCFPDate"></a>
			{translate key="manager.timeline.showCFP"}
		</td>
		<td width="50%" class="value">
			{html_select_date prefix="showCFPDate" time=$showCFPDate all_extra="class=\"selectMenu\"" start_year=$firstYear end_year=$lastYear}
		</td>
	</tr>
	<tr valign="top">
		<td width="50%" class="label">
			<a name="submissionsOpenDate"></a>
			{translate key="manager.timeline.submissionsOpen"}
		</td>
		<td width="50%" class="value">
			{html_select_date prefix="submissionsOpenDate" time=$submissionsOpenDate all_extra="class=\"selectMenu\"" start_year=$firstYear end_year=$lastYear}
		</td>
	</tr>
	<tr valign="top">
		<td width="50%" class="label">
			<a name="submissionsCloseDate"></a>
			{translate key="manager.timeline.submissionsClosed"}
		</td>
		<td width="50%" class="value">
			{html_select_date prefix="submissionsCloseDate" time=$submissionsCloseDate all_extra="class=\"selectMenu\"" start_year=$firstYear end_year=$lastYear}
			<input type="hidden" name="submissionsCloseDateHour" value="23" />
			<input type="hidden" name="submissionsCloseDateMinute" value="59" />
			<input type="hidden" name="submissionsCloseDateSecond" value="59" />
		</td>
	</tr>
</table>
</div>
<br/>

<div id="reviewsTimeline">
<table width="100%" class="data">
	<tr valign="top">
		<td width="50%" class="heading"><h4>{translate key="manager.timeline.reviews"}</h4></td>
		<td width="50%" class="heading"><h4>{translate key="manager.timeline.postDate"}</h4></td>
	</tr>
	<tr valign="top">
		<td width="50%" class="label">
			<a name="regReviewerOpenDate"></a>
			{translate key="manager.timeline.openRegReviewer"}
		</td>
		<td width="50%" class="value">
			{html_select_date prefix="regReviewerOpenDate" time=$regReviewerOpenDate all_extra="class=\"selectMenu\"" start_year=$firstYear end_year=$lastYear}
		</td>
	</tr>
	<tr valign="top">
		<td width="50%" class="label">
			<a name="regReviewerCloseDate"></a>
			{translate key="manager.timeline.closeRegReviewer"}
		</td>
		<td width="50%" class="value">
			{html_select_date prefix="regReviewerCloseDate" time=$regReviewerCloseDate all_extra="class=\"selectMenu\"" start_year=$firstYear end_year=$lastYear}
			<input type="hidden" name="regReviewerCloseDateHour" value="23" />
			<input type="hidden" name="regReviewerCloseDateMinute" value="59" />
			<input type="hidden" name="regReviewerCloseDateSecond" value="59" />
		</td>
	</tr>
</table>
</div>
<br/>

<div id="websitePosting">
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
		<td class="label" colspan="2">
			<input type="checkbox" name="postTimeline" id="postTimeline" value="1" {if $postTimeline}checked="checked"{/if} />
			{fieldLabel name="postTimeline" key="manager.timeline.postTimeline"}
		</td>
	</tr>
	
	<tr valign="top">
		<td class="label" colspan="2">
			<input type="checkbox" name="postOverview" id="postOverview" value="1" {if $postOverview}checked="checked"{/if} />
			{fieldLabel name="postOverview" key="manager.timeline.postOverview"}
		</td>
	</tr>
	<tr valign="top">
		<td class="label" colspan="2">
			<input type="checkbox" name="postCFP" id="postCFP" value="1" {if $postCFP}checked="checked"{/if} />
			{fieldLabel name="postCFP" key="manager.timeline.postCFP"}
		</td>
	</tr>
	<tr valign="top">
		<td class="label" colspan="2">
			<input type="checkbox" name="postProposalSubmission" id="postProposalSubmission" value="1" {if $postProposalSubmission}checked="checked"{/if} />
			{fieldLabel name="postProposalSubmission" key="manager.timeline.postProposalSubmission"}
		</td>
	</tr>
	<tr valign="top">
		<td class="label" colspan="2">
			<input type="checkbox" name="postTrackPolicies" id="postTrackPolicies" value="1" {if $postTrackPolicies}checked="checked"{/if} />
			{fieldLabel name="postTrackPolicies" key="manager.timeline.postTrackPolicies"}
		</td>
	</tr>
	<tr valign="top">
		<td class="label" colspan="2">
			<input type="checkbox" name="postProgram" id="postProgram" value="1" {if $postProgram}checked="checked"{/if} /> 
			{fieldLabel name="postProgram" key="manager.timeline.postProgram"}
		</td>
	</tr>
	<tr valign="top">
		<td class="label" colspan="2">
			<input type="checkbox" name="postPresentations" id="postPresentations" value="1" {if $postPresentations}checked="checked"{/if} />
			{fieldLabel name="postPresentations" key="manager.timeline.postPresentations"}
		</td>
	</tr>
	<tr valign="top">
		<td class="label" colspan="2">
			<input type="checkbox" name="postAccommodation" id="postAccommodation" value="1" {if $postAccommodation}checked="checked"{/if} />
			{fieldLabel name="postAccommodation" key="manager.timeline.postAccommodation"}
		</td>
	</tr>
	<tr valign="top">
		<td class="label" colspan="2">
			<input type="checkbox" name="postSupporters" id="postSupporters" value="1" {if $postSupporters}checked="checked"{/if} />
			{fieldLabel name="postSupporters" key="manager.timeline.postSupporters"}
		</td>
	</tr>
	<tr valign="top">
		<td class="label" colspan="2">
			<input type="checkbox" name="postPayment" id="postPayment" value="1" {if $postPayment}checked="checked"{/if} />
			{fieldLabel name="postPayment" key="manager.timeline.postRegistration"}
		</td>
	</tr>
	
	<tr valign="top">
		<td width="50%"><h4>{translate key="manager.timeline.include"}</h4></td>
		<td width="50%" class="heading"><h4>{translate key="manager.timeline.postDate"}</h4></td>
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
			<input type="checkbox" name="postAbstracts" id="postAbstracts" value="1" {if $postAbstracts}checked="checked"{/if} />
			{fieldLabel name="postAbstracts" key="manager.timeline.postAbstracts"}
		</td>
		<td width="50%" class="value">
				{html_select_date prefix="postAbstractsDate" time=$postAbstractsDate all_extra="class=\"selectMenu\"" start_year=$firstYear end_year=$lastYear}
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
			<input type="hidden" name="closeCommentsDateHour" value="23" />
			<input type="hidden" name="closeCommentsDateMinute" value="59" />
			<input type="hidden" name="closeCommentsDateSecond" value="59" />
		</td>
	</tr>
	
</table>
</div>
<br/>

<p>
	{if $errorsExist}<input type="checkbox" name="overrideDates" value="1" id="overrideDates" />&nbsp;&nbsp;<label for="overrideDates">{translate key="manager.timeline.overrideDates"}</label><br />{/if}
	<input type="submit" value="{translate key="common.save"}" class="button defaultButton" />
	<input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="index"}'" />
</p>

</form>

{include file="common/footer.tpl"}

