<?php

if ( !defined( "bof_root" ) ) die;

class extra_gateways {

	public function setup(){

		bof()->object->core_files->add_key( "class", "pgt_razorpay", bof_extra_gateways_root . "/classes/class_pgt_razorpay.php" );
		bof()->object->core_files->add_key( "class", "pgt_yoomoney", bof_extra_gateways_root . "/classes/class_pgt_yoomoney.php" );
		bof()->object->core_files->add_key( "class", "pgt_flutterwave", bof_extra_gateways_root . "/classes/class_pgt_flutterwave.php" );
		bof()->object->core_files->add_key( "class", "pgt_kkiapay", bof_extra_gateways_root . "/classes/class_pgt_kkiapay.php" );
		bof()->object->core_files->add_key( "class", "pgt_paystack", bof_extra_gateways_root . "/classes/class_pgt_paystack.php" );
		bof()->object->core_files->add_key( "class", "pgt_chapa", bof_extra_gateways_root . "/classes/class_pgt_chapa.php" );
		bof()->object->core_files->add_key( "class", "pgt_mpc", bof_extra_gateways_root . "/classes/class_pgt_mpc.php" );
		bof()->object->core_files->add_key( "class", "pgt_cinetpay", bof_extra_gateways_root . "/classes/class_pgt_cinetpay.php" );
		bof()->object->core_files->add_key( "class", "pgt_cashfree", bof_extra_gateways_root . "/classes/class_pgt_cashfree.php" );
		bof()->object->core_files->add_key( "class", "pgt_pesapal", bof_extra_gateways_root . "/classes/class_pgt_pesapal.php" );
		bof()->object->core_files->add_key( "class", "pgt_fedapay", bof_extra_gateways_root . "/classes/class_pgt_fedapay.php" );
		bof()->object->core_files->add_key( "class", "pgt_payu", bof_extra_gateways_root . "/classes/class_pgt_payu.php" );

		if ( bof()->getName() == "bof_admin" )
		$this->setup_admin();

		bof()->pgt_razorpay->setup();
		bof()->pgt_yoomoney->setup();
		bof()->pgt_flutterwave->setup();
		bof()->pgt_kkiapay->setup();
		bof()->pgt_paystack->setup();
		bof()->pgt_chapa->setup();
		bof()->pgt_mpc->setup();
		bof()->pgt_cinetpay->setup();
		bof()->pgt_cashfree->setup();
		bof()->pgt_pesapal->setup();
		bof()->pgt_fedapay->setup();
		bof()->pgt_payu->setup();

	}
	protected function setup_admin(){

		bof()->pgt_razorpay->setup_admin();
		bof()->pgt_yoomoney->setup_admin();
		bof()->pgt_flutterwave->setup_admin();
		bof()->pgt_kkiapay->setup_admin();
		bof()->pgt_paystack->setup_admin();
		bof()->pgt_chapa->setup_admin();
		bof()->pgt_mpc->setup_admin();
		bof()->pgt_cinetpay->setup_admin();
		bof()->pgt_cashfree->setup_admin();
		bof()->pgt_pesapal->setup_admin();
		bof()->pgt_fedapay->setup_admin();
		bof()->pgt_payu->setup_admin();

		bof()->listen("highlights", "display_pre", function ($method_args, $method_result, $loader) {

			bof()->highlights->add_extension_link(array(
				"title" => "Extra gateways",
				"link" => "gateway_razorpay",
				"icon" => "credit_card"
			));

		});

	}

}

?>
