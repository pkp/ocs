{**
 * index.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Conference setup index/intro.
 *
 * $Id$
 *}

{assign var="pageTitle" value="director.setup.conferenceSetup"}
{include file="common/header.tpl"}

<h3>{translate key="director.setup.stepsToConferenceSite"}</h3>

<ol>
	<li>
		<h4><a href="{url op="setup" path="1"}">{translate key="director.setup.details"}</a></h4>
		{translate key="director.setup.details.description"}<br/>
		&nbsp;
	</li>
	<li>
		<h4><a href="{url op="setup" path="2"}">{translate key="director.setup.policies"}</a></h4>
		{translate key="director.setup.policies.description"}<br/>
		&nbsp;
	</li>
	<li>
		<h4><a href="{url op="setup" path="3"}">{translate key="director.setup.access"}</a></h4>
		{translate key="director.setup.access.description"}<br/>
		&nbsp;
	</li>
	<li>
		<h4><a href="{url op="setup" path="4"}">{translate key="director.setup.indexing"}</a></h4>
		{translate key="director.setup.indexing.description"}<br/>
		&nbsp;
	</li>
	<li>
		<h4><a href="{url op="setup" path="5"}">{translate key="director.setup.look"}</a></h4>
		{translate key="director.setup.look.description"}<br/>
		&nbsp;
	</li>
</ol>

{include file="common/footer.tpl"}
