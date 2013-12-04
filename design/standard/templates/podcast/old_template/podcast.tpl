{def $podcastDatamap=$node.data_map
     $podcastTitle=$podcastDatamap.title.content|trim|wash
     $podcastImage=$podcastDatamap.image.content
     $podcastItunesImage=$podcastDatamap.itunes_image.content
     $podcastPageUrl=$node.url|ezroot(no,full)
     $siteUrl=concat('http://',ezsys('hostname'))
     $podcastFeedUrl=concat($siteUrl,'/podcast/',$node.url)
     $max_episodes=50
     $episodes=fetch( 'content', 'list', hash( 'parent_node_id', $node.node_id,
     		      		 	                   'limit', $max_episodes,
					                           'limitation', array(),
					                           'class_filter_type', 'include',
					                           'class_filter_array', array( 'podcast_episode' ),
					                           'sort_by', array( 'published', false() ) ) )
     $episodeDatamap=''
     $audioFileUrl=''
     $audioFileLength=''
} 
<channel>
    <title>{$podcastTitle}</title>
    <link>{$podcastPageUrl}</link>
    <description>{$podcastDatamap.description.content|trim|wash}</description>
    <language>en-US{* TODO: Pick the language code from the podcast object's translation value, somehow? *}</language>
{if $podcastDatamap.copyright_owner.has_content}

    <copyright>Copyright &#xA9; {currentdate()|datetime( 'custom', '%Y' )} by {$podcastDatamap.copyright_owner.content|trim|wash}</copyright>
{/if}
{if $podcastDatamap.managing_editor_email.has_content}

    <managingEditor>{$podcastDatamap.managing_editor_email.content|trim|wash}</managingEditor>
{/if}
{if $podcastDatamap.webmaster_email.has_content}

    <webMaster>{$podcastDatamap.webmaster_email.content|trim|wash}</webMaster>
{/if}
{* TODO: Fix the time zones-- take daylight savings time into account, and/or the timezone of the server... *}
{if $episodes[0]}

    <pubDate>{if $episodes[0].data_map.published.has_content}{$episodes[0].data_map.published.content.timestamp|datetime('custom', '%D, %d %M %Y %H:%i:%s -0700')}{else}{$episodes[0].object.published|datetime('custom', '%D, %d %M %Y %H:%i:%s -0700')}{/if}</pubDate>
{/if}

    <lastBuildDate>{currentdate()|datetime('custom', '%D, %d %M %Y %H:%i:%s -0700')}</lastBuildDate>
{if $podcastDatamap.category.has_content}

    <category>{$podcastDatamap.category.content|trim|wash}</category>
{/if}

    <docs>http://blogs.law.harvard.edu/tech/rss</docs>
    <ttl>60</ttl>{* TODO: Do we really want to set TTL?  (It is in minutes)  If so, give a field on the Podcast class? *}
{if $podcastImage.is_valid}{def $image=$podcastImage['original']}
    {* TODO: Use a larger size?  The 'rss' size is very small, perhaps out of date? *}
    <image>
      <url>{$image.url|ezroot(no,full)}</url>
      <title>{$podcastTitle}</title>
      <link>{$podcastPageUrl}</link>
      <width>{$image.width}</width>
      <height>{$image.height}</height>
    </image>
{/if}

    <atom:link href="{$podcastFeedUrl}" rel="self" type="application/rss+xml"/>
{if not($podcastItunesImage.is_valid)}{set $podcastItunesImage=$podcastImage}{/if}{if $podcastItunesImage.is_valid}{def $iTunesImage=$podcastItunesImage['original']}{* TODO: Use 'reference'? (600x600) Apple specs want images at least 600x600. *}
    <itunes:image href="{$iTunesImage.url|ezroot(no,full)}" />
{/if}
{if $podcastDatamap.owner_name.has_content}
{* TODO: Make a separate field for iTunes author?  This goes in the "Artist" column in iTunes *}
    <itunes:author>{$podcastDatamap.owner_name.content|trim|wash}</itunes:author>
{/if}

    <itunes:block>no{* TODO: Prevents an episode or podcast from appearing.  Would we ever want to say anything but 'no'?  Do we even need it here? *}</itunes:block>
{if $podcastDatamap.category.has_content}

    <itunes:category text="{$podcastDatamap.category.content|trim|wash}" />
{/if}

    <itunes:explicit>no{* TODO: Make this a field in the Podcast class, just as in the Podcast Episode class? *}</itunes:explicit>
{if $podcastDatamap.keywords.has_content}

    <itunes:keywords>{$podcastDatamap.keywords.content.keyword_string|trim|wash}</itunes:keywords>
{/if}
{* TODO: Enable a feature for the itunes:new-feed-url tag, when the podcast moves? *}
{if or($podcastDatamap.owner_name.has_content,$podcastDatamap.owner_email.has_content)}

    <itunes:owner>
{if $podcastDatamap.owner_name.has_content}

      <itunes:name>{$podcastDatamap.owner_name.content|trim|wash}</itunes:name>
{/if}
{if $podcastDatamap.owner_email.has_content}

      <itunes:email>{$podcastDatamap.owner_email.content|trim|wash}</itunes:email>
{/if}

    </itunes:owner>
{/if}
{if $podcastDatamap.subtitle.has_content}

    <itunes:subtitle>{$podcastDatamap.subtitle.content|trim|wash}</itunes:subtitle>
{/if}
{if $podcastDatamap.itunes_summary.has_content}

    <itunes:summary>{$podcastDatamap.itunes_summary.content|trim|wash}</itunes:summary>
{/if}

{foreach $episodes as $episode}
{set $episodeDatamap=$episode.data_map
     $audioFileUrl=$episodeDatamap.audio.content
     $audioFileLength=$episodeDatamap.audio_length.content
}

    <item>
      <title>{$episodeDatamap.title.content|trim|wash}</title>
{if $episodeDatamap.audio.has_content}

      <link>{$audioFileUrl}</link>
{else}

      <link>{$podcastPageUrl}</link>
{/if}

      <description>{$episodeDatamap.description.content|trim|wash}</description>{* TODO: Use the RSS <author> tag?  Might make sense only if we have a per-episode author field.  Takes the form <author>email (Name)</author> *}
{if $episodeDatamap.category.has_content}

      <category>{$episodeDatamap.category.content|trim|wash}</category>
{/if}
{if $episodeDatamap.discussion.has_content}

      <comments>{$episodeDatamap.discussion.content.main_node.url|ezurl}</comments>
{/if}

      <enclosure url="{$audioFileUrl}" length="{$audioFileLength}" type="audio/mpeg"/>
{if $episodeDatamap.audio.has_content}

      <guid isPermaLink="false">{$audioFileUrl}</guid>
{/if}

      <pubDate>{if $episodeDatamap.published.has_content}{$episodeDatamap.published.content.timestamp|datetime('custom', '%D, %d %M %Y %H:%i:%s -0700')}{else}{$episode.object.published|datetime('custom', '%D, %d %M %Y %H:%i:%s -0700')}{/if}</pubDate>
{if $podcastDatamap.owner_name.has_content}{* TODO: Make a per-episode field for the episode author?  This goes in the "Artist" column in iTunes *}
      <itunes:author>{$podcastDatamap.owner_name.content|trim|wash}</itunes:author>
{/if}

      <itunes:block>no{* TODO: Prevents an episode or podcast from appearing.  Would we ever want to say anything but 'no'?  Do we even need it here? *}</itunes:block>
      <itunes:duration>{$episodeDatamap.duration.content|trim}</itunes:duration>
      <itunes:explicit>{if $episodeDatamap.explicit.content}yes{else}no{/if}</itunes:explicit>
{if $episodeDatamap.keywords.has_content}

      <itunes:keywords>{$episodeDatamap.keywords.content.keyword_string|trim|wash}</itunes:keywords>
{/if}
{if $episodeDatamap.subtitle.has_content}

      <itunes:subtitle>{$episodeDatamap.subtitle.content|trim|wash}</itunes:subtitle>
{/if}

{* TODO: Have an iTunes-specific "summary" field on the Podcast Episode class? 
      <itunes:summary>{$episodeDatamap.description.content|trim|wash}</itunes:summary>
*}    </item>

{/foreach}
  </channel>
