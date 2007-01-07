{**
 * timelineForm.tpl
 *
 * Copyright (c) 2006-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Timeline management page.
 *
 * NOTE: This template contains hard-coded numbers corresponding to
 *       constants defined in EventConstants.php. Beware of changes
 *       to the following:
 *
 *           * SubmissionState
 *           * PublicationState
 *
 * $Id$
 *}

{assign var="pageTitle" value="director.timeline.eventTimeline"}
{include file="common/header.tpl"}

<br />

<div class="instruct">
	{translate key="director.timeline.description" defaultTimeZone=$defaultTimeZone currentTimeDefaultTimeZone=$currentTimeDefaultTimeZone}
</div>

<br />

<form action="{url op="updateTimeline"}" method="post">

{include file="common/formErrors.tpl"}

<h4>{translate key="director.timeline.overallTimeline"}</h4>

<span class="instruct">{translate key="director.timeline.overallTimelineDescription"}</span><br /><br />

<h5>{translate key="director.timeline.startDate"}</h5>

<table width="100%" class="data">
	<tr valign="top">
		<td width="25%" class="label">{translate key="director.timeline.eventStartsOn"}</td>
		<td width="75%" class="value">
			{html_select_date prefix="startDate" time=$startDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$yearOffsetFuture}
		</td>
	</tr>
</table>

<h5>{translate key="director.timeline.endDate"}</h5>

<table width="100%" class="data">
	<tr valign="top">
		<td width="25%" class="label">{translate key="director.timeline.eventEndsOn"}</td>
		<td width="75%" class="value">
			{html_select_date prefix="endDate" time=$endDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$yearOffsetFuture}
		</td>
	</tr>
</table>

<br/>

<h4>{translate key="director.timeline.userTimeline"}</h4>

<span class="instruct">{translate key="director.timeline.userTimelineDescription"}</span><br /><br />

{if $showAuthorSelfRegister}
<table width="100%" class="data">
	<tr valign="top">
		<td width="25%" class="label">
			{translate key="director.timeline.openRegAuthorOn"}
		</td>
		<td width="75%" class="value">
			<nobr>
				{html_select_date prefix="openRegAuthorDate" time=$openRegAuthorDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$yearOffsetFuture}
			</nobr>
		</td>
	</tr>
	<tr valign="top">
		<td width="25%" class="label">
			{translate key="director.timeline.closeRegAuthorOn"}
		</td>
		<td width="75%" class="value">
			<nobr>
				{html_select_date prefix="closeRegAuthorDate" time=$closeRegAuthorDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$yearOffsetFuture}
			</nobr>
		</td>
	</tr>
</table>
{/if}


<h4>{translate key="director.timeline.submissionTimeline"}</h4>

<span class="instruct">{translate key="director.timeline.submissionTimelineDescription"}</span><br /><br />

<h5>{translate key="director.timeline.CFP"}</h5>

	<script type="text/javascript">
		{literal}
		<!--
			function toggleAcceptSubmissions(form) {
				if (form.submissionState[0].checked) {
					form.autoAccept.disabled = false;
					form.autoShowCFP.disabled = false;
				} else {
					form.autoAccept.disabled = true;
					form.autoShowCFP.disabled = true;
				}
			}
			function toggleAutoRemindAuthors(form) {
				if (form.autoRemindAuthors.checked) {
					form.autoRemindAuthorsDays.disabled = false;
				} else {
					form.autoRemindAuthorsDays.disabled = true;
				}
			}
		// -->
		{/literal}
	</script>


{* "Submissions are currently..." *}

