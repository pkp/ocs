{**
 * block.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site sidebar menu -- help pop-up link.
 *
 * $Id$
 *}
<div class="block" id="sidebarHelp">
	<a class="blockTitle" href="javascript:openHelp('{if $helpTopicId}{get_help_id|escape key="$helpTopicId" url="true"}{else}{url page="help"}{/if}')">{translate key="navigation.conferenceHelp"}</a>
</div>
