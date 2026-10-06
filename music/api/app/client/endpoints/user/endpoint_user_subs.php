<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_user_subs( $loader, $excuter, $args ){

  $plans = $loader->object->user_subs_plan->select(
    array(
      "active" => 1
    ),
    array(
      "single" => false,
      "limit" => false,
      "public" => true,
      "order_by" => "priority",
      "select_cleaner" => function( $items, $loader ){

        $new_items = [];
        foreach( $items as $item ){

          if ( empty( $item["_prices"]["min"] ) && empty( $item["free"] ) ) continue;

          $item = array(
            "name" => $item["name"],
            "comment" => $item["comment"],
            "periods" => !empty( $item["_prices"]["original"] ) ? array_keys( $item["_prices"]["original"] ) : null,
            "period_first" => !empty( $item["_prices"]["original"] ) ? array_keys( $item["_prices"]["original"] )[0] : null,
            "prices" => $item["_prices"],
            "hash" => $item["hash"],
            "discount" => $item["discount"],
            "html" => $item["detail_html"],
            "plan_key" => !empty( $item["plan_key"] ) ? $item["plan_key"] : null,
            "trial_days" => !empty( $item["trial_days"] ) ? intval( $item["trial_days"] ) : 0,
            "features" => !empty( $item["features_decoded"] ) ? $item["features_decoded"] : null,
            "features_list" => !empty( $item["features_decoded"] ) && is_array( $item["features_decoded"] )
              ? array_values( array_filter( array_map( function( $fk, $fv ){
                  if ( is_int( $fk ) ? true : !empty( $fv ) ){
                    $_key = is_int( $fk ) ? $fv : $fk;
                    $labels = object_user_subs_plan::feature_keys();
                    return !empty( $labels[ $_key ] ) ? $labels[ $_key ] : ucwords( str_replace( "_", " ", $_key ) );
                  }
                  return null;
                }, array_keys( $item["features_decoded"] ), $item["features_decoded"] ) ) )
              : null,
            "ai_quota" => isset( $item["ai_quota"] ) ? (int) $item["ai_quota"] : null,
            // period => store product id (Google Play / App Store)
            "iap_products" => !empty( $item["iap"] ) && is_array( $item["iap"] )
              ? array_combine(
                  array_map( function( $_v ){ return is_array( $_v ) && !empty( $_v["period"] ) ? $_v["period"] : null; }, array_values( $item["iap"] ) ),
                  array_keys( $item["iap"] )
                )
              : null,
          );

          $new_items[ $item["hash"] ] = $item;

        }

        if ( ( $reqed_plan_k = $loader->nest->user_input( "get", "plan", "md5" ) ) ){
          if ( in_array( $reqed_plan_k, array_keys( $new_items ), true ) ){
            $reqed_plan = $new_items[ $reqed_plan_k ];
            unset( $new_items[ $reqed_plan_k ] );
            $new_items = array_merge( [ $reqed_plan_k => $reqed_plan ], $new_items );
          }
        }

        return $new_items;

      }
    )
  );

  if ( !$plans ) {
    $loader->api->set_error( "no_plans_found", [ "output_args" => [ "turn" => false ] ] );
    return;
  }

  $loader->api->set_message( "ok", array(
    "plans" => $plans,
    "plans_count" => count( $plans ),
    "seo" => array(
      "title" => bof()->object->language->turn( "user_subs_plan", [], [ "uc_first" => true, "lang" => "users" ] )
    )
  ) );

}

?>
