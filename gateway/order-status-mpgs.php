<?php
/**
 * Mastercard Payment Gateway Services: Hosted Session.
 *
 * @package Mpgs_Hosted_Session
 */

defined('ABSPATH') || exit;

return array(
	array(
		'status' => 'wc-mpgs-pending',
		'label'  => 'MPGS Online Pending',
	),
	array(
		'status' => 'wc-mpgs-failed',
		'label'  => 'MPGS Online Failed',
	),
	array(
		'status' => 'wc-mpgs-complete',
		'label'  => 'MPGS Online Complete',
	),
	array(
		'status' => 'wc-mpgs-authorised',
		'label'  => 'MPGS Online Authorised',
	),
	array(
		'status' => 'wc-mpgs-captured',
		'label'  => 'MPGS Online Captured',
	),    
	array(
		'status' => 'wc-mpgs-part-ref',
		'label'  => 'MPGS Online Partially Refunded',
	),
	array(
		'status' => 'wc-mpgs-auth-rev',
		'label'  => 'MPGS Online Auth Reversed',
	),
	array(
		'status' => 'wc-mpgs-full-ref',
		'label'  => 'MPGS Online Fully Refunded',
	),
	
	
	
	
);
