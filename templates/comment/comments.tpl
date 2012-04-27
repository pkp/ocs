{**
 * comments.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display comments on a paper.
 *
 * $Id$
 *}
<div id="comments">
{strip}
{if $comment}
	{assign var=pageTitle value="comments.readerComments"}
	{assign var=pageCrumbTitleTranslated value=$comment->getTitle()|escape|truncate:50:"..."|default:"&nbsp;"}
{else}
	{assign var=pageTitle value="comments.readerComments"}
{/if}
{include file="common/header.tpl"}
{/strip}

{if $enableComments && !$commentsClosed && (!$commentsRequireRegistration || $isUserLoggedIn)}
	{assign var=postingAllowed value=1}
{else}
	{assign var=postingAllowed value=0}
{/if}

{if $comment}
	{assign var=user value=$comment->getUser()}
	<h3>{$comment->getTitle()|escape|default:"&nbsp;"}</h3>
	<h4>
		{if $user}
			{translate key="comments.authenticated" userName=$user->getFullName()|escape}
		{elseif $comment->getPosterName()}
			{translate key="comments.anonymousNamed" userName=$comment->getPosterName()|escape}
		{else}
			{translate key="comments.anonymous"}
		{/if}
		({$comment->getDatePosted()|date_format:$dateFormatShort})
	</h4>

	<p>

	{if $parent}
		{assign var=parentId value=$parent->getCommentId()}
		{url|assign:"url" page="comment" op="view" path=$paperId|to_array:$parentId}
		<em>{translate key="comments.inResponseTo" url=$url title=$parent->getTitle()|escape|default:"&nbsp;"}</em><br />
	{/if}

	{assign var="hasPriorAction" value=0}{* Track whether to add "|" between actions *}

	{if $comment->getPosterEmail()}
		{translate|assign:"emailReply" key="comments.emailReply"}
		{mailto text=$emailReply encode="javascript" address=$comment->getPosterEmail() subject=$comment->getTitle()|default:"&nbsp;" extra='class="action"'}
		{assign var="hasPriorAction" value=1}
	{/if}

	{if $postingAllowed}
		{if $hasPriorAction}&nbsp;|&nbsp;{/if}
		<a href="{url op="add" path=$paperId|to_array:$galleyId:$comment->getId()}" class="action">{translate key="comments.postReply"}</a>
		{assign var="hasPriorAction" value=1}
	{/if}

	{if $isManager}
		{if $hasPriorAction}&nbsp;|&nbsp;{/if}
		<a href="{url op="delete" path=$paperId|to_array:$galleyId:$comment->getId()}" {if $comment->getChildCommentCount()!=0}onclick="return confirm('{translate|escape:"jsparam" key="comments.confirmDeleteChildren"}')" {/if}class="action">{translate key="comments.delete"}</a>
		{assign var="hasPriorAction" value=1}
	{/if}

	<br />
	</p>

	{$comment->getBody()|strip_unsafe_html|nl2br}

<br /><br />

{if $comments}
	<div class="separator"></div>
	<h3>{translate key="comments.replies"}</h3>{/if}
{/if}

{foreach from=$comments item=child}
<div id="childComment">
{assign var=user value=$child->getUser()}
{assign var=childId value=$child->getCommentId()}
<h4><a href="{url op="view" path=$paperId|to_array:$galleyId:$childId}" target="_parent">{$child->getTitle()|escape|default:"&nbsp;"}</a></h4>
<h5>
	{if $user}
		{translate key="comments.authenticated" userName=$user->getUserName()|escape}
	{elseif $child->getPosterName()}
		{translate key="comments.anonymousNamed" userName=$child->getPosterName()|escape}
	{else}
		{translate key="comments.anonymous"}
	{/if}
	({$child->getDatePosted()|date_format:$dateFormatShort})
</h5>

{assign var="hasPriorAction" value=0}

{if $child->getPosterEmail()}
	{translate|assign:"emailReply" key="comments.emailReply"}
	{mailto text=$emailReply encode="javascript" address=$child->getPosterEmail()|escape subject=$child->getTitle()|escape|default:"&nbsp;" extra='class="action"'}
	{assign var="hasPriorAction" value=1}
{/if}

{if $postingAllowed}
	{if $hasPriorAction}&nbsp;|&nbsp;{/if}
	<a href="{url op="add" path=$paperId|to_array:$galleyId:$childId}" class="action">{translate key="comments.postReply"}</a>&nbsp;&nbsp;
	{assign var="hasPriorAction" value=1}
{/if}
{if $isManager}
	{if $hasPriorAction}&nbsp;|&nbsp;{/if}
	<a href="{url op="delete" path=$paperId|to_array:$galleyId:$child->getCommentId()}" {if $child->getChildCommentCount()!=0}onclick="return confirm('{translate|escape:"jsparam" key="comments.confirmDeleteChildren"}')" {/if}class="action">{translate key="comments.delete"}</a>
	{assign var="hasPriorAction" value=1}
{/if}
<br />

{translate|assign:"readMore" key="comments.readMore"}
{url|assign:"moreUrl" op="view" path=$paperId|to_array:$galleyId:$childId}
{assign var=moreLink value="<a href=\"$moreUrl\">$readMore</a>"}
<p>{$child->getBody()|strip_tags|nl2br|truncate:300:"... $moreLink"}</p>

{assign var=grandChildren value=$child->getChildren()}
{if $grandChildren}<ul>{/if}
{foreach from=$child->getChildren() item=grandChild}
<div id="grandchildComment">
{assign var=user value=$grandChild->getUser()}
	<li>
		<a href="{url op="view" path=$paperId|to_array:$galleyId:$grandChild->getCommentId()}" target="_parent">{$grandChild->getTitle()|escape|default:"&nbsp;"}</a>
		{if $grandChild->getChildCommentCount()==1}
			{translate key="comments.oneReply"}
		{elseif $grandChild->getChildCommentCount()>0}
			{translate key="comments.nReplies" num=$grandChild->getChildCommentCount()}
		{/if}
		
		<br/>

		{if $user}
			{translate key="comments.authenticated" userName=$user->getFullName()|escape}
		{elseif $grandChild->getPosterName()}
			{translate key="comments.anonymousNamed" userName=$grandChild->getPosterName()|escape}
		{else}
			{translate key="comments.anonymous"}
		{/if}
		({$grandChild->getDatePosted()|date_format:$dateFormatShort})
	</li>
</div>
{/foreach}
{if $grandChildren}
	</ul>
{/if}
</div>
{foreachelse}
	{if !$comment}
		<div id="noComments">{translate key="comments.noComments"}</div>
	{/if}
{/foreach}

{if $commentsClosed}<div id="commentsClosed">{translate key="comments.commentsClosed" closeCommentsDate=$closeCommentsDate|date_format:$dateFormatShort}<br /></div>{/if}

{if $postingAllowed}
	<div class="separator"></div>
	<div id="addComment"<p><a class="action" href="{url op="add" path=$paperId|to_array:$galleyId}" target="_parent">{translate key="rt.addComment"}</a></p></div>
{/if}

{include file="common/footer.tpl"}
