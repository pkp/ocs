{**
 * accessDenied.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Paper View.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="schedConf.presentations.short"}
{include file="common/header.tpl"}
{/strip}

<div id="accessDenied">
<h3>{$paper->getLocalizedTitle()|strip_unsafe_html}</h3>
<div><em>{$paper->getAuthorString()|escape}</em></div>
<br />

<p>{translate key="reader.accessDenied"}</p>
</div>

{include file="common/footer.tpl"}
