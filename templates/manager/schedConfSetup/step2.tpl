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

{assign var="pageTitle" value="manager.setup.guidingSubmissions}
{include file="manager/schedConfSetup/setupHeader.tpl"}

<form method="post" action="{url op="saveSchedConfSetup" path="2"}">
{include file="common/formErrors.tpl"}

<h3>2.1 {translate key="manager.setup.callForPapers"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="10%" class="label">{fieldLabel name="cfpMessage" key="manager.setup.cfpMessage"}</td>
		<td width="90%" class="value">
			<textarea name="cfpMessage" id="cfpMessage" rows="10" cols="80" class="textArea">{$cfpMessage|escape}</textarea>
			<br />
			<span class="instruct">{translate key="manager.setup.cfpMessageDescription"}</span>
		</td>
	</tr>
</table>

<h3>2.2 {translate key="manager.setup.presenterRegistration"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label">
			<input type="checkbox" name="openRegPresenter" id="openRegPresenter" value="1" {if $openRegPresenter}checked="checked"{/if} />
		</td>
		<td width="95%" class="value">
			{fieldLabel name="openRegPresenter" key="manager.setup.openRegPresenter"}
		</td>
	</tr>
</table>

<h3>2.3 {translate key="manager.setup.submissionContents"}</h3>

<p>{translate key="manager.schedConfSetup.submissionsDescription"}</p>

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
			<label for="acceptPapers">{translate key="manager.setup.acceptPapers"}</label>
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
		<td width="95%"><label for="collectOrder0">{translate key="manager.setup.submitSimultaneous"}</label></td>
	</tr>
	<tr>
		<td width="5%">
			<input type="radio" name="collectPapersWithAbstracts" id="collectOrder1" value="0" onclick="toggleCollectOrder(this.form)" {if !$acceptPapers}disabled="disabled"{/if} {if !$collectPapersWithAbstracts}checked="checked"{/if} />
		</td>
		<td width="95%"><label for="collectOrder1">{translate key="manager.setup.submitSequential"}</label></td>
	</tr>
	<tr>
		<td />
		<td>
			<table width="100%">
				<tr>
					<td width="5%">
						<input type="checkbox" name="reviewPapers" id="reviewPapers" value="1" onclick="toggleReviewPapers(this.form)" {if !$acceptPapers || $collectPapersWithAbstracts}disabled="disabled"{/if} {if $reviewPapers}checked="checked"{/if} />
					</td>
					<td width="95%"><label for="reviewPapers">{translate key="manager.schedConfSetup.reviewPapers"}</label></td>
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
			<label for="acceptSupplementaryReviewMaterials">{translate key="manager.setup.acceptSupplementaryReviewMaterials"}</label>
		</td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label">
			<input type="checkbox" name="acceptSupplementaryPublishedMaterials" id="acceptSupplementaryPublishedMaterials" value="1" {if $acceptSupplementaryPublishedMaterials}checked="checked"{/if} />
		</td>
		<td width="95%" class="value">
			<label for="acceptSupplementaryPublishedMaterials">{translate key="manager.setup.acceptSupplementaryPublishedMaterials"}</label>
		</td>
	</tr>
</table>

<div class="separator"></div>

<h3>2.4 {translate key="manager.setup.notifications"}</h3>

<p>{translate key="manager.setup.notifications.description"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td class="label"><input {if !$submissionAckEnabled}disabled="disabled" {/if}type="checkbox" name="copySubmissionAckPrimaryContact" id="copySubmissionAckPrimaryContact" value="true" {if $copySubmissionAckPrimaryContact}checked="checked"{/if}/></td>
		<td class="value">{fieldLabel name="copySubmissionAckPrimaryContact" key="manager.setup.notifications.copyPrimaryContact"}</td>
	</tr>
	<tr valign="top">
		<td class="label"><input {if !$submissionAckEnabled}disabled="disabled" {/if}type="checkbox" name="copySubmissionAckSpecified" id="copySubmissionAckSpecified" value="true" {if $copySubmissionAckSpecified}checked="checked"{/if}/></td>
		<td class="value">{fieldLabel name="copySubmissionAckAddress" key="manager.setup.notifications.copySpecifiedAddress"}&nbsp;&nbsp;<input {if !$submissionAckEnabled}disabled="disabled" {/if}type="text" class="textField" name="copySubmissionAckAddress" value="{$copySubmissionAckAddress|escape}"/></td>
	</tr>
	{if !$submissionAckEnabled}
	<tr valign="top">
		<td>&nbsp;</td>
		{url|assign:"preparedEmailsUrl" op="emails"}
		<td>{translate key="manager.setup.notifications.submissionAckDisabled" preparedEmailsUrl=$preparedEmailsUrl}</td>
	</tr>
	{/if}
</table>


<div class="separator"></div>


<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="schedConfSetup" escape=false}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
