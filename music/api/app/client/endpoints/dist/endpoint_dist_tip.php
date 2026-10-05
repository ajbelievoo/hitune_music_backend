<?php

/**
 * Fan-to-artist tipping wallet (strategy doc §4 "Direct Artist Monetization").
 *
 * GET  /api/dist/tip?action=wallet            -> {balance, earned, tipped}
 * GET  /api/dist/tip?action=history           -> tips sent + received
 * POST /api/dist/tip                          -> send a tip {amount, note?, track_hash?|artist_hash?}
 *
 * Split: artist share = amount * artist_pct/100. artist_pct is 100 during a
 * "Creator Day" (bof setting `tip_artist_pct` / `tip_event_until`).
 */

if ( !defined("bof_root") ) die;

function endpoint_dist_tip( $loader, $excuter, $args ){

    $user = $loader->user->check();
    if ( !$user || empty($user->ID) )
        return $loader->api->set_error( "access_denied" );

    $user_id = (int)$user->ID;
    $db = $loader->db;

    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'wallet';

    if ( $_SERVER['REQUEST_METHOD'] === 'POST' ){
        if ( $action === 'topup' ) return dist_tip_topup_create( $loader, $db, $user_id );
        return dist_tip_send( $loader, $db, $user_id );
    }

    if ( $action === 'history' ) return dist_tip_history( $loader, $db, $user_id );
    if ( $action === 'topup_status' ) return dist_tip_topup_status( $loader, $db, $user_id );
    return dist_tip_wallet( $loader, $db, $user_id );
}

/**
 * POST ?action=topup {amount} -> Razorpay payment link for wallet credit.
 * GET  ?action=topup_status&topup_id= -> verify payment & credit wallet.
 */
function dist_tip_topup_create( $loader, $db, $user_id ){

    $amount = round( (float)( $_REQUEST['amount'] ?? 0 ), 2 );
    if ( $amount < 10 || $amount > 100000 )
        return $loader->api->set_error( 'invalid_amount', array( 'message' => 'Top-up must be between 10 and 100000' ) );

    if ( !bof()->object->db_setting->get( "gateway_razorpay" ) || !bof()->object->db_setting->get( "gateway_razorpay_id" ) )
        return $loader->api->set_error( 'failed', array( 'message' => 'Payment gateway not configured' ) );

    $db->_insert( '_htx_topups', array( 'user_id' => $user_id, 'amount' => $amount ) );
    $topup_id = (int)$db->insert_id();
    if ( !$topup_id )
        return $loader->api->set_error( 'failed', array( 'message' => 'topup_create_failed' ) );

    $callback = web_address . "api/dist/tip?action=topup_status&topup_id=" . $topup_id;
    $link = bof()->pgt_razorpay->get_link(
        $amount,
        array( 'iso_code' => 'INR' ),
        "WALLETTOP{$topup_id}",
        $callback
    );
    if ( !$link || empty($link['output']['link']) )
        return $loader->api->set_error( 'payment_failed', array( 'message' => 'payment_init_failed' ) );

    $txn = $db->real_escape_string( $link['txn'] );
    $db->query( "UPDATE `_htx_topups` SET transaction_id = '{$txn}' WHERE id = {$topup_id}" );

    $loader->api->set_message( 'ok', array( 'topup_id' => $topup_id, 'payment_url' => $link['output']['link'] ) );
}

