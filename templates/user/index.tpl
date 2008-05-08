{**
 * index.tpl
 *
 * Copyright (c) 2000-2008 John Willinsky
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
{assign var="hasRole" value=1}
<h4><a href="{url page="user"}">{$siteTitle|escape}</a></h4>
<ul class="plain">
	<li>&#187; <a href="{url conference="index" page=$isSiteAdmin->getRolePath()}">{translate key=$isSiteAdmin->getRoleName()}</a></li>
	{call_hook name="Templates::User::Index::Admin"}
</ul>
{/if}

{foreach from=$userConferences item=conference}
{assign var="hasRole" value=1}
<h4><a href="{url conference=$conference->getPath() page="user"}">{$conference->getConferenceTitle()|escape}</a></h4>
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
		<h5><a href="{url conference=$conference->getPath() schedConf=$schedConf->getPath() page="index"}">{$schedConf->getSchedConfTitle()|escape}</a></h5>

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
<h3>{$userConference->getConferenceTitle()}</h3>
<ul class="plain">
{if $isSiteAdmin && !$hasOtherConferences}
	<li>&#187; <a href="{url conference="index" schedConf="index" page=$isSiteAdmin->getRolePath()}">{translate key=$isSiteAdmin->getRoleName()}</a></li>
{/if}
	
	{assign var="conferenceId" value=$userConference->getConferenceId()}

	{* Iterate over conference roles *}
	
	{foreach item=role from=$userRoles[$conferenceId]}
		{if $role->getRolePath() != 'reader'}
			{assign var="hasRole" value=1}
			<li>&#187; <a href="{url conference=$userConference->getPath() schedConf=index page=$role->getRolePath()}">{translate key=$role->getRoleName()}</a></li>
		{/if}
	{/foreach}

	{* Iterate over scheduled conference roles *}
	
	{foreach from=$userSchedConfs[$conferenceId] item=schedConf}
		{assign var="hasRole" value=1}
		{assign var="schedConfId" value=$schedConf->getSchedConfId()}
		<h5><a href="{url conference=$userConference->getPath() schedConf=$schedConf->getPath() page="index"}">{$schedConf->getSchedConfTitle()|escape}</a></h5>

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

{if !$hasRole}
	{if !$currentSchedConf}
		<p>{translate key="user.noRoles.chooseConference"}</p>
		{foreach from=$allConferences item=thisConference key=conferenceId}
			<h4>{$thisConference->getConferenceTitle()|escape}</h4>
			{if !empty($allSchedConfs[$conferenceId])}
			<ul class="plain">
			{foreach from=$allSchedConfs[$conferenceId] item=thisSchedConf key=schedConfId}
				<li>&#187; <a href="{url conference=$thisConference->getPath() schedConf=$thisSchedConf->getPath() page="user" op="index"}">{$thisSchedConf->getSchedConfTitle()|escape}</a></li>
			{/foreach}
			</ul>
			{/if}{* !empty($allSchedConfs[$conferenceId]) *}
		{/foreach}
	{else}{* !$currentSchedConf *}
		<p>{translate key="user.noRoles.noRolesForConference"}</p>
		<ul class="plain">
			<li>
				&#187;
				{if $allowRegPresenter}
					{if $submissionsOpen}
						<a href="{url page="presenter" op="submit"}">{translate key="user.noRoles.submitProposal"}</a>
					{else}{* $submissionsOpen *}
						{translate key="user.noRoles.submitProposalSubmissionsClosed"}
					{/if}{* $submissionsOpen *}
				{else}{* $allowRegPresenter *}
					{translate key="user.noRoles.submitProposalRegClosed"}
				{/if}{* $allowRegPresenter *}
			</li>
			<li>
				&#187;
				{if $allowRegReviewer}
					{url|assign:"userHomeUrl" page="user" op="index"}
					<a href="{url op="become" path="reviewer" source=$userHomeUrl}">{translate key="user.noRoles.regReviewer"}</a>
				{else}{* $allowRegReviewer *}
					{translate key="user.noRoles.regReviewerClosed"}
				{/if}{* $allowRegReviewer *}
			</li>
			<li>
				&#187;
				{if $schedConfPaymentsEnabled}
					<a href="{url page="schedConf" op="registration"}">{translate key="user.noRoles.register"}</a>
				{else}{* $schedConfPaymentsEnabled *}
					{translate key="user.noRoles.registerUnavailable"}
				{/if}{* $schedConfPaymentsEnabled *}
			</li>
		</ul>
	{/if}{* !$currentSchedConf *}
{/if}


<h3>{translate key="user.myAccount"}</h3>
<ul class="plain">
	{if $hasOtherConferences}
	{if $showAllConferences}
	<li>&#187; <a href="{url conference="index" page="user" op="account"}">{translate key="user.createAccountForOtherConferences"}</a></li>
	{else}
	<li>&#187; <a href="{url conference="index" page="user"}">{translate key="user.showAllConferences"}</a></li>
	{/if}
	{/if}
	<li>&#187; <a href="{url page="user" op="profile"}">{translate key="user.editMyProfile"}</a></li>
	<li>&#187; <a href="{url page="user" op="changePassword"}">{translate key="user.changeMyPassword"}</a></li>
	<li>&#187; <a href="{url page="login" op="signOut"}">{translate key="user.logOut"}</a></li>
	{call_hook name="Templates::User::Index::MyAccount"}
</ul>

{include file="common/footer.tpl"}
