<?php

require_once DCMS_SAB_PATH_INCLUDE.'class-dcms-sab-admin-form.php';
require_once DCMS_SAB_PATH_INCLUDE.'class-dcms-sab-contact-methods.php';
require_once DCMS_SAB_PATH_INCLUDE.'simple_html_dom.php';
require_once DCMS_SAB_PATH_INCLUDE.'lib/FirePHPCore/fb.php';

class Dcms_Simple_Author_Bio{


	private $dcms_admin_form;
	private $dcms_contact_methods;
	private $dcms_options;

	public function __construct(){

		$this->dcms_admin_form  	= new Dcms_Sab_Admin_Form();
		$this->dcms_contact_methods = new Dcms_Contact_Methods();
		$this->dcms_options 		= get_option( 'dcms_sab_bd_options' );

			
		add_action( 'admin_init', 			[$this->dcms_admin_form,'dcms_sab_admin_init'] );
		add_action( 'init',					[$this,'dcms_sab_tranlation'] );
		add_action( 'admin_menu',			[$this,'dcms_sab_add_menu'] );
		add_action( 'wp_enqueue_scripts', 	[$this,'dcms_sab_load_scripts_css'] );

		add_filter( 'the_content',			[$this,'dcms_sab_add_content_bio'] );
		add_filter( 'user_contactmethods', 	[$this->dcms_contact_methods,'dcms_sab_add_social_fields'] );

	}


	/*
	*  Load CSS
	*/
    public function dcms_sab_load_scripts_css() {

    	if ( isset( $this->dcms_options['dcms_sab_chk_load_fontawesome'] ) ){
        	wp_enqueue_style( 'sab_font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css' );
    	}

    	if ( isset( $this->dcms_options['dcms_sab_chk_load_css'] ) ){
        	wp_enqueue_style( 'sab_custom_css', plugins_url( '../css/style.css', __FILE__ )  );
    	}
    
    }


	/*
	*  Create item menu plugin
	*/
	public function dcms_sab_add_menu(){

		add_options_page(__('Author Biography Options','dcms-simple-author-bio'), 
							__('Author Bio','dcms-simple-author-bio'), 
							'manage_options', 
							'dcms_sab_options', 
							[$this, 'dcms_sab_settings_page'] 
							);
	}


	/*
	*  Create plugin controls
	*/
	public function dcms_sab_settings_page(){
		$this->dcms_admin_form->dcms_sab_create_admin_form();	
	}

	
	/*
	*  Add info author to content
	*/
	public function dcms_sab_add_content_bio( $content ){

		if ( is_single() ){

			remove_filter( current_filter(), __FUNCTION__ );

			$hide_author	= isset( $this->dcms_options['dcms_sab_chk_hide_author'] );

			$show_all_posts	= isset( $this->dcms_options['dcms_sab_chk_show_view_all'] );
			$show_social 	= isset( $this->dcms_options['dcms_sab_chk_show_social'] );

			
			if ( get_the_author_meta('description') == '' &&  $hide_author ){
				return $content;
			}

			return $content.$this->get_author_bio( $show_social, $show_all_posts );

		}

	}


