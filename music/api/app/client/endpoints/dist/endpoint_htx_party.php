<?php

/**
 * Live listening parties (strategy doc §8 "Artist Choice & Radio Stations...
 * live listening parties").
 *
 * GET  /api/htx/party                     -> list live parties
 * GET  /api/htx/party?action=state&hash=  -> room state (current track + position)
 * POST /api/htx/party?action=create       -> {name, track_hash?}
 * POST /api/htx/party?action=join&hash=
 * POST /api/htx/party?action=leave&hash=
 * POST /api/htx/party?action=set_track&hash=&track_hash=&position_sec=  (host only)
 * POST /api/htx/party?action=end&hash=                                  (host only)
 */

if ( !defined("bof_root") ) die;

function endpoint_htx_party( $loader, $excuter, $args ){

    $db = $loader->db;
    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'list';

    if ( $_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list' )
        return htx_party_list( $loader, $db );

    $user = $loader->user->check();
    if ( !$user || empty($user->ID) )
        return $loader->api->set_error( "access_denied" );
    $user_id = (int)$user->ID;

    switch ( $action ){
        case 'state':     return htx_party_state( $loader, $db, $user_id );
        case 'create':    return htx_party_create( $loader, $db, $user_id );
        case 'join':      return htx_party_join( $loader, $db, $user_id );
        case 'leave':     return htx_party_leave( $loader, $db, $user_id );
        case 'set_track': return htx_party_set_track( $loader, $db, $user_id );
        case 'end':       return htx_party_end( $loader, $db, $user_id );
    }
    $loader->api->set_error( 'invalid_request', array( 'message' => 'unknown action' ) );
}

function htx_party_row( $db, $hash ){
    $h = $db->escape( $hash );
    $r = $db->query( "SELECT * FROM `_htx_party` WHERE hash = '{$h}' LIMIT 1" );
    return ( $r && $r->num_rows ) ? $r->fetch_assoc() : null;
}

function htx_party_public( $db, $p ){
    $track = null;
    if ( !empty($p['track_id']) ){
        $r = $db->query( "SELECT t.hash, t.title, a.name AS artist FROM `_c_m_tracks` t
            LEFT JOIN `_c_m_artists` a ON a.ID = t.artist_id WHERE t.ID = " . (int)$p['track_id'] . " LIMIT 1" );
        if ( $r && $r->num_rows ) $track = $r->fetch_assoc();
    }
    $u = $db->query( "SELECT username FROM `_u_list` WHERE ID = " . (int)$p['host_user_id'] . " LIMIT 1" );
    return array(
        'hash'        => $p['hash'],
        'name'        => $p['name'],
        'host'        => ( $u && $u->num_rows ) ? $u->fetch_assoc()['username'] : null,
        'track'       => $track,
        'position_sec'=> (int)$p['position_sec'],
        'state_at'    => $p['state_at'],
        'status'      => $p['status'],
        'listeners'   => (int)$p['s_listeners'],
    );
}

function htx_party_list( $loader, $db ){
    $items = array();
    $r = $db->query( "SELECT * FROM `_htx_party` WHERE status = 'live' ORDER BY s_listeners DESC, id DESC LIMIT 50" );
    if ( $r ) while ( $p = $r->fetch_assoc() ) $items[] = htx_party_public( $db, $p );
    $loader->api->set_message( 'ok', array( 'parties' => $items ) );
}

function htx_party_state( $loader, $db, $user_id ){
    $p = htx_party_row( $db, $_GET['hash'] ?? '' );
    if ( !$p ) return $loader->api->set_error( 'not_found' );
    $loader->api->set_message( 'ok', array( 'party' => htx_party_public( $db, $p ) ) );
}

function htx_party_create( $loader, $db, $user_id ){
    $name = substr( trim( (string)( $_REQUEST['name'] ?? '' ) ), 0, 120 );
    if ( !strlen($name) ) return $loader->api->set_error( 'invalid_request', array( 'message' => 'name required' ) );

    $track_id = null;
    if ( !empty($_REQUEST['track_hash']) ){
        $h = $db->escape( $_REQUEST['track_hash'] );
        $r = $db->query( "SELECT ID FROM `_c_m_tracks` WHERE hash = '{$h}' LIMIT 1" );
        if ( $r && $r->num_rows ) $track_id = (int)$r->fetch_assoc()['ID'];
    }

    $hash = md5( uniqid( 'party', true ) . $user_id );
    $db->_insert( '_htx_party', array(
        'hash' => $hash, 'host_user_id' => $user_id, 'name' => $name, 'track_id' => $track_id,
    ) );
    $loader->api->set_message( 'party_created', array( 'hash' => $hash ) );
}

function htx_party_join( $loader, $db, $user_id ){
    $p = htx_party_row( $db, $_REQUEST['hash'] ?? '' );
    if ( !$p || $p['status'] !== 'live' ) return $loader->api->set_error( 'not_found' );
    $db->query( "INSERT IGNORE INTO `_htx_party_listeners` (party_id, user_id) VALUES (" . (int)$p['id'] . ", {$user_id})" );
    $db->query( "UPDATE `_htx_party` SET s_listeners = (SELECT COUNT(*) FROM `_htx_party_listeners` WHERE party_id = " . (int)$p['id'] . ") WHERE id = " . (int)$p['id'] );
    $p = htx_party_row( $db, $p['hash'] );
    $loader->api->set_message( 'joined', array( 'party' => htx_party_public( $db, $p ) ) );
}

function htx_party_leave( $loader, $db, $user_id ){
    $p = htx_party_row( $db, $_REQUEST['hash'] ?? '' );
    if ( !$p ) return $loader->api->set_error( 'not_found' );
    $db->query( "DELETE FROM `_htx_party_listeners` WHERE party_id = " . (int)$p['id'] . " AND user_id = {$user_id}" );
    $db->query( "UPDATE `_htx_party` SET s_listeners = (SELECT COUNT(*) FROM `_htx_party_listeners` WHERE party_id = " . (int)$p['id'] . ") WHERE id = " . (int)$p['id'] );
    $loader->api->set_message( 'left' );
}

function htx_party_set_track( $loader, $db, $user_id ){
    $p = htx_party_row( $db, $_REQUEST['hash'] ?? '' );
    if ( !$p ) return $loader->api->set_error( 'not_found' );
    if ( (int)$p['host_user_id'] !== $user_id ) return $loader->api->set_error( 'forbidden' );
    $h = $db->escape( $_REQUEST['track_hash'] ?? '' );
    $r = $db->query( "SELECT ID FROM `_c_m_tracks` WHERE hash = '{$h}' LIMIT 1" );
    if ( !$r || !$r->num_rows ) return $loader->api->set_error( 'not_found', array( 'message' => 'track not found' ) );
    $tid = (int)$r->fetch_assoc()['ID'];
    $pos = max( 0, (int)( $_REQUEST['position_sec'] ?? 0 ) );
    $db->query( "UPDATE `_htx_party` SET track_id = {$tid}, position_sec = {$pos}, state_at = NOW() WHERE id = " . (int)$p['id'] );
    $loader->api->set_message( 'track_set', array( 'track_id' => $tid, 'position_sec' => $pos ) );
}

function htx_party_end( $loader, $db, $user_id ){
    $p = htx_party_row( $db, $_REQUEST['hash'] ?? '' );
    if ( !$p ) return $loader->api->set_error( 'not_found' );
    if ( (int)$p['host_user_id'] !== $user_id ) return $loader->api->set_error( 'forbidden' );
    $db->query( "UPDATE `_htx_party` SET status = 'ended' WHERE id = " . (int)$p['id'] );
    $db->query( "DELETE FROM `_htx_party_listeners` WHERE party_id = " . (int)$p['id'] );
    $loader->api->set_message( 'party_ended' );
}
