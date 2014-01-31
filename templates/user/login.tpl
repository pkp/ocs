{**
 * login.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * User login form.
 *
 * $Id$
 *}
{strip}
{assign var="helpTopicId" value="conference.users.index"}
{assign var="registerOp" value="account"}
{assign var="registerLocaleKey" value="user.login.createAccount"}
{include file="core:user/login.tpl"}
{/strip}