function dist_tip_topup_status( $loader, $db, $user_id ){

    $topup_id = (int)( $_GET['topup_id'] ?? 0 );
    $r = $db->query( "SELECT * FROM `_htx_topups` WHERE id = {$topup_id} AND user_id = {$user_id} LIMIT 1" );
    if ( !$r || !$r->num_rows ) return $loader->api->set_error( 'not_found' );
    $t = $r->fetch_assoc();

    if ( $t['status'] === 'pending' && !empty($t['transaction_id']) ){
        try {
            $check = bof()->pgt_razorpay->check_payment( array( 'gateway_id' => $t['transaction_id'] ) );
        } catch ( Exception $e ) { $check = null; }
        if ( $check && !empty($check['amount']) && (float)$check['amount'] >= (float)$t['amount'] ){
            $amt = (float)$t['amount'];
            $db->query( "UPDATE `_htx_topups` SET status = 'paid' WHERE id = {$topup_id} AND status = 'pending'" );
            $db->query( "INSERT INTO `_htx_wallet` (user_id, balance) VALUES ({$user_id}, {$amt})
                ON DUPLICATE KEY UPDATE balance = balance + {$amt}" );
            $t['status'] = 'paid';
        }
    }

    $loader->api->set_message( 'ok', array(
        'topup_id' => $topup_id,
        'status'   => $t['status'],
        'amount'   => (float)$t['amount'],
    ) );
}

function dist_tip_artist_pct( $loader ){
    $pct = 80;
    $until = bof()->object->db_setting->get( 'tip_event_until' );
    if ( $until && strtotime( $until ) > time() ) $pct = 100; // Creator Day window
    else {
        $conf = (int)bof()->object->db_setting->get( 'tip_artist_pct' );
        if ( $conf >= 1 && $conf <= 100 ) $pct = $conf;
    }
    return $pct;
}

function dist_tip_wallet_row( $db, $user_id ){
    $r = $db->query( "SELECT * FROM `_htx_wallet` WHERE user_id = " . (int)$user_id . " LIMIT 1" );
    return ( $r && $r->num_rows ) ? $r->fetch_assoc() : null;
}

function dist_tip_wallet( $loader, $db, $user_id ){

    $w = dist_tip_wallet_row( $db, $user_id );
    $earned = 0; $tipped = 0;
    $r = $db->query( "SELECT COALESCE(SUM(artist_amount),0) s FROM `_htx_tips` WHERE to_user_id = {$user_id}" );
    if ( $r && $r->num_rows ) $earned = (float)$r->fetch_assoc()['s'];
    $r = $db->query( "SELECT COALESCE(SUM(amount),0) s FROM `_htx_tips` WHERE from_user_id = {$user_id}" );
    if ( $r && $r->num_rows ) $tipped = (float)$r->fetch_assoc()['s'];

    $loader->api->set_message( 'ok', array(
        'balance'      => $w ? (float)$w['balance'] : 0.0,
        'tips_earned'  => $earned,
        'tips_sent'    => $tipped,
        'artist_pct'   => dist_tip_artist_pct( $loader ),
        'currency'     => 'INR',
    ) );
}

function dist_tip_history( $loader, $db, $user_id ){

    $sent = array(); $received = array();
    $r = $db->query( "SELECT t.*, u.username AS to_name FROM `_htx_tips` t
        LEFT JOIN `_u_list` u ON u.ID = t.to_user_id
        WHERE t.from_user_id = {$user_id} ORDER BY t.id DESC LIMIT 50" );
    if ( $r ) while ( $x = $r->fetch_assoc() ) $sent[] = $x;

    $r = $db->query( "SELECT t.*, u.username AS from_name FROM `_htx_tips` t
        LEFT JOIN `_u_list` u ON u.ID = t.from_user_id
        WHERE t.to_user_id = {$user_id} ORDER BY t.id DESC LIMIT 50" );
    if ( $r ) while ( $x = $r->fetch_assoc() ) $received[] = $x;

    $loader->api->set_message( 'ok', array( 'sent' => $sent, 'received' => $received ) );
}

function dist_tip_send( $loader, $db, $user_id ){

    $amount = round( (float)( $_REQUEST['amount'] ?? 0 ), 2 );
    $note   = substr( trim( (string)( $_REQUEST['note'] ?? '' ) ), 0, 255 );
    if ( $amount < 1 || $amount > 100000 )
        return $loader->api->set_error( 'invalid_amount', array( 'message' => 'Tip must be between 1 and 100000' ) );

    $to_user_id = 0; $track_id = null; $label = '';
    if ( !empty($_REQUEST['track_hash']) ){
        $hash = $db->escape( $_REQUEST['track_hash'] );
        $r = $db->query( "SELECT t.ID, t.uploader_id, a.manager_id FROM `_c_m_tracks` t
            LEFT JOIN `_c_m_artists` a ON a.ID = t.artist_id WHERE t.hash = '{$hash}' LIMIT 1" );
        if ( !$r || !$r->num_rows ) return $loader->api->set_error( 'not_found', array( 'message' => 'Track not found' ) );
        $t = $r->fetch_assoc();
        $to_user_id = (int)( $t['uploader_id'] ?: $t['manager_id'] );
        $track_id   = (int)$t['ID'];
        $label = 'track';
    } elseif ( !empty($_REQUEST['artist_hash']) ){
        $hash = $db->escape( $_REQUEST['artist_hash'] );
        $r = $db->query( "SELECT ID, manager_id FROM `_c_m_artists` WHERE hash = '{$hash}' LIMIT 1" );
        if ( !$r || !$r->num_rows ) return $loader->api->set_error( 'not_found', array( 'message' => 'Artist not found' ) );
        $a = $r->fetch_assoc();
        $to_user_id = (int)$a['manager_id'];
        $label = 'artist';
    }

    if ( !$to_user_id )
        return $loader->api->set_error( 'invalid_request', array( 'message' => 'No payable recipient for this ' . ( $label ?: 'item' ) ) );
    if ( $to_user_id === $user_id )
        return $loader->api->set_error( 'invalid_request', array( 'message' => 'You cannot tip yourself' ) );

    $w = dist_tip_wallet_row( $db, $user_id );
    $balance = $w ? (float)$w['balance'] : 0.0;
    if ( $balance < $amount )
        return $loader->api->set_error( 'insufficient_funds', array( 'balance' => $balance ) );

    $pct = dist_tip_artist_pct( $loader );
    $artist_amt   = round( $amount * $pct / 100, 2 );
    $platform_amt = round( $amount - $artist_amt, 2 );

    $db->query( "UPDATE `_htx_wallet` SET balance = balance - {$amount} WHERE user_id = {$user_id}" );
    $db->query( "INSERT INTO `_htx_wallet` (user_id, balance) VALUES ({$to_user_id}, {$artist_amt})
        ON DUPLICATE KEY UPDATE balance = balance + {$artist_amt}" );
    $db->_insert( '_htx_tips', array(
        'from_user_id'    => $user_id,
        'to_user_id'      => $to_user_id,
        'track_id'        => $track_id,
        'amount'          => $amount,
        'artist_amount'   => $artist_amt,
        'platform_amount' => $platform_amt,
        'note'            => $note ?: null,
    ) );

    $loader->api->set_message( 'tip_sent', array(
        'amount'        => $amount,
        'artist_amount' => $artist_amt,
        'artist_pct'    => $pct,
        'balance'       => round( $balance - $amount, 2 ),
    ) );
}
