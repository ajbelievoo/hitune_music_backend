<?php

if ( !defined( "root" ) ) die;

class db extends mysqli {

	protected $do_cache = true;
	protected $cache = array();
	protected $connected = false;
	protected $log = false;
	protected $auth_data = null;
	protected $__count = 0;
	protected $__exe_time = 0;
	protected $__cache_count = 0;
	protected $__cache_exe_time = 0;
	protected $__exe_time_tables = [];
	protected $__exe_query_times = [];

	// connectors
	public function __set_auth( $args ){

		if ( empty( $args["host"] ) || empty( $args["user"] ) || empty( $args["name"] ) || !isset( $args["pass"] ) )
		fall("Invalid database args");

		$this->auth_data = $args;
		return true;

	}
	public function __connect(){

		if ( empty( $this->auth_data ) )
		fall("Invalid database args");

		$args = $this->auth_data;
		$host = null;
		$user = null;
		$pass = null;
		$name = null;
		$char = "utf8mb4";
		extract( $args );

		// Try to connect to DB
		parent::__construct( $host, $user, $pass, $name );

		if( mysqli_connect_errno() )
		return mysqli_connect_error();

		parent::set_charset($char);
		$this->connected = true;

		return true;

	}
	public function __get_stats(){
		return array(
			"count" => $this->__count,
			"time"  => $this->__exe_time,
			"cache_count" => $this->__cache_count,
			"cache_time" => $this->__cache_exe_time,
			"tables" => $this->__exe_time_tables,
			"queries" => $this->__exe_query_times
		);
	}
	public function reset_cache(){
		$this->cache = [];
	}
	public function disable_cache(){
		$this->do_cache = false;
	}
	public function enable_cache(){
		$this->do_cache = true;
	}
	public function force_log(){
		$this->log = true;
	}

	protected function log( $table, $action, $time ){

		if ( production ) return;

		if ( $table ? substr( $table, 0, 1 ) == "(" : false )
		$table = "*dyna*";

		if ( empty( $this->__exe_time_tables[ $table ] ) )
		$this->__exe_time_tables[ $table ] = array(
			"calls" => 0,
			"time" => 0,
			"actions" => []
		);

		if ( empty( $this->__exe_time_tables[ $table ]["actions"][$action] ) ){
			$this->__exe_time_tables[ $table ]["actions"][$action] = array(
				"calls" => 0,
				"time" => 0
			);
		}

		$this->__exe_time_tables[ $table ]["calls"]++;
		$this->__exe_time_tables[ $table ]["time"] += $time;
		$this->__exe_time_tables[ $table ]["actions"][$action]["calls"]++;
		$this->__exe_time_tables[ $table ]["actions"][$action]["time"] += $time;

	}
	protected function log_query( $query, $exe_time ){

		if ( production ) return;
		if ( !rawmean ) return;

		$this->__exe_query_times[] = round( $exe_time, 4 ) . " --> " . $query;

	}

	public function is_only_full_groupby(){
		$run = $this->query( "SELECT @@sql_mode LIKE '%ONLY_FULL_GROUP_BY%' AS is_enabled" );
		return $run->fetch_assoc()["is_enabled"] ? true : false;
	}

