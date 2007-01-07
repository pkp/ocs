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

{assign var="pageTitle" value="eventDirector.setup.eventSetup"}
{include file="common/header.tpl"}

<span class="instruct">{translate key="eventDirector.setup.stepsToEvent"}</span>

<ol>
	<li>
		<h4><a href="{url op="eventSetup" path="1"}">{translate key="eventDirector.setup.details"}</a></h4>
		{translate key="eventDirector.setup.details.description"}<br/>
		&nbsp;
	</li>
	<li>
		<h4><a href="{url op="eventSetup" path="2"}">{translate key="eventDirector.setup.submissions"}</a></h4>
		{translate key="eventDirector.setup.submissions.description"}<br/>
		&nbsp;
	</li>
	<li>
		<h4><a href="{url op="eventSetup" path="3"}">{translate key="eventDirector.setup.review"}</a></h4>
		{translate key="eventDirector.setup.review.description"}<br/>
		&nbsp;
	</li>
	<li>
		<h4><a href="{url op="eventSetup" path="4"}">{translate key="eventDirector.setup.participation"}</a></h4>
		{translate key="eventDirector.setup.participation.description"}<br/>
		&nbsp;
	</li>
	<li>
		<h4><a href="{url op="eventSetup" path="5"}">{translate key="eventDirector.setup.look"}</a></h4>
		{translate key="eventDirector.setup.look.description"}<br/>
		&nbsp;
	</li>
</ol>

{include file="common/footer.tpl"}