<table width="100%" class="data">
	<tr valign="top">
		<td colspan="3" class="label">{translate key="director.timeline.submissionsCurrently"}</td>
	</tr>
	<tr valign="top">
		<td width="10%" />
		<td colspan="2" class="value">
			<input type="radio" name="submissionState" id="submissionState-0" value="0" onclick="toggleAcceptSubmissions(this.form)"{if $submissionState == 0} checked="checked"{/if} />
			{fieldLabel name="submissionState-0" key="director.timeline.submissions.areNotAcceptedYet"}</td>
		</td>
	</tr>
	<tr valign="top">
		<td width="20%" colspan="2" />
		<td class="value">
			<input type="checkbox" name="autoShowCFP" id="autoShowCFP" value="1" {if $submissionState != 0}disabled="disabled"{/if} {if $autoShowCFP}checked="checked"{/if} />
			{fieldLabel name="autoShowCFP" key="director.timeline.submissions.autoShowCFP"}
			<nobr>
				{html_select_date prefix="showCFPDate" time=$showCFPDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$yearOffsetFuture}
			</nobr>
			{translate key="common.dateTimeSeparator"}
			<nobr>
				{html_select_time use_24_hours=false display_seconds=false prefix="showCFPDate" time=$showCFPDate all_extra="class=\"selectMenu\""}
			</nobr>
		</td>
	</tr>
	<tr valign="top">
		<td width="20%" colspan="2" />
		<td class="value">
			<input type="checkbox" name="autoAccept" id="autoAccept" value="1" {if $submissionState != 0}disabled="disabled"{/if} {if $autoAccept}checked="checked"{/if} />
			{fieldLabel name="autoAccept" key="director.timeline.submissions.autoAccept"}
			<nobr>
				{html_select_date prefix="acceptSubmissionsDate" time=$acceptSubmissionsDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$yearOffsetFuture}
			</nobr>
			{translate key="common.dateTimeSeparator"}
			<nobr>
				{html_select_time use_24_hours=false display_seconds=false prefix="acceptSubmissionsDate" time=$acceptSubmissionsDate all_extra="class=\"selectMenu\""}
			</nobr>
		</td>
	</tr>
	<tr valign="top">
		<td width="10%" />
		<td colspan="2" class="value">
			<input type="radio" name="submissionState" id="submissionState-1" value="1" onclick="toggleAcceptSubmissions(this.form)"{if $submissionState == 1} checked="checked"{/if} />
			{fieldLabel name="submissionState-1" key="director.timeline.submissions.areAccepted"}
		</td>
	</tr>
	<tr valign="top">
		<td width="20%" colspan="2" />
		<td class="value">
			{if $showAbstractDueDate}
				{translate key="director.timeline.abstractDueDate"}
				<nobr>
					{html_select_date prefix="abstractDueDate" time=$abstractDueDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$yearOffsetFuture}
				</nobr>
				{translate key="common.dateTimeSeparator"}
				<nobr>
					{html_select_time use_24_hours=false display_seconds=false prefix="abstractDueDate" time=$abstractDueDate all_extra="class=\"selectMenu\""}
				</nobr>
			{/if}
			{if $showSubmissionDueDate}
				{translate key="director.timeline.submissionDueDate"}
				<nobr>
					{html_select_date prefix="submissionsDueDate" time=$submissionsDueDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$yearOffsetFuture}
				</nobr>
				{translate key="common.dateTimeSeparator"}
				<nobr>
					{html_select_time use_24_hours=false display_seconds=false prefix="submissionDueDate" time=$submissionDueDate all_extra="class=\"selectMenu\""}
				</nobr>
			{/if}
		</td>
	</tr>
	<tr valign="top">
		<td width="10%" />
		<td colspan="2" class="value">
			<input type="radio" name="submissionState" id="submissionState-2" value="2" onclick="toggleAcceptSubmissions(this.form)"{if $submissionState == 2} checked="checked"{/if} />
			{fieldLabel name="submissionState-2" key="director.timeline.submissions.areNoLongerAccepted"}</td>
		</td>
	</tr>
</table>

<h5>{translate key="director.timeline.incompleteSubmissionHandling"}</h5>

{* "Handling Incomplete Submissions..." *}

{if $showPaperDueDate}
<table width="100%" class="data">
	<tr valign="top">
		<td width="25%" class="label">{translate key="director.timeline.paperDueDate"}</td>
		<td width="75%" class="value">
			<nobr>
				{html_select_date prefix="paperDueDate" time=$paperDueDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$yearOffsetFuture}
			</nobr>
			{translate key="common.dateTimeSeparator"}
			<nobr>
				{html_select_time use_24_hours=false display_seconds=false prefix="paperDueDate" time=$paperDueDate all_extra="class=\"selectMenu\""}
			</nobr>
		</td>
	</tr>
</table>
{/if}

