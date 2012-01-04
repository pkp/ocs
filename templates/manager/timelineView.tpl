{**
 * timelineForm.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Timeline management page.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="manager.timeline.conferenceTimeline"}
{include file="common/header.tpl"}
{/strip}
<div id="conferenceTimeline">
<h3>{translate key="manager.timeline.conference"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="40%" class="label">{translate key="manager.timeline.schedConfStartsOn"}</td>
		<td width="60%" class="value">
			{$startDate|date_format:$dateFormatLong}
		</td>
	</tr>
	<tr valign="top">
		<td width="40%" class="label">{translate key="manager.timeline.schedConfEndsOn"}</td>
		<td width="60%" class="value">
			{$endDate|date_format:$dateFormatLong}
		</td>
	</tr>
</table>
</div>
<br/>
<div id="websiteTimeline">
<h3>{translate key="manager.timeline.website"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="40%" class="label">{translate key="manager.timeline.schedConfAppearsOn"}</td>
		<td width="60%" class="value">
			{$siteStartDate|date_format:$dateFormatLong}
		</td>
	</tr>
	<tr valign="top">
		<td width="40%" class="label">{translate key="manager.timeline.schedConfArchivedOn"}</td>
		<td width="60%" class="value">
			{$siteEndDate|date_format:$dateFormatLong}
		</td>
	</tr>
</table>
</div>
<br/>
<div id="submissionsTimeline">
<h3>{translate key="manager.timeline.submissions"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="40%" class="label">{translate key="manager.timeline.openRegAuthor"}</td>
		<td width="60%" class="value">
			{$regAuthorOpenDate|date_format:$dateFormatLong}
		</td>
	</tr>
	<tr valign="top">
		<td width="40%" class="label">{translate key="manager.timeline.closeRegAuthor"}</td>
		<td width="60%" class="value">
			{$regAuthorCloseDate|date_format:$dateFormatLong}
		</td>
	</tr>
	<tr valign="top">
		<td width="40%" class="label">{translate key="manager.timeline.showCFP"}</td>
		<td width="60%" class="value">
			{$showCFPDate|date_format:$dateFormatLong}
		</td>
	</tr>
	<tr valign="top">
		<td width="40%" class="label">{translate key="manager.timeline.submissionsOpen"}</td>
		<td width="60%" class="value">
			{$submissionsOpenDate|date_format:$dateFormatLong}
		</td>
	</tr>
	<tr valign="top">
		<td width="40%" class="label">{translate key="manager.timeline.submissionsClosed"}</td>
		<td width="60%" class="value">
			{$submissionsCloseDate|date_format:$dateFormatLong}
		</td>
	</tr>
</table>
</div>
<br/>
<div id="reviewsTimeline">
<h3>{translate key="manager.timeline.reviews"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="40%" class="label">{translate key="manager.timeline.openRegReviewer"}</td>
		<td width="60%" class="value">
			{$regReviewerOpenDate|date_format:$dateFormatLong}
		</td>
	</tr>
	<tr valign="top">
		<td width="40%" class="label">{translate key="manager.timeline.closeRegReviewer"}</td>
		<td width="60%" class="value">
			{$regReviewerCloseDate|date_format:$dateFormatLong}
		</td>
	</tr>
</table>
</div>
{if $postAbstracts || $postSchedule || $postPapers || $delayOpenAccess || $closeComments}

<br/>
<div id="websitePosting">
<h3>{translate key="manager.timeline.websitePosting"}</h3>

<table width="100%" class="data">
	{if $postAbstracts}
		<tr valign="top">
			<td width="40%" class="label">
				{fieldLabel name="postAbstracts" key="manager.timeline.postAbstracts"}
			</td>
			<td width="60%" class="value">
				{$postAbstractsDate|date_format:$dateFormatLong}
			</td>
		</tr>
	{/if}
	{if $postSchedule}
		<tr valign="top">
			<td width="40%" class="label">
				{fieldLabel name="postSchedule" key="manager.timeline.postSchedule"}
			</td>
			<td width="60%" class="value">
				{$postScheduleDate|date_format:$dateFormatLong}
			</td>
		</tr>
	{/if}
	{if $postPapers}
		<tr valign="top">
			<td width="40%" class="label">
				{fieldLabel name="postPapers" key="manager.timeline.postPapers"}
			</td>
			<td width="60%" class="value">
				{$postPapersDate|date_format:$dateFormatLong}
			</td>
		</tr>
	{/if}
	{if $delayOpenAccess}
		<tr valign="top">
			<td width="40%" class="label">
				{fieldLabel name="delayOpenAccess" key="manager.timeline.delayOpenAccess"}
			</td>
			<td width="60%" class="value">
				{$delayOpenAccessDate|date_format:$dateFormatLong}
			</td>
		</tr>
	{/if}
	{if $closeComments}
		<tr valign="top">
			<td width="40%" class="label">
				{fieldLabel name="closeComments" key="manager.timeline.closeComments.plain"}
			</td>
			<td width="60%" class="value">
				{$closeCommentsDate|date_format:$dateFormatLong}
			</td>
		</tr>
	{/if}
</table>
</div>
{/if}

{include file="common/footer.tpl"}
