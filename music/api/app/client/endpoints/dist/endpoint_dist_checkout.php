<?php

/**
 * Music Distribution - Subscription Checkout
 * Handles plan info, Razorpay payment link creation and payment verification
 *
 * GET  /api/dist/checkout?plan=<slug>            -> plan + login/gateway state
 * GET  /api/dist/checkout?sub_id=<id>            -> subscription status (owner only)
 * GET  /api/dist/checkout?sub_id=<id>&razorpay_payment_link_id=... -> Razorpay callback
 * POST /api/dist/checkout  (plan=<slug>)         -> create pending sub + payment link
 */

if ( !defined("bof_root") ) die;

function endpoint_dist_checkout( $loader, $excuter, $args ){

    $db = $loader->db;
    $user = $loader->user->check();
    $user_id = ( $user && !empty($user->ID) ) ? (int)$user->ID : 0;

    if ( $_SERVER['REQUEST_METHOD'] === 'GET' ){

        // Subscription status / Razorpay callback
        if ( !empty($_GET['sub_id']) ){
            return dist_checkout_status( $loader, (int)$_GET['sub_id'], $user_id );
        }

        // Plan info
        $plan_slug = dist_checkout_plan_slug( !empty($_GET['plan']) ? $_GET['plan'] : '' );
        $plan = dist_checkout_get_plan( $db, $plan_slug );
        if ( !$plan )
            return $loader->api->set_error( "not_found", array( 'code' => 'plan_not_found' ) );

        $active_sub = null;
        if ( $user_id ){
            $sq = $db->query("SELECT s.*, p.name as plan_name, p.slug as plan_slug
                FROM `_dist_subscriptions` s
                JOIN `_dist_plans` p ON s.plan_id = p.id
                WHERE s.user_id = {$user_id} AND s.status = 'active' AND s.end_date >= CURDATE()
                ORDER BY s.id DESC LIMIT 1");
            if ( $sq && $sq->num_rows )
                $active_sub = $sq->fetch_assoc();
        }

        $loader->api->set_message( "ok", array(
            'logged_in' => $user_id ? true : false,
            'plan' => array(
                'id' => (int)$plan['id'],
                'name' => $plan['name'],
                'slug' => $plan['slug'],
                'price' => (float)$plan['price_yearly'],
                'currency' => $plan['currency'],
                'features' => json_decode( $plan['features'], true )
            ),
            'gateway_ready' => dist_checkout_gateway_ready(),
            'active_subscription' => $active_sub ? array(
                'id' => (int)$active_sub['id'],
                'plan_name' => $active_sub['plan_name'],
                'end_date' => $active_sub['end_date']
            ) : null
        ));
        return;
    }

    if ( $_SERVER['REQUEST_METHOD'] === 'POST' ){

        if ( !$user_id )
            return $loader->api->set_error( "access_denied", array( 'code' => 'login_required' ) );

        $plan_slug = dist_checkout_plan_slug( !empty($_POST['plan']) ? $_POST['plan'] : '' );
        $plan = dist_checkout_get_plan( $db, $plan_slug );
        if ( !$plan )
            return $loader->api->set_error( "not_found", array( 'code' => 'plan_not_found' ) );

        // Block if an active subscription already exists
        $sq = $db->query("SELECT id FROM `_dist_subscriptions`
            WHERE user_id = {$user_id} AND status = 'active' AND end_date >= CURDATE() LIMIT 1");
        if ( $sq && $sq->num_rows )
            return $loader->api->set_error( "have_access_already_tip", array( 'code' => 'already_subscribed' ) );

        if ( !dist_checkout_gateway_ready() )
            return $loader->api->set_error( "payment_failed", array( 'code' => 'gateway_not_configured' ) );

        // Cancel any stale pending subscriptions for this user+plan
        $db->query("UPDATE `_dist_subscriptions` SET status = 'cancelled'
            WHERE user_id = {$user_id} AND status = 'pending' AND payment_status != 'paid'");

        // Create pending subscription
        $plan_id = (int)$plan['id'];
        $amount = (float)$plan['price_yearly'];
        $db->query("INSERT INTO `_dist_subscriptions`
            (user_id, plan_id, status, payment_status, amount_paid)
            VALUES ({$user_id}, {$plan_id}, 'pending', 'unpaid', {$amount})");
        $sub_id = (int)$db->insert_id;
        if ( !$sub_id )
            return $loader->api->set_error( "failed", array( 'code' => 'subscription_create_failed' ) );

        // Razorpay payment link
        $callback = web_address . "api/dist/checkout?sub_id=" . $sub_id;
        $link = bof()->pgt_razorpay->get_link(
            $amount,
            array( 'iso_code' => !empty($plan['currency']) ? $plan['currency'] : 'INR' ),
            "DISTSUB{$sub_id}",
            $callback
        );

        if ( !$link || empty($link['output']['link']) )
            return $loader->api->set_error( "payment_failed", array( 'code' => 'payment_init_failed' ) );

        $plink_id = $db->real_escape_string( $link['txn'] );
        $db->query("UPDATE `_dist_subscriptions` SET transaction_id = '{$plink_id}' WHERE id = {$sub_id}");

        $loader->api->set_message( "ok", array(
            'subscription_id' => $sub_id,
            'payment_url' => $link['output']['link']
        ));
        return;
    }

    return $loader->api->set_error( "invalid_input", array( 'code' => 'method_not_allowed' ) );
}

/**
 * Map legacy/external slugs to DB slugs
 */
function dist_checkout_plan_slug( $slug ){
    $slug = strtolower( trim( (string)$slug ) );
    $map = array(
        'rising-artist' => 'rising',
        'breakout-artist' => 'breakout',
        'professional' => 'professional',
        'rising' => 'rising',
        'breakout' => 'breakout'
    );
    return isset($map[$slug]) ? $map[$slug] : $slug;
}

function dist_checkout_get_plan( $db, $slug ){
    $slug = $db->real_escape_string( $slug );
    $q = $db->query("SELECT * FROM `_dist_plans` WHERE slug = '{$slug}' AND is_active = 1 LIMIT 1");
    return ( $q && $q->num_rows ) ? $q->fetch_assoc() : null;
}

function dist_checkout_gateway_ready(){
    if ( !bof()->object->db_setting->get( "gateway_razorpay" ) ) return false;
    if ( !bof()->object->db_setting->get( "gateway_razorpay_id" ) ) return false;
    if ( !bof()->object->db_setting->get( "gateway_razorpay_key" ) ) return false;
    return true;
}

/**
 * GET ?sub_id handler: status check (owner) or Razorpay callback redirect
 */
function dist_checkout_status( $loader, $sub_id, $user_id ){

    $db = $loader->db;
    $sq = $db->query("SELECT s.*, p.name as plan_name, p.slug as plan_slug, p.price_yearly
        FROM `_dist_subscriptions` s
        JOIN `_dist_plans` p ON s.plan_id = p.id
        WHERE s.id = {$sub_id} LIMIT 1");
    $sub = ( $sq && $sq->num_rows ) ? $sq->fetch_assoc() : null;
    if ( !$sub )
        return $loader->api->set_error( "not_found", array( 'code' => 'sub_not_found' ) );

    $rzp_link_id = !empty($_GET['razorpay_payment_link_id']) ? $_GET['razorpay_payment_link_id'] : null;
    $is_callback = !empty($rzp_link_id);
    $is_owner = ( $user_id && $user_id === (int)$sub['user_id'] );

    if ( !$is_owner && !$is_callback )
        return $loader->api->set_error( "access_denied", array( 'code' => 'access_denied' ) );

    if ( $is_callback && $rzp_link_id !== $sub['transaction_id'] )
        return $loader->api->set_error( "invalid_input", array( 'code' => 'invalid_callback' ) );

    // Verify payment with Razorpay if still pending
    if ( $sub['status'] !== 'active' && !empty($sub['transaction_id']) ){
        $payment_id = !empty($_GET['razorpay_payment_id']) ? $_GET['razorpay_payment_id'] : null;
        dist_checkout_verify( $db, $sub, $payment_id );
        $sq = $db->query("SELECT s.*, p.name as plan_name, p.slug as plan_slug, p.price_yearly
            FROM `_dist_subscriptions` s
            JOIN `_dist_plans` p ON s.plan_id = p.id
            WHERE s.id = {$sub_id} LIMIT 1");
        $sub = ( $sq && $sq->num_rows ) ? $sq->fetch_assoc() : $sub;
    }

    // Razorpay browser callback -> redirect back to the subscribe page
    if ( $is_callback ){
        $status = ( $sub['status'] === 'active' ) ? 'success' : 'failed';
        header( "Location: " . web_address . "distribution/subscribe/" . $sub['plan_slug'] . "?status={$status}&sub_id={$sub_id}" );
        exit;
    }

    $loader->api->set_message( "ok", array(
        'subscription' => array(
            'id' => (int)$sub['id'],
            'plan_name' => $sub['plan_name'],
            'status' => $sub['status'],
            'payment_status' => $sub['payment_status'],
            'end_date' => $sub['end_date']
        )
    ));
}

/**
 * Verify the Razorpay payment link is paid and activate the subscription
 */
function dist_checkout_verify( $db, $sub, $payment_id = null ){

    try {
        $check = bof()->pgt_razorpay->check_payment( array( 'gateway_id' => $sub['transaction_id'] ) );
    } catch ( Exception $e ) {
        return false;
    }

    if ( !$check || empty($check['amount']) )
        return false;
    if ( (float)$check['amount'] < (float)$sub['price_yearly'] )
        return false;

    $sub_id = (int)$sub['id'];
    $txn = $payment_id ? $db->real_escape_string($payment_id) : $db->real_escape_string($sub['transaction_id']);
    $db->query("UPDATE `_dist_subscriptions` SET
        status = 'active',
        payment_status = 'paid',
        start_date = CURDATE(),
        end_date = DATE_ADD(CURDATE(), INTERVAL 1 YEAR),
        transaction_id = '{$txn}'
        WHERE id = {$sub_id} AND status != 'active'");

    return true;
}
