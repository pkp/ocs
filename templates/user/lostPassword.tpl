{**
 * lostPassword.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Password reset form.
 *
 * $Id$
 *}
{strip}
{assign var="registerOp" value="account"}
{assign var="registerLocaleKey" value="user.login.createAccount"}
{include file="core:user/lostPassword.tpl"}
{/strip}