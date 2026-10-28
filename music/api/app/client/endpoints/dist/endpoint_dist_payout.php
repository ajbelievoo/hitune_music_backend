<?php

/**
 * Music Distribution - Artist payout requests
 *
 * GET  /api/dist/payout            -> balance + payout history + saved profile
 * POST /api/dist/payout            -> request payout {method, details}
 * POST /api/dist/payout?action=profile -> save payout profile {upi_id, account_holder, account_number, ifsc, paypal_email, stage_name}
 *
 * Available balance = unpaid royalties revenue - pending (requested/processing) payout total.
 * Payout requests always withdraw the full available balance (like TuneCore withdrawals).
 */

if ( !defined("bof_root") ) die;

// dist_payout_balance() and DIST_PAYOUT_MIN are defined in loader.php

function endpoint_dist_payout( $loader, $excuter, $args ){

    $user = $loader->user->check();
    if ( !$user || empty($user->ID) )
        return $loader->api->set_error( "access_denied" );

    $user_id = (int)$user->ID;
    $db = $loader->db;

    if ( $_SERVER['REQUEST_METHOD'] === 'POST' ){
        if ( !empty($_REQUEST['action']) && $_REQUEST['action'] === 'profile' )
            return dist_payout_save_profile( $loader, $db, $user_id );
        return dist_payout_request( $loader, $db, $user_id );
    }

    if ( !empty($_GET['action']) && $_GET['action'] === 'profile' )
        return dist_payout_get_profile( $loader, $db, $user_id );

    return dist_payout_get( $loader, $db, $user_id );
}

function dist_payout_profile_row( $db, $user_id ){
    $r = $db->query("SELECT * FROM `_dist_artist_profiles` WHERE user_id = " . (int)$user_id . " LIMIT 1");
    return ( $r && $r->num_rows ) ? $r->fetch_assoc() : null;
}

function dist_payout_get_profile( $loader, $db, $user_id ){
    $p = dist_payout_profile_row( $db, $user_id );
    $loader->api->set_message( "ok", array( 'profile' => $p ? array(
        'stage_name' => $p['stage_name'],
        'upi_id' => $p['upi_id'],
        'account_holder' => $p['account_holder'],
        'account_number' => $p['account_number'],
        'ifsc' => $p['ifsc'],
        'paypal_email' => $p['paypal_email']
    ) : null ) );
}

