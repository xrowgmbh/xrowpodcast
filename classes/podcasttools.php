<?php

class podcastTools
{

    function checkAccess( $functionName, $node, $user, $originalClassID = false, $parentClassID = false, $returnAccessList = false, $language = false )
    {
        $classID = $originalClassID;
        
        $userID = $user->attribute( 'contentobject_id' );
        
        // Fetch the ID of the language if we get a string with a language code
        // e.g. 'eng-GB'
        $originalLanguage = $language;
        if ( is_string( $language ) && strlen( $language ) > 0 )
        {
            $language = eZContentLanguage::idByLocale( $language );
        }
        else
        {
            $language = false;
        }
        
        // This will be filled in with the available languages of the object
        // if a Language check is performed.
        $languageList = false;
        
        // This will be filled if parent object is needed.
        $parentObject = false;
        
        $origFunctionName = $functionName;
        // The 'move' function simply reuses 'edit' for generic access
        // but adds another top-level check below
        // The original function is still available in $origFunctionName
        if ( $functionName == 'move' )
            $functionName = 'edit';
        
     // Manage locations depends if it's removal or not.
        if ( $functionName == 'can_add_location' || $functionName == 'can_remove_location' )
        {
            $functionName = 'manage_locations';
        }
        
        $accessResult = $user->hasAccessTo( 'content', $functionName );
        $accessWord = $accessResult['accessWord'];
        if ( $origFunctionName == 'can_remove_location' )
        {
            if ( $node->ParentNodeID <= 1 )
            {
                return 0;
            }
            $currentNode = eZContentObjectTreeNode::fetch( $node->ParentNodeID );
            if ( ! $currentNode instanceof eZContentObjectTreeNode )
            {
                return 0;
            }
            $contentObject = $currentNode->attribute( 'object' );
        }
        else
        {
            $currentNode = $node;
            $contentObject = $node->attribute( 'object' );
        }
        
        /*
        // Uncomment this part if 'create' permissions should become implied 'edit'.
        // Merges in 'create' policies with 'edit'
        if ( $functionName == 'edit' &&
             !in_array( $accessWord, array( 'yes', 'no' ) ) )
        {
            // Add in create policies.
            $accessExtraResult = $user->hasAccessTo( 'content', 'create' );
            if ( $accessExtraResult['accessWord'] != 'no' )
            {
                $accessWord = $accessExtraResult['accessWord'];
                if ( isset( $accessExtraResult['policies'] ) )
                {
                    $accessResult['policies'] = array_merge( $accessResult['policies'],
                                                             $accessExtraResult['policies'] );
                }
                if ( isset( $accessExtraResult['accessList'] ) )
                {
                    $accessResult['accessList'] = array_merge( $accessResult['accessList'],
                                                               $accessExtraResult['accessList'] );
                }
            }
        }
        */
        
        if ( $origFunctionName == 'remove' or $origFunctionName == 'move' or $origFunctionName == 'can_remove_location' )
        {
            // We do not allow these actions on top-level nodes
            // - remove
            // - move
            if ( $node->ParentNodeID <= 1 )
            {
                return 0;
            }
        }
        
        if ( $classID === false )
        {
            $classID = $contentObject->attribute( 'contentclass_id' );
        }
        if ( $accessWord == 'yes' )
        {
            return 1;
        }
        else 
            if ( $accessWord == 'no' )
            {
                if ( $functionName == 'edit' )
                {
                    // Check if we have 'create' access under the main parent
                    $object = $currentNode->object();
                    if ( $object && $object->attribute( 'current_version' ) == 1 && ! $object->attribute( 'status' ) )
                    {
                        $mainNode = eZNodeAssignment::fetchForObject( $object->attribute( 'id' ), $object->attribute( 'current_version' ) );
                        $parentObj = $mainNode[0]->attribute( 'parent_contentobject' );
                        $result = $parentObj->checkAccess( 'create', $object->attribute( 'contentclass_id' ), $parentObj->attribute( 'contentclass_id' ), false, $originalLanguage );
                        return $result;
                    }
                    else
                    {
                        return 0;
                    }
                }
                
                return 0;
            }
            else
            {
                $policies = $accessResult['policies'];
                $access = 'denied';
                
                foreach ( $policies as $pkey => $limitationArray )
                {
                    if ( $access == 'allowed' )
                    {
                        break;
                    }
                    
                    $limitationList = array();
                    if ( isset( $limitationArray['Subtree'] ) )
                    {
                        $checkedSubtree = false;
                    }
                    else
                    {
                        $checkedSubtree = true;
                        $accessSubtree = false;
                    }
                    if ( isset( $limitationArray['Node'] ) )
                    {
                        $checkedNode = false;
                    }
                    else
                    {
                        $checkedNode = true;
                        $accessNode = false;
                    }
                    foreach ( $limitationArray as $key => $valueList )
                    {
                        $access = 'denied';
                        switch ( $key )
                        {
                            case 'Class':
                                {
                                    if ( $functionName == 'create' and ! $originalClassID )
                                    {
                                        $access = 'allowed';
                                    }
                                    else 
                                        if ( $functionName == 'create' and in_array( $classID, $valueList ) )
                                        {
                                            $access = 'allowed';
                                        }
                                        else 
                                            if ( $functionName != 'create' and in_array( $contentObject->attribute( 'contentclass_id' ), $valueList ) )
                                            {
                                                $access = 'allowed';
                                            }
                                            else
                                            {
                                                $access = 'denied';
                                                $limitationList = array( 
                                                    'Limitation' => $key , 
                                                    'Required' => $valueList 
                                                );
                                            }
                                }
                                break;
                            
                            case 'ParentClass':
                                {
                                    if ( in_array( $contentObject->attribute( 'contentclass_id' ), $valueList ) )
                                    {
                                        $access = 'allowed';
                                    }
                                    else
                                    {
                                        $access = 'denied';
                                        $limitationList = array( 
                                            'Limitation' => $key , 
                                            'Required' => $valueList 
                                        );
                                    }
                                }
                                break;
                            
                            case 'Section':
                            case 'User_Section':
                                {
                                    if ( in_array( $contentObject->attribute( 'section_id' ), $valueList ) )
                                    {
                                        $access = 'allowed';
                                    }
                                    else
                                    {
                                        $access = 'denied';
                                        $limitationList = array( 
                                            'Limitation' => $key , 
                                            'Required' => $valueList 
                                        );
                                    }
                                }
                                break;
                            
                            case 'Language':
                                {
                                    $languageMask = 0;
                                    // If we don't have a language list yet we need to fetch it
                                    // and optionally filter out based on $language.
                                    if ( $functionName == 'create' )
                                    {
                                        // If the function is 'create' we do not use the language_mask for matching.
                                        if ( $language !== false )
                                        {
                                            $languageMask = $language;
                                        }
                                        else
                                        {
                                            // If the create is used and no language specified then
                                            // we need to match against all possible languages (which
                                            // is all bits set, ie. -1).
                                            $languageMask = - 1;
                                        }
                                    }
                                    else
                                    {
                                        if ( $language !== false )
                                        {
                                            if ( $languageList === false )
                                            {
                                                $languageMask = $contentObject->attribute( 'language_mask' );
                                                // We are restricting language check to just one language
                                                $languageMask &= $language;
                                                // If the resulting mask is 0 it means that the user is trying to
                                                // edit a language which does not exist, ie. translating.
                                                // The mask will then become the language trying to edit.
                                                if ( $languageMask == 0 )
                                                {
                                                    $languageMask = $language;
                                                }
                                            }
                                        }
                                        else
                                        {
                                            $languageMask = - 1;
                                        }
                                    }
                                    // Fetch limit mask for limitation list
                                    $limitMask = eZContentLanguage::maskByLocale( $valueList );
                                    if ( ( $languageMask & $limitMask ) != 0 )
                                    {
                                        $access = 'allowed';
                                    }
                                    else
                                    {
                                        $access = 'denied';
                                        $limitationList = array( 
                                            'Limitation' => $key , 
                                            'Required' => $valueList 
                                        );
                                    }
                                }
                                break;
                            
                            case 'Owner':
                            case 'ParentOwner':
                                {
                                    // if limitation value == 2, anonymous limited to current session.
                                    if ( in_array( 2, $valueList ) && $user->isAnonymous() )
                                    {
                                        $createdObjectIDList = eZPreferences::value( 'ObjectCreationIDList' );
                                        if ( $createdObjectIDList && in_array( $contentObject->attribute( 'id' ), unserialize( $createdObjectIDList ) ) )
                                        {
                                            $access = 'allowed';
                                        }
                                    }
                                    else 
                                        if ( $contentObject->attribute( 'owner_id' ) == $userID || $contentObject->attribute( 'id' ) == $userID )
                                        {
                                            $access = 'allowed';
                                        }
                                    if ( $access != 'allowed' )
                                    {
                                        $access = 'denied';
                                        $limitationList = array( 
                                            'Limitation' => $key 
                                        );
                                    }
                                }
                                break;
                            
                            case 'Group':
                            case 'ParentGroup':
                                {
                                    $access = $contentObject->checkGroupLimitationAccess( $valueList, $userID );
                                    
                                    if ( $access != 'allowed' )
                                    {
                                        $access = 'denied';
                                        $limitationList = array( 
                                            'Limitation' => $key , 
                                            'Required' => $valueList 
                                        );
                                    }
                                }
                                break;
                            
                            case 'State':
                                {
                                    if ( count( array_intersect( $valueList, $contentObject->attribute( 'state_id_array' ) ) ) == 0 )
                                    {
                                        $access = 'denied';
                                        $limitationList = array( 
                                            'Limitation' => $key , 
                                            'Required' => $valueList 
                                        );
                                    }
                                    else
                                    {
                                        $access = 'allowed';
                                    }
                                }
                                break;
                            
                            case 'ParentDepth':
                                {
                                    if ( in_array( $currentNode->attribute( 'depth' ), $valueList ) )
                                    {
                                        $access = 'allowed';
                                    }
                                    else
                                    {
                                        $access = 'denied';
                                        $limitationList = array( 
                                            'Limitation' => $key , 
                                            'Required' => $valueList 
                                        );
                                    }
                                }
                                break;
                            
                            case 'Node':
                                {
                                    $accessNode = false;
                                    $mainNodeID = $currentNode->attribute( 'main_node_id' );
                                    foreach ( $valueList as $nodeID )
                                    {
                                        $node = eZContentObjectTreeNode::fetch( $nodeID, false, false );
                                        $limitationNodeID = $node['main_node_id'];
                                        if ( $mainNodeID == $limitationNodeID )
                                        {
                                            $access = 'allowed';
                                            $accessNode = true;
                                            break;
                                        }
                                    }
                                    if ( $access != 'allowed' && $checkedSubtree && ! $accessSubtree )
                                    {
                                        $access = 'denied';
                                        // ??? TODO: if there is a limitation on Subtree, return two limitations?
                                        $limitationList = array( 
                                            'Limitation' => $key , 
                                            'Required' => $valueList 
                                        );
                                    }
                                    else
                                    {
                                        $access = 'allowed';
                                    }
                                    $checkedNode = true;
                                }
                                break;
                            
                            case 'Subtree':
                                {
                                    $accessSubtree = false;
                                    $path = $currentNode->attribute( 'path_string' );
                                    $subtreeArray = $valueList;
                                    foreach ( $subtreeArray as $subtreeString )
                                    {
                                        if ( strstr( $path, $subtreeString ) )
                                        {
                                            $access = 'allowed';
                                            $accessSubtree = true;
                                            break;
                                        }
                                    }
                                    if ( $access != 'allowed' && $checkedNode && ! $accessNode )
                                    {
                                        $access = 'denied';
                                        // ??? TODO: if there is a limitation on Node, return two limitations?
                                        $limitationList = array( 
                                            'Limitation' => $key , 
                                            'Required' => $valueList 
                                        );
                                    }
                                    else
                                    {
                                        $access = 'allowed';
                                    }
                                    $checkedSubtree = true;
                                }
                                break;
                            
                            case 'User_Subtree':
                                {
                                    $path = $currentNode->attribute( 'path_string' );
                                    $subtreeArray = $valueList;
                                    foreach ( $subtreeArray as $subtreeString )
                                    {
                                        if ( strstr( $path, $subtreeString ) )
                                        {
                                            $access = 'allowed';
                                        }
                                    }
                                    if ( $access != 'allowed' )
                                    {
                                        $access = 'denied';
                                        $limitationList = array( 
                                            'Limitation' => $key , 
                                            'Required' => $valueList 
                                        );
                                    }
                                }
                                break;
                            
                            default:
                                {
                                    if ( strncmp( $key, 'StateGroup_', 11 ) === 0 )
                                    {
                                        if ( count( array_intersect( $valueList, $contentObject->attribute( 'state_id_array' ) ) ) == 0 )
                                        {
                                            $access = 'denied';
                                            $limitationList = array( 
                                                'Limitation' => $key , 
                                                'Required' => $valueList 
                                            );
                                        }
                                        else
                                        {
                                            $access = 'allowed';
                                        }
                                    }
                                }
                        }
                        
                        if ( $access == 'denied' )
                        {
                            break;
                        }
                    }
                    
                    $policyList[] = array( 
                        'PolicyID' => $pkey , 
                        'LimitationList' => $limitationList 
                    );
                }
                if ( $access == 'denied' )
                {
                    $accessList = array( 
                        'FunctionRequired' => array( 
                            'Module' => 'content' , 
                            'Function' => $origFunctionName , 
                            'ClassID' => $classID , 
                            'MainNodeID' => $currentNode->attribute( 'main_node_id' ) 
                        ) , 
                        'PolicyList' => $policyList 
                    );
                    return 0;
                }
                else
                {
                    return 1;
                }
            }
    }