	/*
	*  Replace strings in template box-author-bio.txt
	*/
	private function get_author_bio( $show_social, $show_all_posts ){
		
		$template = file_get_html( DCMS_SAB_PATH_TEMPLATE );

		// General validation
		if ( empty($template) ) 	return;
		if ( ! $show_social ) 		$template->find('.author-social')[0]->outertext = '';
		if ( ! $show_all_posts )	$template->find('.author-show-all')[0]->outertext = '';


		// Geto social networks
		$social_networks = $this->dcms_contact_methods->dcms_sab_social;
		
		foreach ( $social_networks as $key => $value){
			// Convert string name to var 
			$$key = get_the_author_meta($key);

			// Validate
			if ( empty($$key) ){
				$template->find('.author-'.$key)[0]->outertext = '';
			}
		}

		// Buscar y reemplazar en la plantilla
		$search		= ['{title}','{avatar}','{description}',
						'{url}','{twitter}','{googleplus}','{facebook}',
						'{github}','{linkedin}','{pinterest}','{youtube}','{instagram}',
						'{show-all-author-url}','{show-all-author-text}'];

		$replace 	= [];
		$replace[] 	= get_the_author();
		$replace[] 	= get_avatar( get_the_author_meta( 'user_email' ) );
		$replace[] 	= get_the_author_meta( 'description');
		$replace[]	= $url;
		$replace[]	= filter_var( $twitter  , FILTER_VALIDATE_URL) ? $twitter : 'https://twitter.com/'.$twitter;
		$replace[]	= $googleplus;
		$replace[]	= $facebook;
		$replace[]	= $github;
		$replace[]	= $linkedin;
		$replace[]	= $pinterest;
		$replace[]	= $youtube;
		$replace[]	= $instagram;
		$replace[]	= esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) );
		$replace[]	= __('View all posts','dcms_simple_author_bio');

		return str_replace( $search, $replace, $template );
	}


	/*
	*  Load traduction
	*/
	public function dcms_sab_tranlation(){

		load_plugin_textdomain('dcms-simple-author-bio', false, DCMS_SAB_PATH_LANGUAGE );

	}



	/*
	*  Activation function
	*/
	public function dcms_sab_activate(){
			
			// delete_option('dcms_sab_bd_options');

			$options 	= get_option('dcms_sab_bd_options');

			if ( is_bool($options) && ! $options ){

			 	$options = [
			 				'dcms_sab_chk_show_social' 		=> 'on',
			 				'dcms_sab_chk_show_view_all'	=> 'on',
			 				'dcms_sab_chk_load_css'			=> 'on',
			 				'dcms_sab_chk_load_fontawesome'	=> 'on',
	☺☻			 			];

				update_option('dcms_sab_bd_options',$options);

			}
	}

	
}




	// add_action( 'update_option_dcms_sab_bd_options', [$this,'dcms_sab_customize_save_after']);

	// /*
	// * After each save, validate and unfilter user_contactmethods
	// */
	// public function dcms_sab_customize_save_after(){

	// 	$this->dcms_options 		= get_option( 'dcms_sab_bd_options' );

	// 	$show_additional_networks 	= isset( $this->dcms_options['dcms_sab_chk_show_aditional_networks'] );
	// 	$has_filter_contactmethods 	= has_filter( 'user_contactmethods', [$this->dcms_contact_methods,'dcms_sab_add_social_fields'] );

	// 	remove_filter( 'user_contactmethods', 	[$this->dcms_contact_methods,'dcms_sab_add_social_fields'] );


	// 	// if ( $show_additional_networks && ! $has_filter_contactmethods ){
	// 	// 	add_filter( 'user_contactmethods', 	[$this->dcms_contact_methods,'dcms_sab_add_social_fields'] );
	// 	// }
	// 	// elseif ( ! $show_additional_networks && $has_filter_contactmethods ){
	// 	// 	remove_filter( 'user_contactmethods', 	[$this->dcms_contact_methods,'dcms_sab_add_social_fields'] );
	// 	// }


	// 	$has_filter_contactmethods 	= has_filter( 'user_contactmethods', [$this->dcms_contact_methods,'dcms_sab_add_social_fields'] );


	// 	$fp = fopen("D:\\Programas\\Ampps\\www\\encurso.app\\wordpress461\\wp-content\\plugins\\dcms-Simple-Author-Bio\\includes\\prueba.txt", 'w');
	// 	fwrite($fp, $show_additional_networks.'-'.$has_filter_contactmethods);
	// 	fclose($fp);


	// 	// $firephp = FirePHP::getInstance(true);
	// 	// $firephp->fb('Hello World'); 


	// 	// $firephp = FirePHP::getInstance(true);
	// 	// $firephp->fb('Hello World'); 

	// 	// return false;
	// 	// error_log('Mensaje de prueba de error');
	// 	// echo " show_additional_networks : ".$show_additional_networks;
	// 	// echo " has_filter_contactmethods: ".$has_filter_contactmethods;

	// 	// if ( ! $show_additional_networks ){
	// 	// 	remove_filter( 'user_contactmethods', [ $this->dcms_contact_methods,'dcms_sab_add_social_fields' ] );
	// 	// }
	// 	// elseif ( !){
	// 	// 		add_filter( 'user_contactmethods', 	[ $this->dcms_contact_methods,'dcms_sab_add_social_fields' ] );
	// 	// }
		
	// }

