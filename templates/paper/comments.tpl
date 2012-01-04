{**
 * comments.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Paper View -- Comments component.
 *
 * $Id$
 *}
{if $comments}
<div class="separator"></div>
<div id="paperComments">
<h4>{translate key="comments.commentsOnPaper"}</h4>

<ul>
{foreach from=$comments item=comment}
{assign var=poster value=$comment->getUser()}
<div id="comment">
	<li>
		<a href="{url page="comment" op="view" path=$paper->getId()|to_array:$galleyId:$comment->getId()}" target="_parent">{$comment->getTitle()|escape|default:"&nbsp;"}</a>
		{if $comment->getChildCommentCount()==1}
			{translate key="comments.oneReply"}
		{elseif $comment->getChildCommentCount()>0}
			{translate key="comments.nReplies" num=$comment->getChildCommentCount()}
		{/if}
		
		<br/>

		{if $poster}
			{translate key="comments.authenticated" userName=$poster->getFullName()|escape}
		{elseif $comment->getPosterName()}
			{translate key="comments.anonymousNamed" userName=$comment->getPosterName()|escape}
		{else}
			{translate key="comments.anonymous"}
		{/if}
		({$comment->getDatePosted()|date_format:$dateFormatShort})
	</li>
</div>
{/foreach}
</ul>

{if $commentsClosed}{translate key="comments.commentsClosed" closeCommentsDate=$closeCommentsDate|date_format:$dateFormatShort}<br />{/if}

<a href="{url page="comment" op="view" path=$paper->getId()|to_array:$galleyId}" class="action" target="_parent">{translate key="comments.viewAllComments"}</a>

{assign var=needsSeparator value=1}

{/if}{* $comments *}

{if $postingAllowed}
	{if $needsSeparator}
		&nbsp;|&nbsp;
	{else}
		<br/><br/>
	{/if}
	<a class="action" href="{url page="comment" op="add" path=$paper->getId()|to_array:$galleyId}" target="_parent">{translate key="rt.addComment"}</a>
{/if}
</div>