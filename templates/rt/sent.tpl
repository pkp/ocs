{**
 * bio.tpl
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * RT Email Sent page.
 *
 * $Id$
 *}
{strip}
{assign var=pageTitle value="email.email"}
{include file="rt/header.tpl"}
{/strip}

<p>{translate key="rt.email.sent"}</p>

{include file="rt/footer.tpl"}