<table width="100%" class="data">
	<tr valign="top">
		<td width="10%" />
		<td width="90%">
			<input type="checkbox" name="autoRemindAuthors" id="autoRemindAuthors" value="1" {if $autoRemindAuthors}checked="checked"{/if} onclick="toggleAutoRemindAuthors(this.form)" />
			{fieldLabel name="autoRemindAuthors" key="director.timeline.incomplete.remindAuthorsPre"}
			<input type="text" name="autoRemindAuthorsDays" value="{$autoRemindAuthorsDays|escape}" size="2" maxlength="2" id="autoRemindAuthorsDays" class="textField" {if not $autoRemindAuthors}disabled="disabled"{/if} />
			{translate key="director.timeline.incomplete.remindAuthorsPost"}
		</td>
	</tr>
	<tr valign="top">
		<td width="10%" />
		<td width="90%">
			<input type="checkbox" name="autoArchiveIncompleteSubmissions" id="autoArchiveIncompleteSubmissions" value="1" {if $autoArchiveIncompleteSubmissions}checked="checked"{/if} />
			{fieldLabel name="autoArchiveIncompleteSubmissions" key="director.timeline.incomplete.autoArchiveIncompleteSubmissions"}
		</td>
	</tr>
</table>

<h4>{translate key="director.timeline.publicationTimeline"}</h4>

<span class="instruct">{translate key="director.timeline.publicationTimelineDescription"}</span><br /><br />

<h5>{translate key="director.timeline.publicationStatus"}</h5>

{* "Proceedings are currently..." *}

<table width="100%" class="data">
	<tr valign="top">
		<td width="10%" />
		<td width="90%">
			<input type="checkbox" name="autoReleaseToParticipants" id="autoReleaseToParticipants" value="1" {if $autoReleaseToParticipants}checked="checked"{/if} />
			{fieldLabel name="autoReleaseToParticipants" key="director.timeline.publication.autoReleaseToParticipants"}
			<nobr>
				{html_select_date prefix="autoReleaseToParticipantsDate" time=$autoReleaseToParticipantsDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$yearOffsetFuture}
			</nobr>
		</td>
	</tr>
	<tr valign="top">
		<td width="10%" />
		<td width="90%">
			<input type="checkbox" name="autoReleaseToPublic" id="autoReleaseToPublic" value="1" {if $autoReleaseToPublic}checked="checked"{/if} />
			{fieldLabel name="autoReleaseToPublic" key="director.timeline.publication.autoReleaseToPublic"}
			<nobr>
				{html_select_date prefix="autoReleaseToPublicDate" time=$autoReleaseToPublicDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$yearOffsetFuture}
			</nobr>
		</td>
	</tr>
</table>


<table width="100%" class="data">
	<tr valign="top">
		<td colspan="3" class="label">{translate key="director.timeline.publicationCurrently"}</td>
	</tr>
	<tr valign="top">
		<td width="10%" />
		<td colspan="2" class="value">
			<input type="radio" name="publicationState" id="publicationState-0" value="0" onclick="toggleAcceptSubmissions(this.form)"{if $publicationState == 0} checked="checked"{/if} />
			{fieldLabel name="publicationState-0" key="director.timeline.publication.notReleased"}</td>
		</td>
	</tr>
	<tr valign="top">
		<td width="10%" />
		<td colspan="2" class="value">
			<input type="radio" name="publicationState" id="publicationState-1" value="1" onclick="toggleAcceptSubmissions(this.form)"{if $publicationState == 1} checked="checked"{/if} />
			{fieldLabel name="publicationState-1" key="director.timeline.publication.internallyReleased"}
		</td>
	</tr>
	<tr valign="top">
		<td width="10%" />
		<td colspan="2" class="value">
			<input type="radio" name="publicationState" id="publicationState-2" value="2" onclick="toggleAcceptSubmissions(this.form)"{if $publicationState == 2} checked="checked"{/if} />
			{fieldLabel name="publicationState-2" key="director.timeline.publication.publiclyReleased"}</td>
		</td>
	</tr>
</table>
<br />

<p>
	<input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" />
	<input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="timeline" escape=false}'" /></p>
</p>


</form>

{include file="common/footer.tpl"}