	// logging & caching
	protected function __log( $args, $forceRun=false ){

		if ( defined( "bof_installer" ) ) return;
		if ( $this->log === false && $forceRun === false && 0.3 > $args["exe_time"] ) return;
		if ( !$this->connected ) $this->__connect();

		$table = null;
		$action = null;
		$query = null;
		$params = null;
		$safe = null;
		$exe_time = null;
		$time_start = null;
		$critical = null;
		$error = null;
		extract( $args );

		if ( $table ? substr( $table, 0, 1 ) == "(" : false )
		$table = "*dyna*";

		if ( is_array( $params ) ) $params = json_encode( $params );
		if ( bof()->general->numeric( $time_start ) ) $time_start = bof()->general->seconds_to_timestamp( $time_start );
		$critical = !$critical && $exe_time > 2 ? 7 : $critical;
		$critical = !$critical && $exe_time > 1 ? 6 : $critical;
		$critical = !$critical && $exe_time > 0.6 ? 5 : $critical;
		$critical = !$critical && $exe_time > 0.3 ? 4 : $critical;

		$d = parent::prepare( "INSERT INTO _bof_log_db ( `table`, `action`, `query`, `params`, `safe`, `exe_time`, `time_start`, `critical`, `error` ) VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ? )" );
		$d->bind_param( "sssssssss", $table, $action, $query, $params, $safe, $exe_time, $time_start, $critical, $error );
		$d->execute();
		$d->close();

	}
	public function _query_cache_save( $args, $results ){

		$query = null;
		$params = null;
		$cache_save = false;
		$cache_load = false;
		$cache_load_range = "1 HOUR";
		extract( $args );
		$query_hash = md5( $query );
		$params_hash = md5( json_encode( $params ) );

		if ( $this->do_cache )
		$this->cache[ $query_hash . $params_hash ] = $results;

		if ( !$cache_save )
		return false;

		if ( !$cache_load ){
			if ( ( $wtf = $this->_query_cache_load( $args, true ) ) ){
				return false;
			}
		}

		$results = json_encode( $results );

		$time_start_m = microtime( true );
		$stmt = $this->prepare("INSERT IGNORE INTO _bof_cache_db ( query_hash, params_hash, results, time_expire ) VALUES ( ?, ?, ?, ADDDATE( now(), INTERVAL {$cache_load_range}) ) ");
		$stmt->bind_param( "sss", $query_hash, $params_hash, $results );
		$stmt->execute();
		$stmt->close();

		$time_exe = microtime( true ) - $time_start_m;
		$this->__cache_count = $this->__cache_count + 1;
		$this->__cache_exe_time = $this->__cache_exe_time + $time_exe;

	}
	public function _query_cache_load( $args, $notForReal=false ){

		if ( !$this->do_cache )
		return;

		$query = null;
		$params = null;
		$cache_load_rt = true;
		$cache_load = false;
		$cache_load_range = "1 HOUR";
		extract( $args );
		$query_hash = md5( $query );
		$params_hash = md5( json_encode( $params ) );

		if ( !empty( $this->cache[ $query_hash . $params_hash ] ) && $notForReal !== true && $cache_load_rt ){
			$this->__cache_count++;
			return $this->cache[ $query_hash . $params_hash ];
		}

		if ( !$cache_load && $notForReal !== true )
		return false;

		$time_start_m = microtime( true );
		$query = $this->query("SELECT * FROM _bof_cache_db WHERE query_hash = '{$query_hash}' AND params_hash = '{$params_hash}' AND time_add > SUBDATE( NOW(), INTERVAL {$cache_load_range} ) ORDER BY time_add DESC LIMIT 1 ", null, true);
		$time_exe = microtime( true ) - $time_start_m;

		$this->__cache_count = $this->__cache_count + 1;
		$this->__cache_exe_time = $this->__cache_exe_time + $time_exe;

		if ( $query->num_rows ){

			$query_result = $query->fetch_assoc();

			if ( $notForReal !== true )
			$this->query("UPDATE _bof_cache_db SET used = used + 1 WHERE ID = '{$query_result["ID"]}' ", null, true);

			$__results = $query_result["results"] ? json_decode( $query_result["results"], 1 ) : true;
			$this->cache[ $query_hash . $params_hash ] = $__results;
			return $__results;

		}

		return false;

	}

	// overwrite mysqli
	#[\ReturnTypeWillChange]
	public function query( $query, $resultmode = null, $dontLog = false ){

		if ( !$this->connected ) $this->__connect();

		$time_start = time();
		$time_start_m = microtime( true );
		$run = parent::query( $query, $resultmode === null ? MYSQLI_STORE_RESULT : $resultmode );
		$exe_time = microtime( true ) - $time_start_m;

		if ( $dontLog !== true ){

			$this->__count = $this->__count + 1;
			$this->__exe_time = $this->__exe_time + $exe_time;

			$this->log( "raw", "raw", $exe_time );

			$this->__log( array(
				"query" => $query,
				"safe" => 0,
				"exe_time" => $exe_time,
				"time_start" => $time_start,
			) );

			$this->log_query( $query, $exe_time );

		}

		return $run;

	}
	#[\ReturnTypeWillChange]
	public function prepare( $query ){

		if ( !$this->connected ) $this->__connect();
		try {
			$run = parent::prepare( $query );
		} catch( Exception $err ){
			fall( $query . "--------------" . $err->getMessage() );
		}

		return $run;

	}

