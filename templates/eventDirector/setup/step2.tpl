{**
 * step3.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 3 of conference setup.
 *
 * $Id$
 *}

{assign var="pageTitle" value="eventDirector.setup.guidingSubmissions}
{include file="eventDirector/setup/setupHeader.tpl"}

<form method="post" action="{url op="saveSetup" path="2"}">
{include file="common/formErrors.tpl"}

<h3>2.1 {translate key="eventDirector.setup.authorRegistration"}</h3>

<p>{translate key="eventDirector.setup.authorRegistrationDescription"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="10%" />
		<td width="90%">
			<input type="checkbox" name="openRegAuthor" id="openRegAuthor" value="1" {if $openRegAuthor}checked="checked"{/if} />
			{fieldLabel name="openRegAuthor" key="eventDirector.setup.openRegAuthorOn"}
			<nobr>
				{html_select_date prefix="openRegAuthorDate" time=$openRegAuthorDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$yearOffsetFuture}
			</nobr>
		</td>
	</tr>
	<tr valign="top">
		<td width="10%" />
		<td width="90%">
			<input type="checkbox" name="closeRegAuthor" id="closeRegAuthor" value="1" {if $closeRegAuthor}checked="checked"{/if} />
			{fieldLabel name="closeRegAuthor" key="eventDirector.setup.closeRegAuthorOn"}
			<nobr>
				{html_select_date prefix="closeRegAuthorDate" time=$closeRegAuthorDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$yearOffsetFuture}
			</nobr>
		</td>
	</tr>
</table>

<h3>2.2 {translate key="eventDirector.setup.submissionContents"}</h3>

<p>{translate key="eventDirector.setup.submissionsDescription"}</p>

	<script type="text/javascript">
		{literal}
		<!--
			function toggleAcceptPapers(form) {
				form.reviewPapers.disabled = !form.acceptPapers.checked || form.collectOrder0.checked;
				form.collectOrder0.disabled = !form.acceptPapers.checked;
				form.collectOrder1.disabled = !form.acceptPapers.checked;
			}
			function toggleCollectOrder(form) {
				form.reviewPapers.disabled = !form.acceptPapers.checked || form.collectOrder0.checked;
			}
		// -->
		{/literal}
	</script>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label">
			<input type="checkbox" name="acceptPapers" id="acceptPapers" value="1" onclick="toggleAcceptPapers(this.form)" {if $acceptPapers}checked="checked"{/if} />
		</td>
		<td width="95%" class="value">
			<label for="acceptPapers">{translate key="eventDirector.setup.acceptPapers"}</label>
		</td>
	</tr>
	<tr>
		<td />
		<td>
			<table width="100%">
	<tr>
		<td width="5%">
			<input type="radio" name="collectPapersWithAbstracts" id="collectOrder0" value="1" onclick="toggleCollectOrder(this.form)" {if !$acceptPapers}disabled="disabled"{/if} {if $collectPapersWithAbstracts}checked="checked"{/if} />
		</td>
		<td width="95%"><label for="collectOrder0">{translate key="eventDirector.setup.submitSimultaneous"}</label></td>
	</tr>
	<tr>
		<td width="5%">
			<input type="radio" name="collectPapersWithAbstracts" id="collectOrder1" value="0" onclick="toggleCollectOrder(this.form)" {if !$acceptPapers}disabled="disabled"{/if} {if !$collectPapersWithAbstracts}checked="checked"{/if} />
		</td>
		<td width="95%"><label for="collectOrder1">{translate key="eventDirector.setup.submitSequential"}</label></td>
	</tr>
	<tr>
		<td />
		<td>
			<table width="100%">
				<tr>
					<td width="5%">
						<input type="checkbox" name="reviewPapers" id="reviewPapers" value="1" onclick="toggleReviewPapers(this.form)" {if !$acceptPapers || $collectPapersWithAbstracts}disabled="disabled"{/if} {if $reviewPapers}checked="checked"{/if} />
					</td>
					<td width="95%"><label for="reviewPapers">{translate key="eventDirector.setup.reviewPapers"}</label></td>
				</tr>
			</td>
		</table>
	</tr>
			</table>
	<tr valign="top">
		<td width="5%" class="label">
			<input type="checkbox" name="acceptSupplementaryReviewMaterials" id="acceptSupplementaryReviewMaterials" value="1" {if $acceptSupplementaryReviewMaterials}checked="checked"{/if} />
		</td>
		<td width="95%" class="value">
			<label for="acceptSupplementaryReviewMaterials">{translate key="eventDirector.setup.acceptSupplementaryReviewMaterials"}</label>
		</td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label">
			<input type="checkbox" name="acceptSupplementaryPublishedMaterials" id="acceptSupplementaryPublishedMaterials" value="1" {if $acceptSupplementaryPublishedMaterials}checked="checked"{/if} />
		</td>
		<td width="95%" class="value">
			<label for="acceptSupplementaryPublishedMaterials">{translate key="eventDirector.setup.acceptSupplementaryPublishedMaterials"}</label>
		</td>
	</tr>
</table>

<div class="separator"></div>

<h3>2.3 {translate key="eventDirector.setup.notifications"}</h3>

<p>{translate key="eventDirector.setup.notifications.description"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td class="label"><input {if !$submissionAckEnabled}disabled="disabled" {/if}type="checkbox" name="copySubmissionAckPrimaryContact" id="copySubmissionAckPrimaryContact" value="true" {if $copySubmissionAckPrimaryContact}checked="checked"{/if}/></td>
		<td class="value">{fieldLabel name="copySubmissionAckPrimaryContact" key="eventDirector.setup.notifications.copyPrimaryContact"}</td>
	</tr>
	<tr valign="top">
		<td class="label"><input {if !$submissionAckEnabled}disabled="disabled" {/if}type="checkbox" name="copySubmissionAckSpecified" id="copySubmissionAckSpecified" value="true" {if $copySubmissionAckSpecified}checked="checked"{/if}/></td>
		<td class="value">{fieldLabel name="copySubmissionAckAddress" key="eventDirector.setup.notifications.copySpecifiedAddress"}&nbsp;&nbsp;<input {if !$submissionAckEnabled}disabled="disabled" {/if}type="text" class="textField" name="copySubmissionAckAddress" value="{$copySubmissionAckAddress|escape}"/></td>
	</tr>
	{if !$submissionAckEnabled}
	<tr valign="top">
		<td>&nbsp;</td>
		{url|assign:"preparedEmailsUrl" op="emails"}
		<td>{translate key="eventDirector.setup.notifications.submissionAckDisabled" preparedEmailsUrl=$preparedEmailsUrl}</td>
	</tr>
	{/if}
</table>


<div class="separator"></div>


<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="setup" escape=false}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
