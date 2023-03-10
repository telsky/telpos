<?php

namespace Telsky\Woocrm\Controller;

use WP_Error;
use WP_REST_Request;
use WP_REST_Server;
use Telsky\Woocrm\Services\Pur_Order_Service;

/**
 *
 * @version 1.0
 * @package WooCRM
 */

class Woocrm_Pur_Order extends Woocrm_Controller {

	private Pur_Order_Service $pur_order_service;
	public function __construct() {
		register_rest_route(
			$this->get_namespace(),
			'pur-order/search',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search' ),
					'permission_callback' => function ( WP_REST_Request $request ) {
						return $this->has_role( $request, array( 'PUR_ORDER' ) );
					},
				),
				'schema' => array( $this, 'get_items_schema' ),
			)
		);
		register_rest_route(
			$this->get_namespace(),
			'pur-order/store',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'store' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'PUR_ORDER' ) );
				},
				'args'                => $this->prefix_get_data_arguments_store(),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'pur-order/show/(?P<pur_id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'show' ),
					'permission_callback' => function ( WP_REST_Request $request ) {
						return $this->has_role( $request, array( 'PUR_ORDER' ) );
					},
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'pur-order/update',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'PUR_ORDER' ) );
				},
				'args'                => $this->prefix_get_data_arguments_for_update(),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'pur-order/delete/(?P<pur_id>\d+)',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'PUR_ORDER' ) );
				},
			)
		);
		$this->pur_order_service = new Pur_Order_Service();
	}
	/**
	 * searching purchase order
	 *
	 * @param WP_REST_Request $request
	 */
	public function search( WP_REST_Request $request ) {
		$params = $request->get_params();
		$result = $this->pur_order_service->select_list_pur_order( $params );
		return rest_ensure_response( $result );
	}
	/**
	 * View detail a purchase order
	 *
	 * @param WP_REST_Request $request
	 */
	public function show( WP_REST_Request $request ) {
		$params = $request->get_params();
		$pur_id = $params['pur_id'];

		return rest_ensure_response(
			array(
				'order'    => $this->pur_order_service->select_one_pur_order( $pur_id ),
				'products' => $this->pur_order_service->select_list_pur_order_items( $pur_id ),
			)
		);
	}
	/**
	 * Add new a purchase order
	 *
	 * @param WP_REST_Request $request
	 */
	public function store( WP_REST_Request $request ) {
		 $params = $request->get_params();

		// save purchase informations
		$pur_id = $this->pur_order_service->insert_pur_order( $params, $request->user_login, $request->user_id );

		return rest_ensure_response(
			array(
				'pur_id' => $pur_id,
			)
		);
	}
	/**
	 * update purchase order
	 *
	 * @param WP_REST_Request $request
	 */
	public function update( WP_REST_Request $request ) {
		$params = $request->get_params();

		$this->pur_order_service->update_pur_order( $params, $request->user_login, $request->user_id );
		return rest_ensure_response(
			array(
				'pur_id' => $params['pur_id'],
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
		 $params = $request->get_params();
		if ( 'pur_cd' === $param ) {
			if ( strlen( $value ) > 50 ) {
				return $this->form_valid( 'M?? ????n h??ng kh??ng nh???p qu?? 50 k?? t???!' );
			}
			if ( $this->pur_order_service->count_exist_by_pur_cd( $value, $request['pur_id'] ) > 0 ) {
				return $this->form_valid( 'M?? ????n ???? t???n t???i!' );
			}
		}
		if ( 'remark' === $param && strlen( $value ) > 255 ) {
			return $this->form_valid( 'Ghi ch?? kh??ng nh???p qu?? 255 k?? t???!' );
		}
		if ( 'product_id' === $param ) {
			if ( empty( $value ) ) {
				return $this->form_valid( 'Danh s??ch s???n ph???m trong ????n h??ng kh??ng h???p l???!' );
			}
			if (
				empty( $params['quantity'] )
				|| empty( $params['unit_price'] )
				|| count( $value ) !== count( $params['quantity'] )
				|| count( $value ) !== count( $params['unit_price'] ) ) {
					return $this->form_valid( 'Danh s??ch s???n ph???m trong ????n h??ng kh??ng h???p l???!' );
			}
			foreach ( $params['product_id'] as $index => $prod_id ) {
				if ( empty( $params['quantity'][ $index ] ) ) {
					return $this->form_valid( 'Ch??a nh???p s??? l?????ng s???n ph???m!' );
				}
				if ( empty( $params['unit_price'][ $index ] ) ) {
					return $this->form_valid( 'Ch??a nh???p ????n gi?? s???n ph???m!' );
				}
			}
		}
	}
	/**
	 * Validate a request argument based on details registered to the route.
	 *
	 * @param  mixed           $value   Value of the argument.
	 * @param  WP_REST_Request $request The current request object.
	 * @param  string          $param   Key of the parameter.
	 * @return WP_Error|boolean
	 */
	public function prefix_update_data_arg_validate_callback( $value, $request, $param ) {
		$this->prefix_data_arg_validate_callback( $value, $request, $param );
		if ( 'pur_id' == $param ) {
			if ( empty( $params['pur_id'] ) ) {
				return $this->form_valid( 'ID ????n h??ng l?? b???t bu???c!' );
			}
			if ( $this->pur_order_service->count_exists( $params['pur_id'] ) <= 0 ) {
				return $this->form_valid( '????n h??ng kh??ng t???n t???i!' );
			}
		}
	}
	/**
	 * We can use this function to contain our arguments for the pur-order endpoint.
	 */
	public function prefix_get_data_arguments_for_update() {
		$args           = $this->prefix_get_data_arguments_store();
		$args['pur_id'] = array(
			'description'       => esc_html__( 'Tham s??? ID ????n h??ng.', 'telsky' ),
			'type'              => 'integer',
			'required'          => true,
			'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
		);
		return $args;
	}
		/**
		 * We can use this function to contain our arguments for the customer endpoint.
		 */
	public function prefix_get_data_arguments_store() {
		return array(
			'pur_cd'      => array(
				'description'       => esc_html__( 'Tham s??? m?? ????n h??ng.', 'telsky' ),
				'type'              => 'string',
				'required'          => true,
				'maxLength'         => 50,
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
			'total_value' => array(
				'description'       => esc_html__( 'Tham s??? t???ng gi?? tr???.', 'telsky' ),
				'type'              => 'string',
				'required'          => false,
				'maxLength'         => 1,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'pur_date'    => array(
				'description'       => esc_html__( 'Tham s??? ng??y nh???p.', 'telsky' ),
				'type'              => 'string',
				'required'          => true,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'unit_price'  => array(
				'description'       => esc_html__( 'List ????n gi??.', 'telsky' ),
				'type'              => 'array',
				'required'          => true,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
			),
			'product_id'  => array(
				'description'       => esc_html__( 'List S???n ph???m.', 'telsky' ),
				'type'              => 'array',
				'required'          => true,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
			),
			'quantity'    => array(
				'description'       => esc_html__( 'List s??? l?????ng.', 'telsky' ),
				'type'              => 'array',
				'required'          => true,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
			),
			'wh_id'       => array(
				'description'       => esc_html__( 'Id kho.', 'telsky' ),
				'type'              => 'integer',
				'required'          => true,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
			),
			'partner_id'  => array(
				'description'       => esc_html__( 'Id Nh?? cung c???p.', 'telsky' ),
				'type'              => 'integer',
				'required'          => true,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
			),
		);
	}
	/**
	 * delete purchase
	 *
	 * @param WP_REST_Request $request
	 */
	public function delete( WP_REST_Request $request ) {
		$params = $request->get_params();
		if ( empty( $params['pur_id'] ) || $this->pur_order_service->count_exists( $params['pur_id'] ) <= 0 ) {
			return rest_ensure_response(
				array(
					__( 'ID ????n h??ng kh??ng t???n t???i!' ),
				),
				400
			);
		}

		$this->pur_order_service->delete_pur_order( $params['pur_id'], $request->user_login, $request->user_id );

		return rest_ensure_response(
			array(
				'pur_id' => $params['pur_id'],
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
					'description' => esc_html__( 'Danh s??ch ????n h??ng.', 'telsky' ),
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
	 * Get item object schema.
	 *
	 * @return array The customer schema for response.
	 */
	public function get_item_schema() {
		// Get schema from cache.
		if ( $this->schema ) {
			return $this->schema;
		}

		$this->schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'pur_order',
			'type'       => 'object',
			'properties' => $this->item_schema(),
		);

		return $this->schema;
	}

	/**
	 * Base item for schema object.
	 */
	private function item_schema() {
		return array(
			'pur_id'       => array(
				'description' => esc_html__( 'ID ????n h??ng.', 'telsky' ),
				'type'        => 'integer',
			),
			'pur_cd'       => array(
				'description' => esc_html__( 'M?? ????n h??ng.', 'telsky' ),
				'type'        => 'string',
			),
			'partner_id'   => array(
				'description' => esc_html__( 'Id NCC.', 'telsky' ),
				'type'        => 'string',
			),
			'pur_date'     => array(
				'description' => esc_html__( 'Ng??y nh???p.', 'telsky' ),
				'type'        => 'string',
			),
			'wh_id'        => array(
				'description' => esc_html__( 'ID kho.', 'telsky' ),
				'type'        => 'string',
			),
			'user_id'      => array(
				'description' => esc_html__( 'Ng?????i xu???t.', 'telsky' ),
				'type'        => 'string',
			),
			'total_value'  => array(
				'description' => esc_html__( 'T???ng gi?? tr???.', 'telsky' ),
				'type'        => 'string',
			),
			'remark'       => array(
				'description' => esc_html__( 'Ghi ch??.', 'telsky' ),
				'type'        => 'string',
			),
			'updated_at'   => array(
				'description' => esc_html__( 'Ng??y c???p nh???t.', 'telsky' ),
				'type'        => 'date-time',
			),
			'updated_user' => array(
				'description' => esc_html__( 'Ng?????i c???p nh???t.', 'telsky' ),
				'type'        => 'string',
			),
		);
	}
}
