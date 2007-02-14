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

<h3>{translate key="director.timeline.conference"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="50%" class="label">{translate key="director.timeline.schedConfStartsOn"}</td>
		<td width="50%" class="value">
			{$startDate|date_format:$dateFormatLong}
		</td>
	</tr>
	<tr valign="top">
		<td width="50%" class="label">{translate key="director.timeline.schedConfEndsOn"}</td>
		<td width="50%" class="value">
			{$endDate|date_format:$dateFormatLong}
		</td>
	</tr>
</table>

<br/>

<h3>{translate key="director.timeline.website"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="50%" class="label">{translate key="director.timeline.schedConfAppearsOn"}</td>
		<td width="50%" class="value">
			{$siteStartDate|date_format:$dateFormatLong}
		</td>
	</tr>
	<tr valign="top">
		<td width="50%" class="label">{translate key="director.timeline.schedConfArchivedOn"}</td>
		<td width="50%" class="value">
			{$siteEndDate|date_format:$dateFormatLong}
		</td>
	</tr>
</table>

<br/>

<h3>{translate key="director.timeline.submissions"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="50%" class="label">{translate key="director.timeline.openRegPresenter"}</td>
		<td width="50%" class="value">
			{$regPresenterOpenDate|date_format:$dateFormatLong}
		</td>
	</tr>
	<tr valign="top">
		<td width="50%" class="label">{translate key="director.timeline.closeRegPresenter"}</td>
		<td width="50%" class="value">
			{$regPresenterCloseDate|date_format:$dateFormatLong}
		</td>
	<tr valign="top">
		<td width="50%" class="label">{translate key="director.timeline.showCFP"}</td>
		<td width="50%" class="value">
			{$showCFPDate|date_format:$dateFormatLong}
		</td>
	</tr>
	{if $showProposalsOpenDate}
	<tr valign="top">
		<td width="50%" class="label">{translate key="director.timeline.proposalsOpen"}</td>
		<td width="50%" class="value">
			{$proposalsOpenDate|date_format:$dateFormatLong}
		</td>
	</tr>
	{/if}
	{if $showProposalsCloseDate}
	<tr valign="top">
		<td width="50%" class="label">{translate key="director.timeline.proposalsClosed"}</td>
		<td width="50%" class="value">
			{$proposalsCloseDate|date_format:$dateFormatLong}
		</td>
	</tr>
	{/if}
	{if $showSubmissionsOpenDate}
	<tr valign="top">
		<td width="50%" class="label">{translate key="director.timeline.submissionsOpen"}</td>
		<td width="50%" class="value">
			{$submissionsOpenDate|date_format:$dateFormatLong}
		</td>
	</tr>
	{/if}
	{if $showSubmissionsCloseDate}
	<tr valign="top">
		<td width="50%" class="label">{translate key="director.timeline.submissionsClosed"}</td>
		<td width="50%" class="value">
			{$submissionsCloseDate|date_format:$dateFormatLong}
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
			{$regReviewerOpenDate|date_format:$dateFormatLong}
		</td>
	</tr>
	<tr valign="top">
		<td width="50%" class="label">{translate key="director.timeline.closeRegReviewer"}</td>
		<td width="50%" class="value">
			{$regReviewerCloseDate|date_format:$dateFormatLong}
		</td>
	</tr>
	{*
	<tr valign="top">
		<td width="50%" class="label">{translate key="director.timeline.closeReviewProcess"}</td>
		<td width="50%" class="value">
			{$closeReviewProcessDate|date_format:$dateFormatLong}
		</td>
	</tr>
	*}
	{*<tr valign="top">
		<td width="50%" class="label">{translate key="director.timeline.secondRoundDue"}</td>
		<td width="50%" class="value">
			{$secondRoundDueDate|date_format:$dateFormatLong}
		</td>
	</tr>*}
</table>

<br/>

<h3>{translate key="director.timeline.registration"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="50%" class="label">{translate key="director.timeline.openRegRegistrant"}</td>
		<td width="50%" class="value">
			{$regRegistrantOpenDate|date_format:$dateFormatLong}
		</td>
	</tr>
	<tr valign="top">
		<td width="50%" class="label">{translate key="director.timeline.closeRegRegistrant"}</td>
		<td width="50%" class="value">
			{$regRegistrantCloseDate|date_format:$dateFormatLong}
		</td>
	</tr>
</table>

<br/>

<h3>{translate key="director.timeline.websitePosting"}</h3>

<table width="100%" class="data">
	{*
	<tr valign="top">
		<td width="50%" class="label">
			{fieldLabel name="postPresentations" key="director.timeline.postPresentations"}
		</td>
		<td width="50%" class="value">
			{$postPresentationsDate|date_format:$dateFormatLong}
		</td>
	</tr>
	*}
	<tr valign="top">
		<td width="50%" class="label">
			{fieldLabel name="postAbstracts" key="director.timeline.postAbstracts"}
		</td>
		<td width="50%" class="value">
			{$postAbstractsDate|date_format:$dateFormatLong}
		</td>
	</tr>
	<tr valign="top">
		<td width="50%" class="label">
			{fieldLabel name="postPapers" key="director.timeline.postPapers"}
		</td>
		<td width="50%" class="value">
			{$postPapersDate|date_format:$dateFormatLong}
		</td>
	</tr>
	<tr valign="top">
		<td width="50%" class="label">
			{fieldLabel name="delayOpenAccess" key="director.timeline.delayOpenAccess"}
		</td>
		<td width="50%" class="value">
			{$delayOpenAccessDate|date_format:$dateFormatLong}
		</td>
	</tr>
	{*
	<tr valign="top">
		<td width="50%" class="label">
			{fieldLabel name="closeComments" key="director.timeline.closeComments"}
		</td>
		<td width="50%" class="value">
			{$closeCommentsDate|date_format:$dateFormatLong}
		</td>
	</tr>
	*}
</table>

{include file="common/footer.tpl"}
