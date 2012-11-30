{**
 * templates/manager/schedConfSettings.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Basic scheduled schedConf settings under schedConf management.
 *
 *}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#schedConfSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="schedConfSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="grid.settings.schedConf.SchedConfGridHandler" op="updateContext"}">
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="schedConfSettingsNotification"}

	{if $contextId}
		{fbvElement id="contextId" type="hidden" name="contextId" value=$contextId}
	{else}
		<p>{translate key="manager.schedConfs.form.createInstructions"}</p>
	{/if}

	{fbvFormArea id="schedConfSettings"}
		{fbvFormSection title="manager.schedConfs.form.title" required=true for="name"}
			{fbvElement type="text" id="name" value=$name multilingual=true}
		{/fbvFormSection}
		{fbvFormSection title="manager.schedConfs.form.acronym" for="acronym"}
			{fbvElement type="text" id="acronym" value=$acronym multilingual=true}
		{/fbvFormSection}
		{fbvFormSection title="common.path" required=true for="path"}
			{fbvElement type="text" id="path" value=$path size=$smarty.const.SMALL maxlength="32"}
			{url|assign:"sampleUrl" router=$smarty.const.ROUTE_PAGE schedConf="path"}
			{** FIXME: is this class instruct still the right one? **}
			<span class="instruct">{translate key="manager.schedConfs.form.urlWillBe" sampleUrl=$sampleUrl}</span>
		{/fbvFormSection}

		<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
		{fbvFormButtons id="schedConfSettingsFormSubmit" submitText="common.save"}
	{/fbvFormArea}
</form>
