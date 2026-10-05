<?php

if ( !defined( "bof_root" ) ) die;

class object_storage extends bof_type_object {

  // Variables
  public $types = array(
    "localhost" => "Localhost",
    "ftp" => "FTP",
    "aws_s3" => "Amazon S3",
    "wasabi" => "Wasabi",
    "storj" => "Storj",
    "bunny" => "Bunny",
    "cloudflare" => "Cloudflare",
    "backblaze" => "Backblaze",
    "cdn777" => "CDN777",
    "minio" => "MinIO",
    // "mega" => "Mega",
    //"google_cloud" => "Google Cloud",
  );

  // BusyOwlFramework handshake
  public function bof(){
    return array(
      "label" => "Storage",
      "name" => "storage",
      "icon" => "storage",
      "db_table_name" => "_bof_files_hosts"
    );
  }
  public function columns(){

    $types = $this->types;

    foreach( $types as $type => $type_name )
    $types_for_display[ $type ] = [ $type, $type_name ];

    $types_for_display_no_localhost = $types_for_display;
    unset( $types_for_display_no_localhost["localhost"] );

    return array(
      "name" => array(
        "label" => "Name",
        "tip" => "Enter any unique name<br><br>Example:<br>Busyowl FTP server<br>Envato AWS Bucket",
        "validator" => array(
          "string",
          array(
            "strip_emoji" => false
          )
        ),
        "input" => array(
          "type" => "text"
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "list" => array(
            "type" => "simple",
            "class" => "title",
            "renderer" => function ( $displayItem, $item, $displayData ){
              $displayData["sub_data"] = $item["comment"];
              return $displayData;
            }
          ),
          "object" => array(
            "seo_slug_source" => true,
            "required" => true
          )
        )
      ),
      "comment" => array(
        "label" => "Comment",
        "tip" => "A few words about this storage. Visible to admins only",
        "validator" => array(
          "string",
          array(
            "empty()",
            "allow_eol" => true,
            "strip_emoji" => false
          )
        ),
        "input" => array(
          "type" => "textarea"
        ),
        "bofAdmin" => array(
          "object" => []
        )
      ),
      "type" => array(
        "label" => "Type",
        "tip" => "<a href='https://support.busyowl.co/documentation/storage' target='_blank'>Click here</a> for documentation",
        "validator" => array(
          "in_array",
          array(
            "values" => array_keys( $this->types )
          )
        ),
        "input" => array(
          "type" => "select",
          "options" => $types_for_display_no_localhost
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "list" => array(
            "type" => "tag"
          ),
          "filters" => array(
            "type" => array(
              "title" => "Type",
              "input" => array(
                "type" => "select",
                "options" => array_merge( [ "__all__" => "All" ], $types_for_display ),
                "value" => "__all__"
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => array_keys( $types_for_display )
                )
              )
            )
          ),
          "object" => array(
            "required" => true,
          ),
        )
      ),
      "data" => array(
        "validator" => array(
          "json",
          array(
            "encode" => true,
            "empty()"
          )
        ),
      ),
      "s_files_count" => array(
        "label" => "Files<br>Count",
        "validator" => array(
          "int",
          array(
            "empty()",
            "min" => 0
          ),
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "list" => array(
            "type" => "simple",
            "renderer" => function ( $displayItem, $item, $displayData ){
              $displayData["data"] = $displayData["data"] ? number_format( $displayData["data"] ) : null;
              return $displayData;
            }
          )
        )
      ),
      "s_files_size" => array(
        "label" => "Files<br>Size",
        "validator" => array(
          "int",
          array(
            "empty()",
            "min" => 0
          ),
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "list" => array(
            "type" => "simple",
            "renderer" => function( $displayItem, $item, $displayData ){
              $displayData["data"] = bof()->general->filesize_hr( $displayData["data"] );
              return $displayData;
            },
          )
        )
      ),
      "time_upload" => array(
        "label" => "Last<br>Upload",
        "validator" => array(
          "timestamp",
          array(
            "empty()"
          ),
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "list" => array(
            "type" => "time",
          )
        )
      ),
    );
  }
  public function selectors(){
    return array(
      "type"        => [ "type", "=" ],
      "query"       => [ "name", "LIKE%lower" ],
      "related_server" => [ "ID", ">", false, "1" ],
    );
  }
  public function bof_columns(){
    return array(
      "ID",
      "time_add"
    );
  }
  public function bof_admin(){
    return array(
      "config" => array(
        "search" => true,
        "create" => true,
        "edit" => true,
        "delete" => true,
        "pagination" => true,
        "edit_page_url" => "storage",
        "list_page_url" => "storages",
        "multi" => array(
          "select" => true,
          "delete" => true,
          "edit"   => false
        )
      ),
      "buttons" => array(),
      "buttons_renderer" => function( $item, $buttons ){

        if ( $item["ID"] == 1 )
        $buttons = [];

        else
        $buttons["test"] = array(
          "ID" => "test_storage",
          "label" => "Test",
          "attr" => "data-id='{$item["ID"]}'"
        );

        if ( $item["s_files_count"] )
        unset( $buttons["delete"] );

        return $buttons;

      },
      "filters" => array(),
      "list" => array(),
      "list_config" => array(),
      "object" => array(

        "name" => null,
        "comment" => null,
        "type" => null,

        "ftp_address" => array(
          "label" => "FTP Address",
          "tip" => "Example: ftp.domain.com OR 95.217.206.55",
          "multi" => false,
          "display_on_cond" => "and",
          "display_on" => array(
            "type" => [ "equal", "ftp" ]
          ),
          "input" => array(
            "name" => "ftp_address",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
              "strict" => true,
            )
          ),
        ),
        "ftp_path" => array(
          "label" => "FTP Path",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "ftp" ]
          ),
          "input" => array(
            "name" => "ftp_path",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
        "ftp_port" => array(
          "label" => "FTP Port",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "ftp" ]
          ),
          "tip" => "Default FTP port is 21",
          "input" => array(
            "name" => "ftp_port",
            "type" => "digit",
            "value" => "21",
            "placeholder" => "21"
          ),
          "validator" => array(
            "int",
            array(
              "empty()",
            )
          )
        ),
        "ftp_type" => array(
          "label" => "FTP Type",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "ftp" ]
          ),
          "tip" => "FTP connection type",
          "input" => array(
            "name" => "ftp_type",
            "type" => "select_i",
            "options" => array(
              [ "ftp", "FTP" ],
              [ "sslftp", "FTP over SSL" ],
              [ "sshftp", "FTP over SSH" ]
            )
          ),
          "validator" => array(
            "in_array",
            array(
              "empty()",
              "values" => [ "ftp", "sslftp", "sshftp" ]
            )
          )
        ),
        "ftp_username" => array(
          "label" => "FTP Username",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "ftp" ]
          ),
          "input" => array(
            "name" => "ftp_username",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
              "strict" => true,
            )
          )
        ),
        "ftp_password" => array(
          "label" => "FTP Password",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "ftp" ]
          ),
          "input" => array(
            "name" => "ftp_password",
            "type" => "text",
          ),
          "validator" => array(
            "password",
            array(
              "empty()",
            )
          )
        ),
        "ftp_web_address" => array(
          "label" => "FTP Web-Address",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "ftp" ]
          ),
          "tip" => "Where is FTP pointing to?<br>Example: https://ftp-server.domain.com",
          "input" => array(
            "name" => "ftp_web_address",
            "type" => "text",
            "placeholder" => "https://"
          ),
          "validator" => array(
            "url",
            array(
              "empty()",
              "default_scheme" => false
            )
          )
        ),

        "aws_bucket" => array(
          "label" => "Amazon Bucket Name",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "aws_s3" ]
          ),
          "input" => array(
            "name" => "aws_bucket",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
        "aws_region" => array(
          "label" => "Amazon Bucket Region",
          "tip" => "Enter bucket region code here. Example: eu-central-1",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "aws_s3" ]
          ),
          "input" => array(
            "name" => "aws_region",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
        "aws_key" => array(
          "label" => "Amazon S3 Key",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "aws_s3" ]
          ),
          "input" => array(
            "name" => "aws_key",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
        "aws_secret" => array(
          "label" => "Amazon S3 Secret",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "aws_s3" ]
          ),
          "input" => array(
            "name" => "aws_secret",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
        "aws_endpoint" => array(
          "label" => "Custom Endpoint",
          "tip" => "Enter endpoint address if you are using a S3 compatible storage service like Wasabi",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "aws_s3" ]
          ),
          "input" => array(
            "name" => "aws_endpoint",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
        "aws_address" => array(
          "label" => "Custom Address",
          "tip" => "Enter custom web-address in case the service is only offered on subdomain ( Cloudflare for example, not official AWS or Wasabi )",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "aws_s3" ]
          ),
          "input" => array(
            "name" => "aws_address",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),

        "wasabi_bucket" => array(
          "label" => "Wasabi Bucket Name",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "wasabi" ]
          ),
          "input" => array(
            "name" => "wasabi_bucket",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
        "wasabi_region" => array(
          "label" => "Wasabi Bucket Region",
          "tip" => "Select bucket region code here. Example: us-east-2",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "wasabi" ]
          ),
          "input" => array(
            "name" => "wasabi_region",
            "type" => "select",
            "options" => array(
              ['us-east-1' ,'us-east-1'],
              ['us-east-2' ,'us-east-2'],
              ['us-central-1' ,'us-central-1'],
              ['us-west-1' ,'us-west-1'],
              ['ca-central-1' ,'ca-central-1'],
              ['eu-central-1' ,'eu-central-1'],
              ['nl-1' ,'nl-1'],
              ['eu-central-2' ,'eu-central-2'],
              ['de-1' ,'de-1'],
              ['eu-west-1' ,'eu-west-1'],
              ['uk-1' ,'uk-1'],
              ['eu-west-2' ,'eu-west-2'],
              ['fr-1' ,'fr-1'],
              ['ap-northeast-1' ,'ap-northeast-1'],
              ['ap-northeast-2' ,'ap-northeast-2'],
              ['ap-southeast-1' ,'ap-southeast-1'],
              ['ap-southeast-2' ,'ap-southeast-2'],
            )
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
        "wasabi_key" => array(
          "label" => "Wasabi Access Key",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "wasabi" ]
          ),
          "input" => array(
            "name" => "wasabi_key",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
        "wasabi_secret" => array(
          "label" => "Wasabi Secret Key",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "wasabi" ]
          ),
          "input" => array(
            "name" => "wasabi_secret",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),

        "minio_bucket" => array(
          "label" => "MinIO Bucket Name",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "minio" ]
          ),
          "input" => array(
            "name" => "minio_bucket",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
        "minio_key" => array(
          "label" => "MinIO Access Key ( or admin's username )",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "minio" ]
          ),
          "input" => array(
            "name" => "minio_key",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
        "minio_secret" => array(
          "label" => "MinIO Secret Key ( or admin's password )",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "minio" ]
          ),
          "input" => array(
            "name" => "minio_secret",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
        "minio_endpoint" => array(
          "label" => "MinIO URL",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "minio" ]
          ),
          "input" => array(
            "name" => "minio_endpoint",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),

        "storj_bucket" => array(
          "label" => "Storj Bucket Name",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "storj" ]
          ),
          "input" => array(
            "name" => "storj_bucket",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
        "storj_bucket_url" => array(
          "label" => "Storj Bucket URL",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "storj" ]
          ),
          "input" => array(
            "name" => "storj_bucket_url",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
        "storj_key" => array(
          "label" => "Storj Access Key",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "storj" ]
          ),
          "input" => array(
            "name" => "storj_key",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
        "storj_secret" => array(
          "label" => "Storj Secret Key",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "storj" ]
          ),
          "input" => array(
            "name" => "storj_secret",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
        "storj_endpoint" => array(
          "label" => "Storj Endpoint",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "storj" ]
          ),
          "input" => array(
            "name" => "storj_endpoint",
            "type" => "text",
            "value" => "https://gateway.storjshare.io"
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),

        "cdn777_bucket" => array(
          "label" => "CDN777 Bucket Name ( Label )",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "cdn777" ]
          ),
          "input" => array(
            "name" => "cdn777_bucket",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
        "cdn777_bucket_url" => array(
          "label" => "CDN777 Bucket URL",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "cdn777" ]
          ),
          "input" => array(
            "name" => "cdn777_bucket_url",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
        "cdn777_key" => array(
          "label" => "CDN777 Access Key",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "cdn777" ]
          ),
          "input" => array(
            "name" => "cdn777_key",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
        "cdn777_secret" => array(
          "label" => "CDN777 Secret Key",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "cdn777" ]
          ),
          "input" => array(
            "name" => "cdn777_secret",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),

        "mega_bucket" => array(
          "label" => "Mega Bucket Name",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "mega" ]
          ),
          "input" => array(
            "name" => "mega_bucket",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
        "mega_region" => array(
          "label" => "Mega Bucket Region",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "mega" ]
          ),
          "input" => array(
            "name" => "mega_region",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
        "mega_key" => array(
          "label" => "Mega Key",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "mega" ]
          ),
          "input" => array(
            "name" => "mega_key",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
        "mega_secret" => array(
          "label" => "Mega Secret Key",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "mega" ]
          ),
          "input" => array(
            "name" => "mega_secret",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),

        "cloudflare_bucket" => array(
          "label" => "CloudFlare Bucket Name",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "cloudflare" ]
          ),
          "input" => array(
            "name" => "cloudflare_bucket",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
        "cloudflare_jurisdiction" => array(
          "label" => "CloudFlare Bucket Jurisdiction",
          "tip" => "Can be empty. Example: eu, fedramp",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "cloudflare" ]
          ),
          "input" => array(
            "name" => "cloudflare_jurisdiction",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
        "cloudflare_key" => array(
          "label" => "CloudFlare Access Key ID",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "cloudflare" ]
          ),
          "input" => array(
            "name" => "cloudflare_key",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
        "cloudflare_secret" => array(
          "label" => "CloudFlare Secret Access Key",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "cloudflare" ]
          ),
          "input" => array(
            "name" => "cloudflare_secret",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
        "cloudflare_user_id" => array(
          "label" => "CloudFlare Account-ID",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "cloudflare" ]
          ),
          "input" => array(
            "name" => "cloudflare_user_id",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
        "cloudflare_address" => array(
          "label" => "CloudFlare Custom Address",
          "tip" => "If you are using a custom address to access your content on CloudFlare ( for example cdn.example.com ), enter it here. Leave blank to use generic addresses",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "cloudflare" ]
          ),
          "input" => array(
            "name" => "cloudflare_address",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),

        "backblaze_bucket" => array(
          "label" => "Backblaze Bucket Name",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "backblaze" ]
          ),
          "input" => array(
            "name" => "backblaze_bucket",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
        "backblaze_key" => array(
          "label" => "Backblaze KeyID",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "backblaze" ]
          ),
          "input" => array(
            "name" => "backblaze_key",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
        "backblaze_key2" => array(
          "label" => "Backblaze KeyName",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "backblaze" ]
          ),
          "input" => array(
            "name" => "backblaze_key2",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
        "backblaze_secret" => array(
          "label" => "Backblaze ApplicationKey",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "backblaze" ]
          ),
          "input" => array(
            "name" => "backblaze_secret",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
        "backblaze_endpoint" => array(
          "label" => "Backblaze Endpoint",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "backblaze" ]
          ),
          "input" => array(
            "name" => "backblaze_endpoint",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),

        "bunny_address" => array(
          "label" => "Bunny Pull-Zone Hostname",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "bunny" ]
          ),
          "input" => array(
            "name" => "bunny_address",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
        "bunny_ftp_host" => array(
          "label" => "Bunny FTP Host",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "bunny" ]
          ),
          "input" => array(
            "name" => "bunny_ftp_host",
            "type" => "text",
            "value" => "storage.bunnycdn.com"
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
        "bunny_ftp_port" => array(
          "label" => "Bunny FTP Port",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "bunny" ]
          ),
          "input" => array(
            "name" => "bunny_ftp_port",
            "type" => "digit",
            "value" => "21"
          ),
          "validator" => array(
            "int",
            array(
              "empty()",
            )
          )
        ),
        "bunny_ftp_user" => array(
          "label" => "Bunny FTP Username",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "bunny" ]
          ),
          "input" => array(
            "name" => "bunny_ftp_user",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
        "bunny_ftp_pass" => array(
          "label" => "Bunny FTP Password",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "bunny" ]
          ),
          "input" => array(
            "name" => "bunny_ftp_pass",
            "type" => "text",
          ),
          "validator" => array(
            "password",
            array(
              "empty()",
            )
          )
        ),

      ),
      "object_item_renderer" => function( $item_name, &$item_data, $request ){

        if ( $request["type"] != "single" ) return;

        $content = $request["content"][ $request["IDS"][0] ];

        foreach( [ "ftp", "aws", "wasabi", "bunny", "storj", "backblaze", "cloudflare", "cdn777", "mega", "minio" ] as $_px ){
          if ( substr( $item_name, 0, strlen( $_px ) + 1 ) == "{$_px}_" ){
            $_px_name = substr( $item_name, strlen( $_px ) + 1 );
            if ( isset( $content["data_decoded"][ $_px_name ] ) ? $content["data_decoded"][ $_px_name ] !== false : false )
            $item_data["input"]["value"] = $content["data_decoded"][ $_px_name ];
          }
        }

      },
      "object_be_renderer" => function( $_inputs, $request ){

        if ( $request["type"] == "multi" ) return;

        $data = [];

        if ( $_inputs["data"]["type"] == "ftp" ){

          foreach( [ "ftp_address", "ftp_username", "ftp_password", "ftp_web_address", "ftp_port", "ftp_type" ] as $required_key ){
            if ( $_inputs["data"][ $required_key ] === false || $_inputs["data"][ $required_key ] === null )
            $_inputs["report"]["fail"][ $required_key ] = false;
            else
            $data[ substr( $required_key, 4 ) ] = $_inputs["data"][ $required_key ];
          }

          foreach( [ "ftp_path" ] as $optional_key ){
            if ( $_inputs["data"][ $required_key ] )
            $data[ substr( $optional_key, 4 ) ] = $_inputs["data"][ $optional_key ];
          }

        }
        if ( $_inputs["data"]["type"] == "aws_s3" ){

          foreach( [ "aws_bucket", "aws_region", "aws_key", "aws_secret" ] as $required_key ){
            if ( $_inputs["data"][ $required_key ] === false || $_inputs["data"][ $required_key ] === null )
            $_inputs["report"]["fail"][ $required_key ] = false;
            else
            $data[ substr( $required_key, 4 ) ] = $_inputs["data"][ $required_key ];
          }

          foreach( [ "aws_endpoint", "aws_address" ] as $optional_key ){
            if ( $_inputs["data"][ $required_key ] )
            $data[ substr( $optional_key, 4 ) ] = $_inputs["data"][ $optional_key ];
          }

        }
        if ( $_inputs["data"]["type"] == "wasabi" ){

          foreach( [ "wasabi_bucket", "wasabi_region", "wasabi_key", "wasabi_secret" ] as $required_key ){
            if ( $_inputs["data"][ $required_key ] === false || $_inputs["data"][ $required_key ] === null )
            $_inputs["report"]["fail"][ $required_key ] = false;
            else
            $data[ substr( $required_key, 7 ) ] = $_inputs["data"][ $required_key ];
          }

        }
        if ( $_inputs["data"]["type"] == "minio" ){

          foreach( [ "minio_bucket", "minio_endpoint", "minio_key", "minio_secret" ] as $required_key ){
            if ( $_inputs["data"][ $required_key ] === false || $_inputs["data"][ $required_key ] === null )
            $_inputs["report"]["fail"][ $required_key ] = false;
            else
            $data[ substr( $required_key, 6 ) ] = $_inputs["data"][ $required_key ];
          }

        }
        if ( $_inputs["data"]["type"] == "bunny" ){

          foreach( [ "bunny_address", "bunny_ftp_host", "bunny_ftp_port", "bunny_ftp_user", "bunny_ftp_pass" ] as $required_key ){
            if ( $_inputs["data"][ $required_key ] === false || $_inputs["data"][ $required_key ] === null )
            $_inputs["report"]["fail"][ $required_key ] = false;
            else
            $data[ substr( $required_key, 6 ) ] = $_inputs["data"][ $required_key ];
          }

        }
        if ( $_inputs["data"]["type"] == "storj" ){

          foreach( [ "storj_bucket", "storj_bucket_url", "storj_key", "storj_secret", "storj_endpoint" ] as $required_key ){
            if ( $_inputs["data"][ $required_key ] === false || $_inputs["data"][ $required_key ] === null )
            $_inputs["report"]["fail"][ $required_key ] = false;
            else
            $data[ substr( $required_key, 6 ) ] = $_inputs["data"][ $required_key ];
          }

        }
        if ( $_inputs["data"]["type"] == "cdn777" ){

          foreach( [ "cdn777_bucket", "cdn777_bucket_url", "cdn777_key", "cdn777_secret" ] as $required_key ){
            if ( $_inputs["data"][ $required_key ] === false || $_inputs["data"][ $required_key ] === null )
            $_inputs["report"]["fail"][ $required_key ] = false;
            else
            $data[ substr( $required_key, 7 ) ] = $_inputs["data"][ $required_key ];
          }

        }
        if ( $_inputs["data"]["type"] == "mega" ){

          foreach( [ "mega_bucket", "mega_region", "mega_key", "mega_secret" ] as $required_key ){
            if ( $_inputs["data"][ $required_key ] === false || $_inputs["data"][ $required_key ] === null )
            $_inputs["report"]["fail"][ $required_key ] = false;
            else
            $data[ substr( $required_key, 5 ) ] = $_inputs["data"][ $required_key ];
          }

        }
        if ( $_inputs["data"]["type"] == "backblaze" ){

          foreach( [ "backblaze_bucket", "backblaze_key", "backblaze_key2", "backblaze_secret", "backblaze_endpoint" ] as $required_key ){
            if ( $_inputs["data"][ $required_key ] === false || $_inputs["data"][ $required_key ] === null )
            $_inputs["report"]["fail"][ $required_key ] = false;
            else
            $data[ substr( $required_key, 10 ) ] = $_inputs["data"][ $required_key ];
          }

        }
        if ( $_inputs["data"]["type"] == "cloudflare" ){

          foreach( [ "cloudflare_key", "cloudflare_secret", "cloudflare_user_id", "cloudflare_bucket" ] as $required_key ){
            if ( $_inputs["data"][ $required_key ] === false || $_inputs["data"][ $required_key ] === null )
            $_inputs["report"]["fail"][ $required_key ] = false;
            else
            $data[ substr( $required_key, 11 ) ] = $_inputs["data"][ $required_key ];
          }

          foreach( [ "cloudflare_jurisdiction", "cloudflare_address" ] as $optional_key ){
            if ( $_inputs["data"][ $required_key ] )
            $data[ substr( $optional_key, 11 ) ] = $_inputs["data"][ $optional_key ];
          }

          if ( empty( $_inputs["report"]["fail"] ) )
          $data["endpoint"] = !empty( $data["jurisdiction"] ) ? "{$data["user_id"]}.{$data["jurisdiction"]}.r2.cloudflarestorage.com" : "{$data["user_id"]}.r2.cloudflarestorage.com";

        }

        $_inputs["data"]["data"] = $_inputs["set"]["data"] = $_inputs["update"]["data"] = $data;
        return $_inputs;

      },
    );
  }

  // BusyOwlFramework helpers
  public function select( $whereArgs=[], $selectArgs=[] ){

    $listing = false;
    $editing = false;
    $deleting = false;
    $verified = false;
    extract( $selectArgs );

    if ( $editing ){
      $whereArgs[] = [ "ID", "!=", "1" ];
    }

    if ( $deleting ){
      $whereArgs[] = [ "ID", "!=", "1" ];
    }

    return bof()->object->_select( $this, $whereArgs, $selectArgs );

  }
  public function clean( $item, $args ){

    $search = false;
    extract( $args );

    if ( $search ){
      $item = array(
        "ID" => $item["ID"],
        "title" => $item["name"],
        "image" => false
      );
    }

    return $item;

  }

  public function admin_setting( $groups ){

    $hosts = bof()->object->db_setting->get( "fh_setting" );
    $objects = bof()->bofAdmin->_get_objects();

    foreach( $objects as $object_name => $object_args ){

      $object_parsed = bof()->object->parse_caller( $object_name );
      $object_rules = [];

      $columns = $object_parsed->parsed->columns;
      if ( $object_parsed->proxied->method_exists( "bof_admin" ) ? !empty( $object_parsed->proxied->bof_admin()["object"] ) : false )
      $columns = array_merge( $object_parsed->proxied->bof_admin()["object"], $columns );

      foreach( $columns as $object_column_name => $object_column_args ){
        if ( !empty( $object_column_args["bofInput"] ) ? $object_column_args["bofInput"][0] == "file" : false ){

          $object_rules[ "fh_" . $object_column_args["bofInput"][1]["object_type"] . "_" . $object_column_args["bofInput"][1]["type"] ] = array(
            "title" => ( !empty( $object_column_args["label"] ) ? $object_column_args["label"] : $object_column_name ) . ( preg_match( "/source/", $object_column_name ) && !empty( $object_column_args["bofInput"]["1"]["type"] ) ? " - " . $object_column_args["bofInput"]["1"]["type"]  : "" ),
          );

        }
      }

      if ( !empty( $object_rules ) ){

        bof()->object->storage->add_rules( array(
    			"title" => $object_parsed->direct->bof()["label"],
          "icon" => $object_parsed->direct->bof()["icon"],
    			"rules"=> $object_rules
    		) );

      }

    }

    if ( $this->rules_groups ){
      foreach( $this->rules_groups as $_rule_group ){

        $_rule_group_inputs = [];
        foreach( $_rule_group["rules"] as $_rule_k => $_rule ){
          $_rule_group_inputs[ $_rule_k ] = array(
            "title" => $_rule["title"],
            "tip" => !empty( $_rule["tip"] ) ? $_rule["tip"] : null,
            "input" => array(
              "name"  => $_rule_k,
              "value" => !empty( $hosts[ substr( $_rule_k, 3 ) ] ) ? $hosts[ substr( $_rule_k, 3 ) ] : null
            ),
            "bofInput" => array(
              "object",
              array(
                "type" => "storage",
                "multi" => false
              )
            ),
            "validator" => array(
              "in_array",
              array(
                "empty()"
              )
            )
          );
        }

        $groups[] = array(
          "title" => $_rule_group["title"],
          "icon" => $_rule_group["icon"],
          "inputs" => $_rule_group_inputs
        );

      }
    }

    return $groups;

  }
  public function admin_setting_be( $groups, $inputs ){

    if ( empty( $inputs["data"]["fh_default"] ) ){
      $inputs["report"]["fail"]["fh_default"] = "Can't be empty";
    }

    $fhs = [];
    foreach( $inputs["data"] as $_k => $_v ){
      if ( !empty( $_v ) )
      $fhs[ substr( $_k, strlen( "fh_" ) ) ] = $_v;
    }

    $inputs["set"][ "fh_setting" ] = json_encode( $fhs );

    return $inputs;

  }

  public $rules_groups = array(

    array(
      "title" => "General",
      "icon" => "tune",
      "rules"=> array(
        "fh_default" => array(
          "title" => "Default storage",
          "tip" => "Fallback storage for all files",
        ),

      )
    )

  );

  public function add_rules( $args ){

    $title = null;
    $icon = null;
    $rules = null;
    extract( $args );

    $this->rules_groups[] = array(
      "title" => $title,
      "icon" => $icon,
      "rules" => $rules
    );

  }
  public function count_files( $ID ){

    $data = bof()->object->file->select(array(
      "host_id" => $ID
    ),array(
      "columns" => "COUNT(*) as _c,SUM(size) as _s",
      "clean" => false,
      "cache_load_rt" => false,
    ));

    if ( !$data )
    $data = array( "_c" => 0, "_s" => 0 );

    $this->_bof_this->update(array(
      "ID" => $ID
    ),array(
      "s_files_count" => $data["_c"],
      "s_files_size" => $data["_s"]
    ));

    return true;

  }

}

?>
