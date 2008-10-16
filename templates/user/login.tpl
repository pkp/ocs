{**
 * login.tpl
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * User login form.
 *
 * $Id$
 *}
{strip}
{assign var="helpTopicId" value="conference.users.index"}
{assign var="registerLocaleKey" value="user.login.createAccount"}
{url|assign:"registerUrl" page="user" op="account" source=$source|escape requiresPresenter=$requiresPresenter|escape}
{include file="core:user/login.tpl"}
{/strip}