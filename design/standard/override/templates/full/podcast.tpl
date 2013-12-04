<div class="hd">
	<h1>{attribute_view_gui attribute=$node.data_map.title}</h1>
	<div class="hr hr-margin"></div>
</div>
<div class="bd">
	{attribute_view_gui attribute=$node.data_map.formatted_description}
</div>

<div class="hr hr-margin"></div>

{* TODO: pagination? *}
{def $maxEpisodes=120
     $episodes=fetch( 'content', 'list', hash( 'parent_node_id', $node.main_node_id,
     		      		 	       'class_filter_type', 'include',
					       'class_filter_array', array( 'podcast_episode' ),
					       'sort_by', array( 'published', false() ),
					       'limit', $maxEpisodes ) ) }
{foreach $episodes as $episode}
	{node_view_gui view='line' content_node=$episode linkEpisode=true()}
{/foreach}
