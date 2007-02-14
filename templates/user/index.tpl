{**
 * index.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * User index.
 *
 * $Id$
 *}

{assign var="pageTitle" value="user.userHome"}
{include file="common/header.tpl"}

{if $showAllConferences}

<h3>{translate key="user.myConferences"}</h3>

{if $isSiteAdmin}
<h4><a href="{url page="user"}">{$siteTitle|escape}</a></h4>
<ul class="plain">
	<li>&#187; <a href="{url conference="index" page=$isSiteAdmin->getRolePath()}">{translate key=$isSiteAdmin->getRoleName()}</a></li>
	{call_hook name="Templates::User::Index::Site"}
</ul>
{/if}

{foreach from=$userConferences item=conference}
<h4><a href="{url conference=$conference->getPath() page="user"}">{$conference->getTitle()|escape}</a></h4>
	<ul class="plain">
	{assign var="conferenceId" value=$conference->getConferenceId()}

	{* Iterate over conference roles *}
	
	{foreach item=role from=$userRoles[$conferenceId]}
		{if $role->getRolePath() != 'reader'}
			<li>&#187; <a href="{url conference=$conference->getPath() schedConf="index" page=$role->getRolePath()}">{translate key=$role->getRoleName()}</a></li>
		{/if}
	{/foreach}

	{* Iterate over scheduled conference roles *}
	
	{foreach from=$userSchedConfs[$conferenceId] item=schedConf}
		{assign var="schedConfId" value=$schedConf->getSchedConfId()}
		<h5>{$schedConf->getTitle()|escape}</h5>

		{foreach item=role from=$userSchedConfRoles[$schedConfId]}
			{if $role->getRolePath() != 'reader'}
				<li>&#187; <a href="{url conference=$conference->getPath() schedConf=$schedConf->getPath() page=$role->getRolePath()}">{translate key=$role->getRoleName()}</a></li>
			{/if}
		{/foreach}

	{/foreach}

	{call_hook name="Templates::User::Index::Conference" conference=$conference}
</ul>
{/foreach}

{else}
<h3>{$userConference->getTitle()}</h3>
<ul class="plain">
{if $isSiteAdmin && !$hasOtherConferences}
	<li>&#187; <a href="{url conference="index" schedConf="index" page=$isSiteAdmin->getRolePath()}">{translate key=$isSiteAdmin->getRoleName()}</a></li>
{/if}
	
	{assign var="conferenceId" value=$userConference->getConferenceId()}

	{* Iterate over conference roles *}
	
	{foreach item=role from=$userRoles[$conferenceId]}
		{if $role->getRolePath() != 'reader'}
			<li>&#187; <a href="{url conference=$userConference->getPath() schedConf=index page=$role->getRolePath()}">{translate key=$role->getRoleName()}</a></li>
		{/if}
	{/foreach}

	{* Iterate over scheduled conference roles *}
	
	{foreach from=$userSchedConfs[$conferenceId] item=schedConf}
		{assign var="schedConfId" value=$schedConf->getSchedConfId()}
		<h5>{$schedConf->getTitle()|escape}</h5>

		{foreach item=role from=$userSchedConfRoles[$schedConfId]}
			{if $role->getRolePath() != 'reader'}
				<li>&#187;
					<a href="{url
							conference=$userConference->getPath() 
							schedConf=$schedConf->getPath()
							page=$role->getRolePath()}">
						{translate key=$role->getRoleName()}
					</a>
				</li>
			{/if}
		{/foreach}

	{/foreach}
</ul>
{/if}

<h3>{translate key="user.myAccount"}</h3>
<ul class="plain">
	{if $hasOtherConferences}
	{if $showAllConferences}
	<li>&#187; <a href="{url conference="index" page="user" op="register"}">{translate key="user.registerForOtherConferences"}</a></li>
	{else}
	<li>&#187; <a href="{url conference="index" page="user"}">{translate key="user.showAllConferences"}</a></li>
	{/if}
	{/if}
	<li>&#187; <a href="{url page="user" op="profile"}">{translate key="user.editMyProfile"}</a></li>
	<li>&#187; <a href="{url page="user" op="changePassword"}">{translate key="user.changeMyPassword"}</a></li>
	<li>&#187; <a href="{url page="login" op="signOut"}">{translate key="user.logOut"}</a></li>
	{call_hook name="Templates::Admin::Index::MyAccount"}
</ul>

{include file="common/footer.tpl"}
