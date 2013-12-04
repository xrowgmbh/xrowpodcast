{* Simple, short listing of podcast episode, showing only its title and
   publication date, and linking to the full version. *}

{def $isPremium=$node.object.section_id|eq(7)
     $hrefLink=$node.url|ezurl}

<div class="hd"> {* TODO: Why use class "hd" when there is no corresponding "bd"? *}
<div class="hr hr-margin"></div>

<div style="float:left;margin:-3px 5px 0 0;">
<a href={$hrefLink}><img src={if $isPremium}{'round_blue_play_button_50x50.png'|ezimage}{else}{'round_green_play_button_50x50.png'|ezimage}{/if} align="left" width="50" height="50" alt="play" />
</div>
<h2><a href={$hrefLink}>
	{if $node.data_map.episode_number.has_content}Episode {$node.data_map.episode_number.content}{else}Bonus Episode{/if}:
	{attribute_view_gui attribute=$node.data_map.title}
</a></h2>

<p style="display:block;">{if $node.data_map.published_date.has_content}{$node.data_map.published_date.content.timestamp|datetime( 'custom', '%F %d, %Y' )}{else}{$node.object.published|datetime( 'custom', '%F %d, %Y' )}{/if}</p>

<div class="clear"></div>
{if $node.data_map.formatted_description.has_content}
	{attribute_view_gui attribute=$node.data_map.formatted_description}
{else}
	<p>{attribute_view_gui attribute=$node.data_map.description}</p>
{/if}

</div>
