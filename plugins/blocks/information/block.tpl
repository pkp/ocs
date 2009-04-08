{**
 * block.tpl
 *
 * Copyright (c) 2000-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site sidebar menu -- information links.
 *
 * $Id$
 *}
{if !empty($forReaders) || !empty($forPresenters)}
<div class="block" id="sidebarInformation">
	<span class="blockTitle">{translate key="plugins.block.information.link"}</span>
	<ul>
		{if !empty($forReaders)}<li><a href="{url page="information" op="readers"}">{translate key="navigation.infoForReaders"}</a></li>{/if}
		{if !empty($forPresenters)}<li><a href="{url page="information" op="presenters"}">{translate key="navigation.infoForPresenters"}</a></li>{/if}
	</ul>
</div>
{/if}