    static function _loginUser( $login, $password, $authenticationMatch = false )
    {
        $http = eZHTTPTool::instance();
        $db = eZDB::instance();
        
        if ( $authenticationMatch === false )
            $authenticationMatch = eZUser::authenticationMatch();
        
        $loginEscaped = $db->escapeString( $login );
        $passwordEscaped = $db->escapeString( $password );
        
        $loginArray = array();
        if ( $authenticationMatch & eZUser::AUTHENTICATE_LOGIN )
            $loginArray[] = "login='$loginEscaped'";
        if ( $authenticationMatch & eZUser::AUTHENTICATE_EMAIL )
        {
            if ( eZMail::validate( $login ) )
            {
                $loginArray[] = "email='$loginEscaped'";
            }
        }
        if ( empty( $loginArray ) )
            $loginArray[] = "login='$loginEscaped'";
        $loginText = implode( ' OR ', $loginArray );
        
        $contentObjectStatus = eZContentObject::STATUS_PUBLISHED;
        
        $ini = eZINI::instance();
        $databaseName = $db->databaseName();
        // if mysql
        if ( $databaseName === 'mysql' )
        {
            $query = "SELECT contentobject_id, password_hash, password_hash_type, email, login
                      FROM ezuser, ezcontentobject
                      WHERE ( $loginText ) AND
                        ezcontentobject.status='$contentObjectStatus' AND
                        ezcontentobject.id=contentobject_id AND
                        ( ( password_hash_type!=4 ) OR
                          ( password_hash_type=4 AND
                              ( $loginText ) AND
                          password_hash=PASSWORD('$passwordEscaped') ) )";
        }
        else
        {
            $query = "SELECT contentobject_id, password_hash,
                             password_hash_type, email, login
                      FROM   ezuser, ezcontentobject
                      WHERE  ( $loginText )
                      AND    ezcontentobject.status='$contentObjectStatus'
                      AND    ezcontentobject.id=contentobject_id";
        }
        
        $users = $db->arrayQuery( $query );
        $exists = false;
        if ( $users !== false && isset( $users[0] ) )
        {
            $ini = eZINI::instance();
            foreach ( $users as $userRow )
            {
                $userID = $userRow['contentobject_id'];
                $hashType = $userRow['password_hash_type'];
                $hash = $userRow['password_hash'];
                $exists = eZUser::authenticateHash( $userRow['login'], $password, eZUser::site(), $hashType, $hash );
                
                // If hash type is MySql
                if ( $hashType == eZUser::PASSWORD_HASH_MYSQL and $databaseName === 'mysql' )
                {
                    $queryMysqlUser = "SELECT contentobject_id, password_hash, password_hash_type, email, login
                              FROM ezuser, ezcontentobject
                              WHERE ezcontentobject.status='$contentObjectStatus' AND
                                    password_hash_type=4 AND ( $loginText ) AND password_hash=PASSWORD('$passwordEscaped') ";
                    $mysqlUsers = $db->arrayQuery( $queryMysqlUser );
                    if ( isset( $mysqlUsers[0] ) )
                        $exists = true;
                
                }
                
                eZDebugSetting::writeDebug( 'kernel-user', eZUser::createHash( $userRow['login'], $password, eZUser::site(), $hashType, $hash ), "check hash" );
                eZDebugSetting::writeDebug( 'kernel-user', $hash, "stored hash" );
                // If current user has been disabled after a few failed login attempts.
                $canLogin = eZUser::isEnabledAfterFailedLogin( $userID );
                
                if ( $exists )
                {
                    // We should store userID for warning message.
                    $GLOBALS['eZFailedLoginAttemptUserID'] = $userID;
                    
                    $userSetting = eZUserSetting::fetch( $userID );
                    $isEnabled = $userSetting->attribute( "is_enabled" );
                    if ( $hashType != eZUser::hashType() and strtolower( $ini->variable( 'UserSettings', 'UpdateHash' ) ) == 'true' )
                    {
                        $hashType = eZUser::hashType();
                        $hash = eZUser::createHash( $userRow['login'], $password, eZUser::site(), $hashType );
                        $db->query( "UPDATE ezuser SET password_hash='$hash', password_hash_type='$hashType' WHERE contentobject_id='$userID'" );
                    }
                    break;
                }
            }
        }
        
        if ( $exists and $isEnabled and $canLogin )
        {
            return new eZUser( $userRow );
        }
        else
        {
            return isset( $userID ) ? $userID : false;
        }
    }

