{**
 * block.tpl
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site sidebar menu -- user tools.
 *
 * $Id$
 *}
<div class="block" id="sidebarUser">
	<span class="blockTitle">{translate key="navigation.user"}</span>
	{if $isUserLoggedIn}
		{translate key="plugins.block.user.loggedInAs"}<br />
		<strong>{$loggedInUsername|escape}</strong>

		<ul>
			{if $hasOtherConferences}
				<li><a href="{url conference="index" page="user"}">{translate key="plugins.block.user.myConferences"}</a></li>
			{/if}
			<li><a href="{url page="user" op="profile"}">{translate key="plugins.block.user.myProfile"}</a></li>
			<li><a href="{url page="login" op="signOut"}">{translate key="plugins.block.user.logout"}</a></li>
			{if $userSession->getSessionVar('signedInAs')}
				<li><a href="{url page="manager" op="signOutAsUser"}">{translate key="plugins.block.user.signOutAsUser"}</a></li>
			{/if}
		</ul>
	{else}
		<form method="post" action="{$userBlockLoginUrl}">
			<table>
				<tr>
					<td><label for="sidebar-username">{translate key="user.username"}</label></td>
					<td><input type="text" id="sidebar-username" name="username" value="" size="12" maxlength="32" class="textField" /></td>
				</tr>
				<tr>
					<td><label for="sidebar-password">{translate key="user.password"}</label></td>
					<td><input type="password" id="sidebar-password" name="password" value="{$password|escape}" size="12" maxlength="32" class="textField" /></td>
				</tr>
				<tr>
					<td colspan="2"><input type="checkbox" id="remember" name="remember" value="1" /> <label for="remember">{translate key="plugins.block.user.rememberMe"}</label></td>
				</tr>
				<tr>
					<td colspan="2"><input type="submit" value="{translate key="user.login"}" class="button" /></td>
				</tr>
			</table>
		</form>
	{/if}
</div>
