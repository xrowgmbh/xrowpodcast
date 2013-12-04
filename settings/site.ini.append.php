<?php /* #?ini charset="utf-8"?

# Grant permission to everyone to view the podcast feed
# (We control access to the premium podcasts using HTTP basic authentication at
# the Apache layer)
[RoleSettings]
PolicyOmitList[]=podcast/feed
PolicyOmitList[]=podcast/allfeeds
# Secret password-free access for Podtrac
PolicyOmitList[]=podcast/podtrac-xxxxxxxxx

[SSLZoneSettings]      
# The code in the various views controls whether to use HTTPS, so we won't force it either way here
ModuleViewAccessMode[podcast/*]=keep

*/ ?>