	// helpers
	public function _row_count(){
		$get_row_count = $this->query("SELECT ROW_COUNT() as bu_syowl",null,true);
		$row_count = $get_row_count->fetch_assoc();
		return $row_count["bu_syowl"];
	}
	public function _select( $args ){

		$table    = null;
		$limit    = 1;
		$offset   = 0;
		$order    = "DESC";
		$order_by = null;
		$where    = null;
		$joins    = "";
		$columns  = "*";
		$group    = null;
		$cache_save = false;
		$cache_load = false;
		$cache_load_rt = true;
		$cache_load_range = "1 HOUR";
		$single   = false;
		extract( $args );
		$offset   = $offset ? $offset : 0;
		$joins    = $joins ? $joins : "";

		try {
			list( $where_query, $where_vals ) = $this->parse_where( $where );
		} catch( Exception $err ){
			die( "Running `Select` on table `{$table}` failed: " . $err->getMessage() . "<br><br>" . json_encode($where) . "<br><br>" . bof()->general->generateCallTrace() );
		}
		$where_query = $where_query ? "WHERE {$where_query}" : null;

		$select = $this->_query([
			"table"  => $table,
			"action" => "select",
			"query"  => "SELECT {$columns} FROM {$table} {$joins} {$where_query}" . ( $group ? " {$group}" : "" ) . ( $order_by ? " ORDER BY {$order_by} {$order}" : "" ) . ( $limit ? " LIMIT {$offset}, {$limit} " : "" ),
			"params" => $where_vals,
			"cache_save" => $cache_save,
			"cache_load" => $cache_load,
			"cache_load_rt" => $cache_load_rt,
			"cache_load_range" => $cache_load_range
		]);

		if ( $select && $limit == 1 && $single )
		return reset( $select );

		return $select;

	}
	public function _update( $args ){

		$table    = null;
		$limit    = null;
		$offset   = null;
		$order    = null;
		$order_by = null;
		$where    = null;
		$set      = null;
		extract( $args );

		$where_query = "";
		$where_vals  = [];
		if ( !empty( $where ) ){
			list( $where_query, $where_vals ) = $this->parse_where( $where );
			$where_query = $where_query ? "WHERE {$where_query}" : null;
		}

		list( $set_query, $set_vals ) = $this->parse_set_update( $set );
		$set_query = $set_query ? "SET {$set_query}" : null;

		$this->_query([
			"table" => $table,
			"action" => "update",
			"query"  => "UPDATE {$table} {$set_query} {$where_query}" . ( $order_by ? " ORDER BY {$order_by} {$order}" : "" ) . ( $limit ? " LIMIT {$offset}, {$limit} " : "" ),
			"params" => array_merge( $set_vals, $where_vals ),
		]);

		return true;

	}
	public function _delete( $args ){

		$table    = null;
		$limit    = null;
		$offset   = null;
		$order    = null;
		$order_by = null;
		$where    = null;
		extract( $args );

		list( $where_query, $where_vals ) = $this->parse_where( $where );
		$where_query = $where_query ? "WHERE {$where_query}" : null;

		$this->_query([
			"table" => $table,
			"action" => "delete",
			"query"  => "DELETE FROM {$table} {$where_query}" . ( $order_by ? " ORDER BY {$order_by} {$order}" : "" ) . ( $limit ? " LIMIT {$limit} " : "" ),
			"params" => $where_vals,
		]);

		return true;

	}
	public function _insert( $args ){

		$table  = null;
		$set    = null;
		$ignore = false;
		extract( $args );

		list( $key_query, $qm_query, $set_vals ) = $this->parse_set_insert( $set );

		$verb = $ignore ? "INSERT IGNORE" : "INSERT";

		return $this->_query([
			"table" => $table,
			"action" => "insert",
			"query"  => "{$verb} INTO {$table} {$key_query} VALUES {$qm_query}",
			"params" => $set_vals,
		]);

	}

