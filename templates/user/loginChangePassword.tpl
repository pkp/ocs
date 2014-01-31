{**
 * loginChangePassword.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to change a user's password in order to login.
 *
 * $Id$
 *}
{strip}
{assign var="passwordLengthRestrictionLocaleKey" value="user.account.passwordLengthRestriction"}
{include file="core:user/loginChangePassword.tpl"}
{/strip}