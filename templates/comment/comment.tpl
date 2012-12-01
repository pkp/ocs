{**
 * templates/comment/comment.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Paper reader comment editing
 *
 *}
{strip}
{assign var="pageTitle" value="comments.enterComment"}
{include file="common/header.tpl"}
{/strip}

<script type="text/javascript">
<!--
{literal}
function handleAnonymousCheckbox(theBox) {
	var submitForm = document.getElementById('submit');
	if (theBox.checked) {
		submitForm.posterName.disabled = false;
		submitForm.posterEmail.disabled = false;
		submitForm.posterName.value = "";
		submitForm.posterEmail.value = "";
		submitForm.posterName.focus();
	} else {
		submitForm.posterName.disabled = true;
		submitForm.posterEmail.disabled = true;
		{/literal}{if $commentsAllowAnonymous}
		submitForm.posterName.value = "{$userName|escape}";
		submitForm.posterEmail.value = "{$userEmail|escape}";
		{/if}{literal}
	}
}
// -->
{/literal}
</script>

{include file="common/formErrors.tpl"}

{assign var=parentId value=$parentId|default:"0"}
<div id="comment">
<form class="pkp_form" id="submit" action="{if $commentId}{url op="edit" path=$paperId|to_array:$galleyId:$commentId}{else}{url op="add" path=$paperId|to_array:$galleyId:$parentId:"save"}{/if}" method="post">
<table class="data" width="100%">
	<tr valign="top">
		<td class="label" width="20%"><label for="posterName">{translate key="comments.name"}</label></td>
		<td class="value" width="80%"><input type="text" class="textField" name="posterName" id="posterName" value="{$posterName|escape}" size="40" maxlength="90" /></td>
	</tr>
	<tr valign="top">
		<td class="label"><label for="posterEmail">{translate key="comments.email"}</label></td>
		<td class="value"><input type="text" class="textField" name="posterEmail" id="posterEmail" value="{$posterEmail|escape}" size="40" maxlength="90" /></td>
	</tr>
	{if $isUserLoggedIn && $commentsAllowAnonymous}
	<tr valign="top">
		<td class="label">&nbsp;</td>
		<td class="value">
			<input type="checkbox" name="anonymous" id="anonymous" onclick="handleAnonymousCheckbox(this)">
			<label for="anonymous">{translate key="comments.postAnonymously"}</label>
		</td>
	</tr>
	{/if}
	<tr valign="top">
		<td class="label"><label for="title">{translate key="comments.title"}</label></td>
		<td class="value"><input type="text" class="textField" name="title" id="title" value="{$title|escape}" size="60" maxlength="255" /></td>
	</tr>
	<tr valign="top">
		<td class="label"><label for="bodyField">{translate key="comments.body"}</label></td>
		<td class="value">
			<textarea class="textArea richContent" name="body" id="bodyField" rows="5" cols="60">{$body|escape}</textarea>
		</td>
	</tr>

{if $captchaEnabled}
	<tr valign="top">
		<td class="label" valign="top">{fieldLabel name="recaptcha_challenge_field" required="true" key="common.captchaField"}</td>
		<td class="value">
			{$reCaptchaHtml}
		</td>
	</tr>
{/if}

</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="location.href='{url page="comment" op="view" path=$paperId|to_array:$galleyId:$parentId}';" /></p>

</form>
</div>
{if $commentsClosed}{translate key="comments.commentsClosed" closeCommentsDate=$closeCommentsDate|date_format:$dateFormatShort}<br />{/if}

{include file="common/footer.tpl"}
