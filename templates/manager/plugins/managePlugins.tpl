{**
 * plugins.tpl
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List available import/export plugins.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="manager.plugins.$path"}
{include file="common/header.tpl"}
{/strip}


{if $path == 'install'}
	{if !$uploaded}
		<p>{translate key="manager.plugins.installDescription"}</p>
	{/if}
	
	<form method="post" action="{url path="installPlugin"}" enctype="multipart/form-data">	
		{if $error}
			<span class="formError">{translate key="form.errorsOccurred"}:</span>
			<ul class="formErrorList">
				<li>
				{if is_array($message)}
					{translate key=$message[0]} {$message[1]}
				{else}
					{translate key=$message}
				{/if}
				</li>
			</ul>
		{/if}
		{if $uploaded}
			<ul class="plain">
				<li>&#187;&nbsp;
				{if is_array($message)}
					{translate key=$message[0]} {$message[1]}
				{else}
					{translate key=$message}
				{/if}
				</li>
			</ul>
		{/if}

		<br />
		<table class="data" width="100%">
		<tr>
			<td width="25%" class="label">
					{translate key="manager.plugins.uploadPluginDir"}
			</td>
			<td width="75%" class="value">
				<input type="file" class="uploadField" name="newPlugin" id="newPlugin" /> 
				<input name="uploadPlugin" type="submit" value="{translate key="common.continue"}" class="button defaultButton" />
			</td>
		</tr>
		</table>
		<p>
	</form>

{elseif $path == 'upgrade'}
	{if !$uploaded}
		<p>{translate key="manager.plugins.upgradeDescription"}</p>
	{/if}
	
	<form method="post" action="{url path="upgradePlugin"|to_array:$plugin}" enctype="multipart/form-data">		
		{if $error}
			<span class="formError">{translate key="form.errorsOccurred"}:</span>
			<ul class="formErrorList">
				{if is_array($message)}
					{translate key=$message[0]} {$message[1]}
				{else}
					{translate key=$message}
				{/if}
			</ul>
		{/if}
		{if $uploaded}
			<ul class="plain">
				<li>&#187;&nbsp;
				{if is_array($message)}
					{translate key=$message[0]} {$message[1]}
				{else}
					{translate key=$message}
				{/if}
				</li>
			</ul>
		{/if}
		
		<br />
		<table class="data" width="100%">
		<tr>
			<td width="25%" class="label">
					{translate key="manager.plugins.uploadPluginDir"}
			</td>
			<td width="75%" class="value">
				<input type="file" class="uploadField" name="newPlugin" id="newPlugin" />
				<input name="uploadPlugin" type="submit" value="{translate key="common.continue"}" class="button defaultButton" />
			</td>
		</tr>
		</table>
		<p>
	</form>
	
{elseif $path == 'delete'}
	{if !$deleted}
		<p>{translate key="manager.plugins.deleteDescription"}</p>
	{/if}
	
	{if !$deleted}
		{if !$error}
			<ul class="formErrorList">
				<li>{translate key="manager.plugins.deleteConfirm"}</li>
			</ul>
		{/if}
	
		<br />
		<form method="post" action="{url path="deletePlugin"|to_array:$plugin}" enctype="multipart/form-data">		
			{if $error}
				<span class="formError">{translate key="form.errorsOccurred"}:</span>
				<ul class="formErrorList">
					<li>
					{if is_array($message)}
						{translate key=$message[0]} {$message[1]}
					{else}
						{translate key=$message}
					{/if}
					</li>
				</ul>
			{/if}
			<input type="submit" name="save" class="button defaultButton" value="{translate key="common.delete"}"/> <input type="button" class="button" value="{translate key="common.cancel"}" onclick="history.go(-1)"/>
		</form>
	{else}
		<p>{translate key="manager.plugins.deleteSuccess"}</p>
	{/if}

{/if}

{include file="common/footer.tpl"}