function dist_payout_save_profile( $loader, $db, $user_id ){

    $fields = array('stage_name','upi_id','account_holder','account_number','ifsc','paypal_email');
    $vals = array(); $sets = array();
    foreach ( $fields as $f ){
        $v = isset($_POST[$f]) ? trim( (string)$_POST[$f] ) : '';
        $sql_v = ( $v === '' ) ? "NULL" : "'" . $db->real_escape_string( mb_substr($v,0,255) ) . "'";
        $vals[] = $sql_v;
        $sets[] = "{$f} = {$sql_v}";
    }

    // basic sanity checks
    if ( !empty($_POST['ifsc']) && !preg_match( '/^[A-Z]{4}0[A-Z0-9]{6}$/', strtoupper(trim($_POST['ifsc'])) ) )
        return $loader->api->set_error( "invalid_input", array( 'input_name' => 'ifsc', 'code' => 'bad_ifsc' ) );
    if ( !empty($_POST['paypal_email']) && !filter_var( $_POST['paypal_email'], FILTER_VALIDATE_EMAIL ) )
        return $loader->api->set_error( "invalid_input", array( 'input_name' => 'paypal_email', 'code' => 'bad_email' ) );

    $db->query("INSERT INTO `_dist_artist_profiles` (user_id, " . implode(',', $fields) . ")
        VALUES (" . (int)$user_id . ", " . implode(',', $vals) . ")
        ON DUPLICATE KEY UPDATE " . implode(',', $sets));

    $loader->api->set_message( "ok", array( 'saved' => true ) );
}

function dist_payout_get( $loader, $db, $user_id ){

    $balance = dist_payout_balance( $db, $user_id );

    $history = array();
    $r = $db->query("SELECT id, amount, currency, method, details, status, payment_reference, admin_notes, time_add, time_update
        FROM `_dist_payouts` WHERE user_id = {$user_id} ORDER BY time_add DESC LIMIT 50");
    while ( $r && ($row = $r->fetch_assoc()) ){
        $row['id'] = (int)$row['id'];
        $row['amount'] = (float)$row['amount'];
        $history[] = $row;
    }

    $profile = dist_payout_profile_row( $db, $user_id );

    $loader->api->set_message( "ok", array(
        'balance' => $balance,
        'history' => $history,
        'profile' => $profile ? array(
            'stage_name' => $profile['stage_name'],
            'upi_id' => $profile['upi_id'],
            'account_holder' => $profile['account_holder'],
            'account_number' => $profile['account_number'],
            'ifsc' => $profile['ifsc'],
            'paypal_email' => $profile['paypal_email']
        ) : null,
        'methods' => array(
            array( 'value' => 'upi', 'label' => 'UPI' ),
            array( 'value' => 'bank', 'label' => 'Bank Transfer (IMPS/NEFT)' ),
            array( 'value' => 'paypal', 'label' => 'PayPal' )
        )
    ));
}

function dist_payout_request( $loader, $db, $user_id ){

    $method  = !empty($_POST['method']) ? $_POST['method'] : '';
    $details = !empty($_POST['details']) ? trim($_POST['details']) : '';

    // fall back to the saved payout profile when details aren't sent
    if ( in_array( $method, array('upi','bank','paypal'), true ) && !$details ){
        $p = dist_payout_profile_row( $db, $user_id );
        if ( $p ){
            if ( $method === 'upi' && !empty($p['upi_id']) )
                $details = "UPI: " . $p['upi_id'];
            elseif ( $method === 'paypal' && !empty($p['paypal_email']) )
                $details = "PayPal: " . $p['paypal_email'];
            elseif ( $method === 'bank' && !empty($p['account_number']) )
                $details = "Bank: " . trim( ($p['account_holder'] ?: '') . " / A/C " . $p['account_number'] . " / IFSC " . ($p['ifsc'] ?: '') );
        }
    }

    if ( !in_array( $method, array('upi','bank','paypal'), true ) || !$details )
        return $loader->api->set_error( "invalid_input", array( 'code' => 'missing_fields' ) );

    if ( strlen( $details ) > 1000 )
        return $loader->api->set_error( "invalid_input", array( 'code' => 'details_too_long' ) );

    $balance = dist_payout_balance( $db, $user_id );

    if ( $balance['available'] < DIST_PAYOUT_MIN )
        return $loader->api->set_error( "invalid_input", array(
            'code' => 'insufficient_balance',
            'available' => $balance['available'],
            'min' => DIST_PAYOUT_MIN
        ));

    $amount = $balance['available'];
    $method_sql = $db->real_escape_string( $method );
    $details_sql = $db->real_escape_string( $details );

    $db->query("INSERT INTO `_dist_payouts` (user_id, amount, currency, method, details, status)
        VALUES ({$user_id}, {$amount}, 'INR', '{$method_sql}', '{$details_sql}', 'requested')");
    $payout_id = (int)$db->insert_id;

    // log it
    $db->query("INSERT INTO `_dist_logs` (user_id, submission_id, action, details, performed_by)
        VALUES ({$user_id}, NULL, 'payout_requested', 'Payout request #{$payout_id} for INR {$amount} via {$method_sql}', {$user_id})");

    // notify admin
    dist_notify_admin( "New payout request #{$payout_id}",
        "User #{$user_id} requested a payout of INR {$amount} via {$method}.<br><br>Payout details:<br>" . nl2br( htmlspecialchars( $details ) ) );

    $loader->api->set_message( "ok", array(
        'id' => $payout_id,
        'amount' => $amount,
        'balance' => dist_payout_balance( $db, $user_id )
    ));
}

?>
