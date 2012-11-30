{**
 * templates/admin/conferenceSettings.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Basic conference settings under site administration.
 *
 *}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#conferenceSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="conferenceSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="grid.admin.conference.ConferenceGridHandler" op="updateContext"}">
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="conferenceSettingsNotification"}

	{if $contextId}
		{fbvElement id="contextId" type="hidden" name="contextId" value=$contextId}
	{else}
		<p>{translate key="admin.conferences.createInstructions"}</p>
	{/if}

	{fbvFormArea id="conferenceSettings"}
		{fbvFormSection title="manager.setup.conferenceTitle" required=true for="name"}
			{fbvElement type="text" id="name" value=$name multilingual=true}
		{/fbvFormSection}
		{fbvFormSection title="admin.conferences.conferenceDescription" for="description"}
			{fbvElement type="textarea" id="description" value=$description multilingual=true rich=true}
		{/fbvFormSection}
		{fbvFormSection title="common.path" required=true for="path"}
			{fbvElement type="text" id="path" value=$path size=$smarty.const.SMALL maxlength="32"}
			{url|assign:"sampleUrl" router=$smarty.const.ROUTE_PAGE conference="path"}
			{** FIXME: is this class instruct still the right one? **}
			<span class="instruct">{translate key="admin.conferences.urlWillBe" sampleUrl=$sampleUrl}</span>
		{/fbvFormSection}
		{fbvFormSection for="enabled" list=true}
			{if $enabled}{assign var="enabled" value="checked"}{/if}
			{fbvElement type="checkbox" id="enabled" checked=$enabled value="1" label="admin.conferences.enableConferenceInstructions"}
		{/fbvFormSection}

		<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
		{fbvFormButtons id="conferenceSettingsFormSubmit" submitText="common.save"}
	{/fbvFormArea}
</form>