	public function _query( $args ){

		$table = "";
		$action = null;
		$query  = null;
		$params = null;
		$cache  = false;
		$time_start = time();
		$time_start_m = microtime( true );
		$exe_time = 0;
		extract( $args );
		$result = null;

		$cached = $this->_query_cache_load( $args );
		if ( $cached ) return $cached;

		// Start the statement
		$stmt = $this->prepare( $query, true );
		if ( !$stmt ){

			$error = !empty( $this->error ) ? $this->error : "Unkown";

			$this->__log( array(
				"table" => $table,
				"action" => $action,
				"query" => $query,
				"params" => $params,
				"safe" => 1,
				"exe_time" => $exe_time,
				"time_start" => $time_start,
				"critical" => 9,
				"error" => $error
			), true );

			fall( "Prepearing statement for {$action} on table `{$table}` FAILED. You can check `_bof_log_db` for more detail: {$query}" );
			die;

		}

		// Bind paramets ( if any )
		if( $params ){

			$types = "";
			for( $i=0; $i<count($params); $i++ ) {
				$types .= "s";
			}

			$bind_names = [ $types ];

			for( $i=0; $i<count($params); $i++ ) {

				$var_name  = 'var_' . $i;
				$$var_name = $params[$i];
				$bind_names[] = &$$var_name;

			}

			$return = call_user_func_array( array( $stmt, 'bind_param' ), $bind_names );

		}

		// Execute the statement
		try {
			$stmt->execute();
		} catch ( Exception $err ){

			$this->__log( array(
				"table" => $table,
				"action" => $action,
				"query" => $query,
				"params" => $params,
				"safe" => 1,
				"exe_time" => $exe_time,
				"time_start" => $time_start,
				"critical" => 8,
				"error" => $stmt->error
			), true );

			fall( "Prepearing statement for {$action} on table `{$table}` FAILED. You can check `_bof_log_db` for more detail: {$stmt->error} :" . json_encode($params) );
			die;

		}
		$exe_time = microtime( true ) - $time_start_m;

		$this->log_query( $query . ($params?" ps: " . json_encode($params):""), $exe_time );

		$this->__count = $this->__count + 1;
		$this->__exe_time = $this->__exe_time + $exe_time;

		$this->log( $table, $action, $exe_time );

		if ( !empty( $stmt->error ) ){

			$this->__log( array(
				"table" => $table,
				"action" => $action,
				"query" => $query,
				"params" => $params,
				"safe" => 1,
				"exe_time" => $exe_time,
				"time_start" => $time_start,
				"critical" => 8,
				"error" => $stmt->error
			), true );

			fall( "Executing statement for {$action} on table `{$table}` FAILED. You can check `_bof_log_db` for more detail" );
			die;

		}

		// Collect required information
		if ( $action == "select" ){

			$stmt->store_result();
			if ( $stmt->num_rows ){

				$md = $stmt->result_metadata();
				$fields = [];
				while( $field = $md->fetch_field() ) {
					$fields[] = $field->name;
				}

				for ( $i=0; $i<$stmt->num_rows; $i++ ){

					$__r = array();
					$__p = array();

					foreach( $fields as $field ){
						$__p[] = &$__r[ $field ];
					}

					call_user_func_array( array( $stmt, 'bind_result' ), $__p );

					if( $stmt->fetch() )
					$result[] = $__r;

				}


			}
			$stmt->free_result();

		}

		if ( $action == "insert" ){
			$result = $stmt->insert_id;
		}

		$stmt->close();

		$this->__log( array(
			"table" => $table,
			"action" => $action,
			"query" => $query,
			"params" => $params,
			"safe" => 1,
			"exe_time" => $exe_time,
			"time_start" => $time_start,
		) );

		$this->_query_cache_save( $args, $result );

		return $result;

	}

