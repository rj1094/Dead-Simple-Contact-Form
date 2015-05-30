<?php
/**
 * Plugin Name: Dead Simple Contact Form
 * Plugin URI:  TO BE ADDED WHEN PURCHASED
 * Description: A ridiculously simple way to implement a basic comment form.
 * Version: 0.5
 * Author: RJ Hallsted
 * Author URI: TO BE ADDED WHEN PURCHASED
 * License: GPL2
 */

//security check to block direct access.
defined('ABSPATH') or die("No script kiddies please!");

function dead_simple_enqueue_styles() {
	wp_enqueue_style( 'dead-simple-contact-form-css', plugin_dir_url( __FILE__ ) . '/dead-simple-contact-form.css' );
}
add_action( 'wp_enqueue_scripts', 'dead_simple_enqueue_styles' );

add_shortcode( 'dead-simple-contact-form', 'dead_simple_contact_form' );
function dead_simple_contact_form() {
	/*
	 * This is the function to call in the theme to
	 * in order to display and handle the footer contact 
	 * form.
	 */
	global $_POST;

	/*Function call order
	 * Dead_Simple_Contact_Form->__constructor();
	 * * Dead_Simple_Contact_Form->set_variables();
	 * Dead_Simple_Contact_Form->handle_contact_form();
	 * * either -
	 * * Dead_Simple_Contact_Form->display_form();
	 * * * Dead_Simple_Contact_Form->humanity_check();
	 * * or -
	 * * Dead_Simple_Contact_Form->set_variables()
	 * * Dead_Simple_Contact_Form->determine_response_message();
	 * * * Dead_Simple_Contact_Form->send_message();
	 * * Dead_Simple_Contact_Form->generate_response();
	 * * * and possibly - 
	 * * * Dead_Simple_Contact_Form->display_form();
	*/
	$form = new Dead_Simple_Contact_Form();
	$form_output = $form->handle_contact_form( $_POST );

	return $form_output;
}

class Dead_Simple_Contact_Form {
	//Initialize all of the private variables.
	private $name 				= null;
	private $email 				= null;
	private $message 			= null;
	private $humanity_submitted	= null;
	private $humanity_addend	= null;
	private $humanity_sum 		= null;
	private $submitted			= null;
	private $form_count 		= null;

