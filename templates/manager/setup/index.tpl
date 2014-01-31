{**
 * index.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Conference setup index/intro.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="manager.websiteManagement"}
{include file="common/header.tpl"}
{/strip}

<h3>{translate key="manager.setup.stepsToConferenceSite"}</h3>

<ol>
	<li>
		<h4><a href="{url op="setup" path="1"}">{translate key="manager.setup.aboutConference"}</a></h4>
		{translate key="manager.setup.aboutConference.description"}<br/>
		&nbsp;
	</li>
	<li>
		<h4><a href="{url op="setup" path="2"}">{translate key="manager.setup.additionalContent"}</a></h4>
		{translate key="manager.setup.additionalContent.description"}<br/>
		&nbsp;
	</li>
	<li>
		<h4><a href="{url op="setup" path="3"}">{translate key="manager.setup.layout"}</a></h4>
		{translate key="manager.setup.layout.description"}<br/>
		&nbsp;
	</li>
	<li>
		<h4><a href="{url op="setup" path="4"}">{translate key="manager.setup.style"}</a></h4>
		{translate key="manager.setup.style.description"}<br/>
		&nbsp;
	</li>
	<li>
		<h4><a href="{url op="setup" path="5"}">{translate key="manager.setup.loggingAndAuditing"}</a></h4>
		{translate key="manager.setup.loggingAndAuditing.description"}<br/>
		&nbsp;
	</li>
	<li>
		<h4><a href="{url op="setup" path="6"}">{translate key="manager.setup.indexing"}</a></h4>
		{translate key="manager.setup.indexing.description"}<br/>
		&nbsp;
	</li>
</ol>

{include file="common/footer.tpl"}
