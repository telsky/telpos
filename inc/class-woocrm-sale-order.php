<?php

namespace Telsky\Woocrm\Controller;

use Telsky\Woocrm\Services\Inventory_Service;
use Utility;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use Telsky\Woocrm\Services\Sale_Order_Service;

/**
 *
 * @version 1.0
 * @package WooCRM
 */

class Woocrm_Sale_Order extends Woocrm_Controller {

	private Sale_Order_Service $sale_order_service;
	private Inventory_Service $inventory_service;
	public function __construct() {
		register_rest_route(
			$this->get_namespace(),
			'sale-order/search',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search' ),
					'permission_callback' => function ( WP_REST_Request $request ) {
						return $this->has_role( $request, array( 'SALE_ORDER' ) );
					},
				),
				'schema' => array( $this, 'get_items_schema' ),
			)
		);
		register_rest_route(
			$this->get_namespace(),
			'sale-order/store',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'store' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'SALE_ORDER' ) );
				},
				'args'                => $this->prefix_get_data_arguments_store(),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'sale-order/show/(?P<sale_id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'show' ),
					'permission_callback' => function ( WP_REST_Request $request ) {
						return $this->has_role( $request, array( 'SALE_ORDER' ) );
					},
					'schema'              => array( $this, 'get_item_schema' ),
				),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'sale-order/update',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'SALE_ORDER' ) );
				},
				'args'                => $this->prefix_get_data_arguments_for_update(),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'sale-order/delete/(?P<sale_id>\d+)',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'SALE_ORDER' ) );
				},
			)
		);

		$this->sale_order_service = new Sale_Order_Service();
		$this->inventory_service  = new Inventory_Service();
	}
	/**
	 * searching sale order
	 *
	 * @param WP_REST_Request $request
	 */
	public function search( WP_REST_Request $request ) {
		$results = $this->sale_order_service->select_list_sale_order( $request->get_params() );

		return rest_ensure_response( $results );
	}
	/**
	 * View detail a sale order
	 *
	 * @param WP_REST_Request $request
	 */
	public function show( WP_REST_Request $request ) {
		$params  = $request->get_params();
		$sale_id = $params['sale_id'];
		return rest_ensure_response(
			array(
				'order'    => $this->sale_order_service->select_one_sale_order( $sale_id ),
				'products' => $this->sale_order_service->select_list_sale_order_items( $sale_id ),
			)
		);
	}
	/**
	 * Add new a sale order
	 *
	 * @param WP_REST_Request $request
	 */
	public function store( WP_REST_Request $request ) {
		 $params = $request->get_params();
		$sale_id = $this->sale_order_service->insert_sale_order( $params, $request->user_login, $request->user_id );
		return rest_ensure_response(
			array(
				'sale_id' => $sale_id,
			)
		);
	}
	/**
	 * update sale order
	 *
	 * @param WP_REST_Request $request
	 */
	public function update( WP_REST_Request $request ) {
		$params = $request->get_params();

		$this->sale_order_service->update_sale_order( $params, $request->user_login, $request->user_id );
		return rest_ensure_response(
			array(
				'sale_id' => $params['sale_id'],
			)
		);
	}

	/**
	 * validation request data
	 *
	 *  @param  mixed           $value   Value of the argument.
	 * @param  WP_REST_Request $request The current request object.
	 * @param  string          $param   Key of the parameter.
	 * @return WP_Error|boolean
	 */
	public function prefix_data_arg_validate_callback( $value, $request, $param ) {
		 $params = $request->get_params();
		if ( 'sale_cd' === $param ) {
			if ( strlen( $value ) > 50 ) {
				return $this->form_valid( 'M?? ????n h??ng kh??ng nh???p qu?? 50 k?? t???!' );
			}
			if ( $this->sale_order_service->count_exist_by_sale_cd( $value, $request['sale_id'] ) > 0 ) {
				return $this->form_valid( 'M?? ????n h??ng ???? t???n t???i!' );
			}
		}
		if ( 'remark' === $param && strlen( $value ) > 255 ) {
			return $this->form_valid( 'Ghi ch?? kh??ng nh???p qu?? 255 k?? t???!' );
		}
		if ( 'product_id' === $param ) {
			if ( empty( $value ) ) {
				return $this->form_valid( 'Kh??ng tim th???y s???n ph???m trong ????n h??ng!' );
			}
			if ( ! empty( $value ) ) {
				foreach ( $value as $val ) {
					if ( empty( $val ) ) {
						return $this->form_valid( 'Ch??a nh???p s???n ph???m!' );
					}
				}
			}
		}
		if ( 'quantity' === $param ) {
			if ( empty( $value ) ) {
				return $this->form_valid( 'Ch??a nh???p s??? l?????ng s???n ph???m!' );
			}
			if ( ! empty( $value ) ) {
				foreach ( $value as $index => $val ) {
					if ( empty( $val ) ) {
						return $this->form_valid( 'Ch??a nh???p s??? l?????ng s???n ph???m!' );
					}
					if ( $val <= 0 ) {
						return $this->form_valid( 'Ch??a nh???p s??? l?????ng s???n ph???m!' );
					}
					$product_ids = $params['product_id'];
					if ( empty( $product_ids ) ) {
						return $this->form_valid( 'Ch??a nh???p s??? l?????ng s???n ph???m!' );
					}
					$prod_id   = $product_ids[ $index ];
					$inventory = $this->inventory_service->select_one_inventory( $params['wh_id'], $prod_id );
					if ( empty( $inventory ) || $inventory->quantity < $val ) {
						 return $this->form_valid( 'S??? l?????ng t???n kho kh??ng ?????!' );
					}
				}
			}
		}
		if ( 'unit_price' === $param ) {
			if ( empty( $value ) ) {
				return $this->form_valid( 'Ch??a nh???p ????n gi??!' );
			}
			if ( ! empty( $value ) ) {
				foreach ( $value as $val ) {
					if ( empty( $val ) ) {
						return $this->form_valid( 'Ch??a nh???p ????n gi??!' );
					}
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
		if ( 'sale_id' == $param ) {
			if ( empty( $params['sale_id'] ) ) {
				return $this->form_valid( 'ID ????n h??ng l?? b???t bu???c!' );
			}
			if ( $this->sale_order_service->count_exists( $params['sale_id'] ) <= 0 ) {
				return $this->form_valid( '????n h??ng kh??ng t???n t???i!' );
			}
		}
	}
	/**
	 * We can use this function to contain our arguments for the customer endpoint.
	 */
	public function prefix_get_data_arguments_for_update() {
		$args            = $this->prefix_get_data_arguments_store();
		$args['sale_id'] = array(
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
			'sale_cd'     => array(
				'description'       => esc_html__( 'Tham s??? m?? ????n h??ng.', 'telsky' ),
				'type'              => 'string',
				'required'          => true,
				'maxLength'         => 50,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'sale_date'   => array(
				'description'       => esc_html__( 'Tham s??? ng??y b??n h??ng.', 'telsky' ),
				'type'              => 'string',
				'required'          => true,
				'maxLength'         => 255,
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
			'total_payed' => array(
				'description'       => esc_html__( 'T???ng ti???n ???? thanh to??n.', 'telsky' ),
				'type'              => 'integer',
				'required'          => true,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
			),
			'quantity'    => array(
				'description'       => esc_html__( 'S??? l?????ng s???n ph???m.', 'telsky' ),
				'type'              => 'array',
				'required'          => true,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
			),
			'unit_price'  => array(
				'description'       => esc_html__( '????n gi?? gi???n ph???m.', 'telsky' ),
				'type'              => 'array',
				'required'          => true,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
			),
			'product_id'  => array(
				'description'       => esc_html__( 'Id s???n ph???m.', 'telsky' ),
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
			'customer_id' => array(
				'description'       => esc_html__( 'Id kh??ch h??ng.', 'telsky' ),
				'type'              => 'integer',
				'required'          => true,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
			),
		);
	}
	/**
	 * delete sale
	 *
	 * @param WP_REST_Request $request
	 */
	public function delete( WP_REST_Request $request ) {
		global $wpdb;
		$params = $request->get_params();
		if ( empty( $params['sale_id'] ) || $this->sale_order_service->count_exists( $params['sale_id'] ) <= 0 ) {
			return rest_ensure_response(
				array(
					__( 'ID ????n h??ng kh??ng t???n t???i!' ),
				),
				400
			);
		}

		$this->sale_order_service->delete_sale_order( $params['sale_id'], $request->user_login, $request->user_id );

		return rest_ensure_response(
			array(
				'sale_id' => $params['sale_id'],
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
			'title'      => 'sale_order',
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
			'sale_id'      => array(
				'description' => esc_html__( 'ID ????n h??ng.', 'telsky' ),
				'type'        => 'integer',
			),
			'sale_cd'      => array(
				'description' => esc_html__( 'M?? ????n h??ng.', 'telsky' ),
				'type'        => 'string',
			),
			'custom_id'    => array(
				'description' => esc_html__( 'Id Kh??ch h??ng.', 'telsky' ),
				'type'        => 'string',
			),
			'sale_date'    => array(
				'description' => esc_html__( 'Ng??y b??n.', 'telsky' ),
				'type'        => 'string',
			),
			'wh_id'        => array(
				'description' => esc_html__( 'ID kho.', 'telsky' ),
				'type'        => 'string',
			),
			'user_id'      => array(
				'description' => esc_html__( 'Ng?????i b??n.', 'telsky' ),
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
			'total_payed'  => array(
				'description' => esc_html__( 'T???ng s??? ti???n ???? thanh to??n.', 'telsky' ),
				'type'        => 'integer',
			),
		);
	}
}
