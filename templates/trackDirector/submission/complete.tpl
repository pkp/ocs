{**
 * complete.tpl
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the "complete" button for submissions.
 *
 * $Id$
 *}
<a name="complete"></a>

<h3>{translate key="submission.complete"}</h3>

<form method="post" action="{url op="completePaper"}">
	<input type="hidden" name="paperId" value="{$submission->getPaperId()}" />
	{translate key="submission.complete.description"}
	<input name="complete" {if $submission->getStatus() == SUBMISSION_STATUS_PUBLISHED}disabled="disabled" {/if}type="submit" value="{translate key="submission.complete"}" class="button" />
	<input name="remove" {if $submission->getStatus() != SUBMISSION_STATUS_PUBLISHED}disabled="disabled" {/if}type="submit" value="{translate key="common.remove"}" class="button" />
</form>