	function __constructor() {
		//Define the variables using the $_POST variables
		$this->set_variables();

		/*
		 * Define $this->form_count. This is a global because on some pages, their may be more than one form present,
		 * and we need seperate ID's and action urls.
		 * This only needs to be defined once per form, and is global, so it is included here, in the constructor
		 * instead of set_variables().
		*/
		global $contact_forms_on_page;
		$contact_forms_on_page = ( isset( $contact_forms_on_page ) ) ? $contact_forms_on_page + 1 : 1;
		$this->form_count 	= $contact_forms_on_page;
	}
	function display_form() {
		//used to print the form HTML
		global $post;

		$output = '<form action="' . get_permalink( $post->ID ) . '#dead-simple-contact-form-' . $this->form_count . '" id="dead-simple-contact-form-' . $this->form_count . '" class="dead-simple-contact-form" method="post">';
		
			$output .= '<label for="dead_simple_message_name">Name</label>';
			$output .= '<input type="text" placeholder="John Doe" name="dead_simple_message_name" value="' . esc_attr( $this->name ) . '" required>';

			$output .= '<label for="dead_simple_message_email">Email</label>';
			$output .= '<input type="email" placeholder="example@mail.com" name="dead_simple_message_email" value="' .  esc_attr( $this->email ) . '" required>';

			$output .= '<label for="dead_simple_message_text">Message</label>';
			$output .= '<textarea placeholder="How can we help you?" name="dead_simple_message_text" id="contact-message" cols="15" rows="4" required>' . esc_textarea ( $this->message ) . '</textarea>';

			$output .= $this->humanity_check();

			$output .= '<input type="hidden" name="dead_simple_submitted" value="1">';
			$output .= '<input type="submit" value="Send">';
		$output .= '</form>';

		return $output;
	}
	function humanity_check() {
		//print humanity check html and reset session variables for security purposes.
		$_SESSION[ 'dead_simple_humanity_sum' ] = mt_rand( 0, 20 );
		$_SESSION[ 'dead_simple_humanity_addend' ] = mt_rand( 0, $_SESSION[ 'dead_simple_humanity_sum' ] );
		$this->humanity_sum = $_SESSION[ 'dead_simple_humanity_sum' ];
		$this->humanity_addend = $_SESSION[ 'dead_simple_humanity_addend' ];

		$humanity_output = '<div class="humanity-check">';
			$humanity_output .= '<label for="dead_simple_message_human">Are you human?</label>';
			$humanity_output .= '<input type="num" name="dead_simple_message_human" autocomplete="off" required><div class="math-text">  + ' . $this->humanity_addend . ' = ' . $this->humanity_sum . '</div>';
		$humanity_output .= '</div>';

		return $humanity_output;
	}
	function set_variables( $form_values ) {
		/*
		* Sets the private properties, using the array
		* passed by the user.
		* If an array is not passed, it sets the properties
		* using $_POST data.
		*/
		if( !isset( $form_values ) ) {
			global $_POST;
			$form_values = $_POST;
		}
		$this->name 				= $form_values[ 'dead_simple_message_name' ];
		$this->email 				= $form_values[ 'dead_simple_message_email' ];
		$this->message 				= $form_values[ 'dead_simple_message_text' ];
		$this->humanity_submitted	= $form_values[ 'dead_simple_message_human' ];
		$this->submitted 			= $form_values[ 'dead_simple_submitted' ];

		/*
		 * Set humanity check variables using session variables if the exist. If not,
		 * set the session variables to rand numbers, and the the humanity properties as well.
		 */
		if( !isset( $_SESSION[ 'dead_simple_humanity_sum' ] ) || !isset( $_SESSION[ 'dead_simple_humanity_addend' ] ) ) {
			$_SESSION[ 'dead_simple_humanity_sum' ] = mt_rand( 0, 20 );
			$_SESSION[ 'dead_simple_humanity_addend' ] = mt_rand( 0, $_SESSION[ 'dead_simple_humanity_sum' ] );
		}
		$this->humanity_sum = $_SESSION[ 'dead_simple_humanity_sum' ];
		$this->humanity_addend = $_SESSION[ 'dead_simple_humanity_addend' ];

		return $this;
	}
	function send_message() {
		//use the wp_mail function to send the form.
		//php mailer variables
		$to = get_option( 'admin_email' );
		$subject = 'Someone sent a message from the ' . get_bloginfo( 'name' ) . ' website.';
		$headers = 'From: '. $this->email . '\n' . 'Reply-To: ' . $this->email . '\n';

		return wp_mail( $to, $subject, strip_tags( $this->message ), $headers );
	}
	function determine_response_message() {
		//This function handles our server-side input validation.
		$difference = $this->humanity_sum - $this->humanity_addend;

		if( $this->humanity_submitted != 0 && $this->humanity_submitted != null ) {
			if( $this->humanity_submitted != $difference )
				$response_message = 'not_human';
			else {
				//validate email
				if( !filter_var( $this->email, FILTER_VALIDATE_EMAIL ) )
					$response_message = 'email_invalid';
				else { //email is valid
					//validate presence of name and message
					if( empty( $this->name ) || empty( $this->message ) ) {
						$response_message = 'missing_content';
					} else { //ready to go!
						if( $this->send_message() )
							$response_message = 'message_sent';
						else
							$response_message = 'message_unsent';
					}
				}
			}
		} else if( $this->submitted )
			$response_message = 'missing_content';

		return $response_message;
	}
	function generate_response( $message ) {
		//Determine the necessary response, and output it.

		//response text
		$responses[ 'not_human' ] 		= "Excuse me, but apparently you're not a human. Would you please correct that?";
		$responses[ 'missing_content' ]	= "Hey, you're missing some stuff! Fill it in, would you please?";
		$responses[ 'email_invalid' ] 	= "You're email address isn't valid. Please fix that, would you?";
		$responses[ 'message_unsent' ]	= "Woops, the message wasn't sent! Please try again.";
		$responses[ 'message_sent' ] 	= "Thanks for contacting us! We'll be in touch soon!";

		$response_type = ( $message == 'message_sent' ) ? 'success' : 'error';

		$response_output = "<div class='{$response_type}'>{$responses[ $message ]}</div>";
		if( $response_type == 'error' )
			$response_output .= $this->display_form();

		return $response_output;
	}
	function handle_contact_form( $form_values ) {
		/*
		* Check if form has been submitted. If so, set values, and generate response. If not, print form.
		*/
		if( isset( $form_values[ 'dead_simple_submitted' ] ) ) {
			$this->set_variables( $form_values );
			$handled_output = $this->generate_response( $this->determine_response_message() );
		} else {
			$handled_output = $this->display_form();
		}

		return $handled_output;
	}
}


//now the shortcode will work in text widgets.
add_filter('widget_text', 'do_shortcode');

?>