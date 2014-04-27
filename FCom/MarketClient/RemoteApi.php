<?php

final class FCom_MarketClient_RemoteApi extends BClass
{
    protected static $_modulesVersionsCacheKey = 'marketclient_modules_versions';

    #protected $_apiUrl = 'https://market.sellvana.com/';
    protected $_apiUrl = 'http://market.sellvana.com/';

    public function getUrl( $path = '', $params = [] )
    {
        $url = $this->_apiUrl;
        $url .= ltrim( $path, '/' );
        if ( $params ) {
            $url = BUtil::setUrlQuery( $url, $params );
        }
        return $url;
    }

    public function setupConnection()
    {
        $siteKey = BConfig::i()->get( 'modules/FCom_MarketClient/site_key' );
        $url = $this->getUrl( 'api/v1/market/site/connect', [
            'admin_url' => BApp::href(),
            'retry_url' => BApp::href( 'marketclient/site/connect' ),
            'site_key' => $siteKey,
        ] );
        $response = BUtil::remoteHttp( 'GET', $url );
        $result = BUtil::fromJson( $response );
        if ( !empty( $result[ 'site_key' ] ) ) {
            BConfig::i()->set( 'modules/FCom_MarketClient/site_key', $result[ 'site_key' ], false, true );
            FCom_Core_Main::i()->writeConfigFiles( 'local' );
        }
        return $result;
    }

    public function getModulesVersions( $modules, $resetCache = false )
    {
        $result = BCache::i()->load( static::$_modulesVersionsCacheKey );
        if ( $result && true === $modules && !$resetCache ) {
            return $result;
        }
        if ( !$result ) {
            $result = [];
        }

        if ( true === $modules ) {
            $modules = join( ',', array_keys( BModuleRegistry::i()->getAllModules() ) );
        }

        $siteKey = BConfig::i()->get( 'modules/FCom_MarketClient/site_key' );
        $url = $this->getUrl( 'api/v1/market/module/version', [
            'mod_name' => $modules,
            'site_key' => $siteKey,
        ] );
        $response = BUtil::remoteHttp( "GET", $url );
#echo "<pre>"; var_dump($response); exit;
        $modResult = BUtil::fromJson( $response );
        if ( !empty( $modResult[ 'error' ] ) ) {
            BCache::i()->delete( static::$_modulesVersionsCacheKey );
            throw new BException( $modResult[ 'message' ] );
        }
        $result = BUtil::arrayMerge( $result, $modResult[ 'modules' ] );
        foreach ( $result as $modName => &$mod ) {
            $mod[ 'name' ] = $modName;
            if ( !empty( $mod[ 'status' ] ) && $mod[ 'status' ] === 'mine' ) {
                $localMod = BApp::m( $modName );
                $remChannelVer = $mod[ 'channels' ][ $localMod->channel ][ 'version_uploaded' ];
                $mod[ 'can_update' ] = version_compare( $remChannelVer, $localMod->version, '<' );
            }
        }
        unset( $mod );
        if ( !empty( $result ) ) {
            BCache::i()->save( static::$_modulesVersionsCacheKey, $result, 86400 );
        }

        return $result;
    }

    public function getModuleInstallInfo( $modules )
    {
        $url = $this->getUrl( 'api/v1/market/module/install_info', [
            'mod_name' => $modules,
        ] );
        $response = BUtil::remoteHttp( "GET", $url );
#var_dump($response); exit;
        $result = BUtil::fromJson( $response );
        if ( !empty( $result[ 'error' ] ) ) {
            throw new BException( $result[ 'message' ] );
        }
        $modules = $result[ 'modules' ];
        foreach ( $modules as $modName => &$modInfo ) {
            $localMod = BApp::m( $modName );
            $modInfo[ 'local_channel' ] = $localMod ? $localMod->channel : null;
            $modInfo[ 'local_version' ] = $localMod ? $localMod->version : null;
            if ( $localMod ) {
                if ( $modInfo[ 'status' ] === 'dependency' ) {
                    if ( version_compare( $localMod->version, $modInfo[ 'version' ], '<' ) ) {
                        $modInfo[ 'status' ] = 'upgrade';
                    } else {
                        #unset($result[$modName]);
                        $modInfo[ 'status' ] = 'latest';
                    }
                }
            } else {
                $modInfo[ 'status' ] = 'install';
            }
        }
        unset( $modInfo );
        return $modules;
    }

    public function createModule( $modName )
    {
        $siteKey = BConfig::i()->get( 'modules/FCom_MarketClient/site_key' );
        $url = $this->getUrl( 'api/v1/market/module/create' );
        $data = [
            'site_key' => $siteKey,
            'mod_name' => $modName,
        ];
        $response = BUtil::remoteHttp( 'POST', $url, $data );
        return BUtil::fromJson( $response );
    }

    public function uploadPackage( $moduleName )
    {
        $mod = BModuleRegistry::i()->module( $moduleName );
        if ( !$mod ) {
            return [ 'error' => true, 'message' => 'Invalid package: ' . $moduleName ];
        }
        $packageDir = BConfig::i()->get( 'fs/storage_dir' ) . '/marketclient/upload';
        BUtil::ensureDir( $packageDir );
        $packageFilename = "{$packageDir}/{$moduleName}-{$mod->version}.zip";
        @unlink( $packageFilename );
        BUtil::zipCreateFromDir( $packageFilename, $mod->root_dir );
        $siteKey = BConfig::i()->get( 'modules/FCom_MarketClient/site_key' );
        $url = $this->getUrl( 'api/v1/market/module/upload' );
        $data = [
            'site_key' => $siteKey,
            'mod_name' => $moduleName,
            'package_zip' => '@' . $packageFilename,
        ];
        $response = BUtil::remoteHttp( 'POST', $url, $data );
#echo "<pre>"; var_dump($response); exit;
        BCache::i()->delete( static::$_modulesVersionsCacheKey );
        return BUtil::fromJson( $response );
    }

    public function downloadPackage( $moduleName, $version = null, $channel = null )
    {
        if ( $version === '*' ) {
            $version = null;
        }
        if ( $channel === '*' ) {
            $channel = null;
        }
        $url = $this->getUrl( 'api/v1/market/module/download', [
            'mod_name' => $moduleName,
            'version' => $version,
            'channel' => $channel,
        ] );
        $response = BUtil::remoteHttp( "GET", $url );
        if ( !$response ) {
            throw new BException( "Problem downloading the package ({$moduleName})" );
        }
        $dir = BConfig::i()->get( 'fs/storage_dir' ) . '/marketclient/download';
        BUtil::ensureDir( $dir );
        if ( !is_writable( $dir ) ) {
            throw new BException( "Problem with write permissions ({$dir})" );
        }

        $filename = $moduleName . '.zip';
        $reqInfo = BUtil::lastRemoteHttpInfo();
        if ( preg_match( '#;\s*filename=(.*)$#i', $reqInfo[ 'headers' ][ 'content-disposition' ], $m ) ) {
            $filename = $m[ 1 ];
        }
        $filepath = $dir . '/' . $filename;
        if ( file_put_contents( $filepath, $response ) ) {
            return $filepath;
        } else {
            throw new BException( "Problem with write permissions ({$filepath})" );
        }
    }
}