	public function parse_where( $where ){

		if ( empty( $where ) ? true : !is_array( $where ) ) return [ null, null ];

		if ( empty( $where["oper"] ) && empty( $where["cond"] ) ){
			$where = [ "cond" => $where ];
		}

		list( $where_vars, $where_vals ) = array_values( $this->parse_where_level( $where ) );
		$where_vars = substr( $where_vars, 2, -2 );

		return [ $where_vars, $where_vals ];

	}
	protected function parse_where_level( $array ){

		if ( !isset( $array["cond"] ) ){
			throw new Exception( "Bad arguemtns passed to parse_where_level" . json_encode( $array ), E_USER_ERROR );
		}

		$oper = isset( $array["oper"] ) ? $array["oper"] : "AND";
		$cond = $array["cond"];

		$vars = [];
		$vals = [];

		foreach( $cond as $__l ){

			if ( !empty( $__l["oper"] ) && !empty( $__l["cond"] ) ){

				$p = $this->parse_where_level( $__l );
				$vars[] = $p["vars"];
				if ( $p["vals"] ) $vals = array_merge( $vals, $p["vals"] );

			} else {

				$p = $this->parse_cond( $__l );
				$vars[] = $p["var"];
				if ( !$p["raw"] ) $vals[] = $p["val"];

			}

		}

		return [
			"vars" => "( ". implode( " {$oper} ", $vars ) ." )",
			"vals" => $vals,
		];

	}
	protected function parse_cond( $array ){

		if ( !is_array( $array ) )
		throw new Exception( "ParseCond: NotAnArray:" . json_encode( $array ) );

		if ( count( $array ) < 3 )
		throw new Exception( "ParseCond: InvalidArray:" . json_encode( $array ) );

		$column = $array[0];
		$oper   = $array[1];
		$value  = $array[2];
		$raw    = !empty( $array[3] );
		$column_esc = $raw ? $column : "`{$column}`";

		$string = "";

		if ( in_array( $oper, [ "=", ">", "<", "!=", ">=" ,"<=" ] ) ){

			$string = "{$column_esc} {$oper} " . ( !$raw ? "?" : "{$value}" );

		}
		elseif( $oper == "MATCH" ) {

			$string = "MATCH({$column_esc}) AGAINST(" . ( !$raw ? "?" : "'\"{$value}\"'" ) . " IN BOOLEAN MODE)";
			$value  = !$raw ? "\"{$value}\"" : "";

		}
		elseif( $oper == "MATCH%" ) {

			$string = "MATCH({$column_esc}) AGAINST(" . ( !$raw ? "?" : "'%{$value}%'" ) . " IN BOOLEAN MODE)";
			$value  = !$raw ? "%{$value}%" : "";

		}
		elseif( $oper == "LIKE%" ) {

			$string = "{$column_esc} LIKE " . ( !$raw ? "?" : "'%{$value}%'" );
			$value  = !$raw ? "%{$value}%" : "";

		}
		elseif( $oper == "-LIKE%" ) {

			$string = "{$column_esc} LIKE " . ( !$raw ? "?" : "'{$value}%'" );
			$value  = !$raw ? "{$value}%" : "";

		}
		elseif( $oper == "LIKE%lower" ) {

			$string = "lower( {$column_esc} ) LIKE lower( " . ( !$raw ? "?" : "'%{$value}%'" ) . " )";
			$value  = !$raw ? "%{$value}%" : "";

		}
		elseif( $oper == "LIKE" ) {

			$string = "{$column_esc} LIKE " . ( !$raw ? "?" : "'{$value}'" );

		}
		elseif( $oper == "LIKEtrim" ) {

			$string = "TRIM( {$column_esc} ) LIKE " . ( !$raw ? "?" : "'{$value}'" );

		}
		elseif( $oper == "IN" ) {

			$value = is_array( $value ) ? implode( ",", $value ) : $value;
			$string = "{$column_esc} IN ( {$value} )";

		}
		elseif( $oper == "NOT IN" ) {

			$string = "{$column_esc} NOT IN ( {$value} )";

		}
		elseif( $oper === null && $value === null ) {

			$string = "{$column_esc} IS NULL";

		}
		elseif( $oper === "NOT" && $value === null ) {

			$string = "{$column_esc} IS NOT NULL";

		}
		elseif( $oper === "NOT EXISTS" || $oper === "EXISTS" ){

			$string = "{$oper} ( {$value} )";

		}

		return [
			"var" => $string,
			"val" => $raw ? null : $value,
			"raw" => $raw,
		];

	}
	protected function parse_set_update( $array ){

		$vars = $vals = [];

		foreach( $array as $__i ){

			$raw = count( $__i ) == 3 ? true : false;
			$var = $__i[0];
			$val = $__i[1];

			$vars[] = "{$var} = " . ( $raw ? $val : "?" );
			if ( !$raw ) {
				// $vals[] = substr( $val, 0, 1 ) == "{" ? $val : htmlspecialchars_decode( $val, ENT_QUOTES );
				$vals[] = $val;
			}

		}

		return [ implode( ", ", $vars ), $vals ];

	}
	protected function parse_set_insert( $array ){

		$keys = $vals = $qm = [];

		foreach( $array as $__i ){

			$raw = count( $__i ) == 3 ? true : false;
			$var = $__i[0];
			$val = $__i[1];

			$keys[] = "`{$var}`";

			if ( $raw ){
				$qm[]   = $val;
			} else {
				$qm[]   = "?";
				$vals[] = $val ? ( substr( $val, 0, 1 ) == "{" ? $val : htmlspecialchars_decode( $val, ENT_QUOTES ) ) : $val;
			}

			}

			return [
				"( " . implode( ", ", $keys ) . " )",
				"( " . implode( ", ", $qm ) . " )",
				$vals
			];

		}

	}

	?>
