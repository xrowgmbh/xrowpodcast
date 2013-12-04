{def $podcastAudioFileUrl=$node.data_map.audio.content}

<div class="hd">
<div class="hr hr-margin"></div>

<h2>{if $linkEpisode}<a href={$node.url|ezurl}>{/if}{if $node.data_map.episode_number.has_content}Episode {$node.data_map.episode_number.content}{else}Bonus Episode{/if}: {attribute_view_gui attribute=$node.data_map.title}{if $linkEpisode}</a>{/if}</h2>

<p style="display:block;">{if $node.data_map.published_date.has_content}{$node.data_map.published_date.content.timestamp|datetime( 'custom', '%F %d, %Y' )}{else}{$node.object.published|datetime( 'custom', '%F %d, %Y' )}{/if}</p>

{* Embed audio player *}
{include uri='design:html5audioplayer.tpl' audioFileUrl=$podcastAudioFileUrl}
<br>
     	      
{if $node.data_map.formatted_description.has_content}
	{attribute_view_gui attribute=$node.data_map.formatted_description}
{else}
	<p>{attribute_view_gui attribute=$node.data_map.description}</p>
{/if}

{if $node.data_map.discussion.has_content}<p><a href={$node.data_map.discussion.content.main_node.url|ezurl}>Join the conversation about this podcast!</a></p>{/if}

{* For section 7 (Members) we try not to expose the MP3 URL too much so people don't copy and share it. 
{if $node.object.section_id|ne(7)}<p><a href="{$podcastAudioFileUrl}">Direct link to MP3 file</a></p>{/if}
*}
{* Scott wants to show it to Members anyways. *}
<p><a href="{$podcastAudioFileUrl}">Direct link to MP3 file</a></p>

</div>