    public static function feedAuth( eZContentObjectTreeNode $node, $user )
    {
        
        if ( $user && self::hasAccess($node, $user) )
        {
        	                        
        	return $user;
        
        }
        
        // Special access for PodTrac; use my user ID
        if ( preg_match("/podtrac-10hg0vc9addryq9nrhbs/", $_SERVER['REQUEST_URI'] ) ) {
            return eZUser::fetch(701);
        } elseif ( ! isset( $_SERVER['PHP_AUTH_USER'] ) or ! isset( $_SERVER['PHP_AUTH_PW'] ) ) {
            header( 'WWW-Authenticate: Basic realm="' . $node->getName() . '"' );
            header( 'HTTP/1.0 401 Unauthorized' );
            eZExecution::cleanExit();
        } else {
            if ( preg_match( '(^' . preg_quote( $_SERVER['SERVER_NAME'] ) . '(.+))', $_SERVER['PHP_AUTH_USER'], $matches ) > 0 ) {
                $_SERVER['PHP_AUTH_USER'] = $matches[1];
            }
            
            $user = self::_loginUser( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] );
            
            if ( ! ( $user instanceof eZUser ) or ! self::checkAccess( 'read', $node, $user ) ) {
                header( 'WWW-Authenticate: Basic realm="' . $node->getName() . '"' );
                header( 'HTTP/1.0 401 Unauthorized' );
                eZExecution::cleanExit();
            }
            return $user;
        }
    }

    public static function hasAccess( $node, $user )
    {
        
        if ( !self::checkAccess( 'read', $node, $user ) )
        {
            
            return false;
        
        }
        return true;
    }
}
