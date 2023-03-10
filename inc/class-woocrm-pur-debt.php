<?php

namespace Telsky\Woocrm\Controller;

use WP_Error;
use WP_REST_Request;
use WP_REST_Server;
use Telsky\Woocrm\Services\Pur_Debt_Service;

/**
 *
 * @version 1.0
 * @package WooCRM
 */

class Woocrm_Pur_Debt extends Woocrm_Controller {

	private Pur_Debt_Service $pur_debt_service;
	public function __construct() {
		register_rest_route(
			$this->get_namespace(),
			'pur-debt/search',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'show' ),
					'permission_callback' => function ( WP_REST_Request $request ) {
						return $this->has_role( $request, array( 'PUR_DEBT' ) );
					},
				),
				'schema' => array( $this, 'get_items_schema' ),
			)
		);
		register_rest_route(
			$this->get_namespace(),
			'pur-debt/store',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'store' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'PUR_DEBT' ) );
				},
				'args'                => $this->prefix_get_data_arguments_store(),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'pur-debt/delete/(?P<debt_id>\d+)',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'PUR_DEBT' ) );
				},
			)
		);
		$this->pur_debt_service = new Pur_Debt_Service();
	}

	/**
	 * View detail a list purchase debt of partner
	 *
	 * @param WP_REST_Request $request
	 */
	public function show( WP_REST_Request $request ) {
		$params = $request->get_params();

		return rest_ensure_response(
			array(
				'total_value' => $this->pur_debt_service->select_total_value( $params['partner_id'] ),
				'total_payed' => $this->pur_debt_service->select_total_payed( $params['partner_id'] ),
				'paymentList' => $this->pur_debt_service->select_list_payed( $params ),
			)
		);
	}
	/**
	 * Add new a payment of partner
	 *
	 * @param WP_REST_Request $request
	 */
	public function store( WP_REST_Request $request ) {
		 $params = $request->get_params();

		$payment_value = $params['total_payed'];
		$total_value   = $this->pur_debt_service->select_total_value( $params['partner_id'] );
		$total_payed   = $this->pur_debt_service->select_total_payed( $params['partner_id'] );

		if ( $total_value === 0 || $total_value === $total_payed ) {
			return $this->form_valid( 'Kh??ng c?? th??ng tin c??ng n??? c???n thanh to??n!' );
		}

		if ( $payment_value >= 0 && ( $total_payed + $payment_value ) <= $total_value ) {
			$debt_id = $this->pur_debt_service->insert_pur_debt( $params, $request->user_login );
		} else {
			return $this->form_valid( 'S??? ti???n thanh to??n kh??ng ch??nh x??c!' );
		}

		return rest_ensure_response(
			array(
				'debt_id' => $debt_id,
			)
		);
	}

	/**
	 * validation request data
	 *
	 * @param WP_REST_Request $request
	 * @return array
	 */
	public function prefix_data_arg_validate_callback( $value, $request, $param ) {
		if ( 'total_payed' === $param && $value <= 0 ) {
			return $this->form_valid( 'T???ng s??? ti???n thanh to??n ph???i l???n h??n 0!' );
		}
		if ( 'remark' === $param && strlen( $value ) > 255 ) {
			return $this->form_valid( 'Ghi ch?? kh??ng nh???p qu?? 255 k?? t???!' );
		}
	}
	/**
	 * We can use this function to contain our arguments for the pur-debt endpoint.
	 */
	public function prefix_get_data_arguments_store() {
		return array(
			'partner_id'  => array(
				'description'       => esc_html__( 'Tham s??? ID kh??ch h??ng.', 'telsky' ),
				'type'              => 'string',
				'required'          => true,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'user_id'     => array(
				'description'       => esc_html__( 'Tham s??? ID ng?????i thanh to??n.', 'telsky' ),
				'type'              => 'string',
				'required'          => true,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'pay_date'    => array(
				'description'       => esc_html__( 'Tham s??? ng??y thanh to??n.', 'telsky' ),
				'type'              => 'string',
				'required'          => true,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'total_payed' => array(
				'description'       => esc_html__( 'Tham s??? t???ng gi?? tr??? ???? thanh to??n.', 'telsky' ),
				'type'              => 'string',
				'required'          => false,
				'maxLength'         => 1,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'remark'      => array(
				'description'       => esc_html__( 'Tham s??? ghi ch??.', 'telsky' ),
				'type'              => 'string',
				'required'          => false,
				'maxLength'         => 255,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}
	/**
	 * delete payment of partner
	 *
	 * @param WP_REST_Request $request
	 */
	public function delete( WP_REST_Request $request ) {
		$params = $request->get_params();
		if ( empty( $params['debt_id'] ) || $this->pur_debt_service->count_exists( $params['debt_id'] ) <= 0 ) {
			return $this->form_valid( 'ID thanh to??n kh??ng t???n t???i!' );
		}

		$this->pur_debt_service->delete_pur_debt( $params['debt_id'] );

		return rest_ensure_response(
			array(
				'debt_id' => $params['debt_id'],
			)
		);
	}

	/**
	 * Get item object schema.
	 *
	 * @return array The customers schema for response.
	 */
	public function get_items_schema() {
		// Get schema from cache.
		if ( $this->schema ) {
			return $this->schema;
		}

		$this->schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'item',
			'type'       => 'object',
			'properties' => array(
				'data'  => array(
					'description' => esc_html__( 'Danh s??ch th??ng tin thanh to??n.', 'telsky' ),
					'type'        => 'array',
					'items'       => $this->item_schema(),
				),
				'total' => array(
					'description' => esc_html__( 'T???ng s??? b???n ghi t??m th???y.', 'telsky' ),
					'type'        => 'integer',
				),
			),
		);

		return $this->schema;
	}
	/**
	 * Base item for schema object.
	 */
	private function item_schema() {
		return array(
			'debt_id'     => array(
				'description' => esc_html__( 'ID thanh to??n.', 'telsky' ),
				'type'        => 'integer',
			),
			'pur_cd'      => array(
				'description' => esc_html__( 'M?? ????n h??ng.', 'telsky' ),
				'type'        => 'string',
			),
			'pay_date'    => array(
				'description' => esc_html__( 'Ng??y thanh to??n.', 'telsky' ),
				'type'        => 'string',
			),
			'total_payed' => array(
				'description' => esc_html__( 'S??? ti???n thanh to??n.', 'telsky' ),
				'type'        => 'string',
			),
		);
	}
}
